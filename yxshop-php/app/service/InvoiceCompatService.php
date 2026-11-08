<?php

namespace app\service;

use app\exception\BusinessException;
use app\repository\InvoiceCompatRepository;

/**
 * 发票兼容层业务服务。
 *
 * 承载开源版发票模块的业务规则：资源/字段白名单、可写字段计算、
 * 时间戳维护、默认项互斥、统计口径。数据访问全部委托给
 * \app\repository\InvoiceCompatRepository，Controller 只做协议适配。
 *
 * 安全约束：
 * - 表名只能来自 TABLES 白名单，绝不接收请求参数；
 * - 写入/查询字段按实际表结构过滤，杜绝列名注入；
 * - 值全部经查询构建器参数绑定；
 * - 分页上限 100。
 *
 * @package app\service
 */
class InvoiceCompatService
{
    /** 资源路径 => 数据表（不含末尾 id 与动作后缀） */
    public const TABLES = [
        'headers' => 'yxshop_invoice_headers',
        'records' => 'yxshop_invoice_records',
    ];

    /** 语义动作后缀 */
    public const ACTION_SUFFIXES = [
        'batch-delete', 'default', 'stats', 'status', 'cancel',
    ];

    /** 模糊匹配的列（其余等值匹配） */
    public const LIKE_COLUMNS = [
        'title', 'header_name', 'invoice_no', 'company_name', 'tax_no',
    ];

    /** 由系统维护、不允许请求直接写入的列 */
    public const GUARDED = ['id', 'created_at', 'updated_at', 'deleted_at'];

    private InvoiceCompatRepository $repository;

    public function __construct(?InvoiceCompatRepository $repository = null)
    {
        $this->repository = $repository ?? new InvoiceCompatRepository();
    }

    /**
     * 把资源路径解析成 [表名, 动作, id]。
     *
     * @return array{0: string|null, 1: string|null, 2: int|null}
     */
    public function resolve(string $path): array
    {
        $segments = explode('/', trim($path, '/'));
        $id = null;
        if ($segments && ctype_digit((string) end($segments))) {
            $id = (int) array_pop($segments);
        }
        $action = null;
        if ($segments && in_array(end($segments), self::ACTION_SUFFIXES, true)) {
            $action = array_pop($segments);
        }
        $resource = implode('/', $segments);

        return [self::TABLES[$resource] ?? null, $action, $id];
    }

    /**
     * 列表查询。
     *
     * @param array<string, mixed> $query 已按列白名单过滤的查询参数
     * @return array{list: array<int, array<string, mixed>>, total: int, page: int, page_size: int}
     */
    public function paginate(string $table, array $query): array
    {
        $cols = $this->columns($table);
        $filters = [];
        // 软删除过滤：仅展示未删除的记录
        if (in_array('deleted_at', $cols, true)) {
            $filters['deleted_at'] = 0;
        }
        foreach ($query as $key => $value) {
            if ($value === '' || $value === null || !is_scalar($value)) {
                continue;
            }
            if (!in_array($key, $cols, true) || in_array($key, self::GUARDED, true)) {
                continue;
            }
            $filters[$key] = in_array($key, self::LIKE_COLUMNS, true)
                ? ['like', '%' . $value . '%']
                : $value;
        }

        $page = max(1, min(10000, (int) ($query['page'] ?? 1)));
        $size = max(1, min(100, (int) ($query['page_size'] ?? 20)));
        $sort = (string) ($query['sort'] ?? 'id');
        if (!in_array($sort, $cols, true)) {
            $sort = 'id';
        }
        $order = strtolower((string) ($query['order'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

        $result = $this->repository->paginate($table, $filters, $page, $size, $sort, $order);

        return [
            'list'      => $result['list'],
            'total'     => $result['total'],
            'page'      => $page,
            'page_size' => $size,
        ];
    }

    /**
     * 新增记录。
     *
     * @return array{id: int}
     */
    public function create(string $table, array $data): array
    {
        $cols = $this->columns($table);
        $fill = $this->fillable($table, $data);
        if (!$fill) {
            throw new BusinessException('没有可写入的有效字段');
        }
        $now = time();
        if (in_array('created_at', $cols, true)) {
            $fill['created_at'] = $now;
        }
        if (in_array('updated_at', $cols, true)) {
            $fill['updated_at'] = $now;
        }
        if (in_array('deleted_at', $cols, true)) {
            $fill['deleted_at'] = 0;
        }
        if (in_array('app_id', $cols, true) && !isset($fill['app_id'])) {
            $fill['app_id'] = 0;
        }

        return ['id' => $this->repository->insert($table, $fill)];
    }

    /**
     * 更新记录。
     *
     * @return array{affected: int}
     */
    public function update(string $table, $id, array $data): array
    {
        if (!$id) {
            throw new BusinessException('缺少记录 ID');
        }
        $cols = $this->columns($table);
        $fill = $this->fillable($table, $data);
        if (in_array('updated_at', $cols, true)) {
            $fill['updated_at'] = time();
        }
        if (!$fill) {
            return ['affected' => 0];
        }

        return ['affected' => $this->repository->updateById($table, $id, $fill)];
    }

    /**
     * 删除记录（软删除优先）。
     *
     * @return array{affected: int}
     */
    public function delete(string $table, $id): array
    {
        if (!$id) {
            throw new BusinessException('缺少记录 ID');
        }
        return $this->deleteByIds($table, [$id]);
    }

    /**
     * 批量删除。
     *
     * @param array<int, mixed> $ids
     * @return array{affected: int}
     */
    public function batchDelete(string $table, array $ids): array
    {
        $ids = array_values(array_filter(array_map('intval', $ids), fn($v) => $v > 0));
        if (!$ids) {
            throw new BusinessException('缺少要删除的 ID');
        }
        return $this->deleteByIds($table, $ids);
    }

    /**
     * 状态变更 / 设为默认等语义动作。
     *
     * @return array{affected: int}
     */
    public function applyAction(string $table, string $action, $id, array $data, $scalar): array
    {
        $cols = $this->columns($table);
        $fill = $this->fillable($table, $data);
        $now = time();

        switch ($action) {
            case 'status':
                if (is_scalar($scalar) && in_array('status', $cols, true)) {
                    $fill['status'] = $scalar;
                }
                break;
            case 'cancel':
                if (in_array('cancel_reason', $cols, true) && is_string($scalar)) {
                    $fill['cancel_reason'] = $scalar;
                }
                break;
            case 'default':
                if (!$id) {
                    throw new BusinessException('缺少记录 ID');
                }
                // 同一用户下的其它记录先取消默认，保证「每人仅一个默认」
                $userId = $this->repository->valueById($table, 'user_id', $id);
                if ($userId !== null && in_array('is_default', $cols, true)) {
                    $this->repository->updateWhere($table, ['user_id' => $userId], ['is_default' => 0]);
                }
                $fill['is_default'] = 1;
                break;
            default:
                throw new BusinessException('接口不存在', 404);
        }

        if (!$id) {
            throw new BusinessException('缺少记录 ID');
        }
        if (in_array('updated_at', $cols, true)) {
            $fill['updated_at'] = $now;
        }
        if (!$fill) {
            return ['affected' => 0];
        }

        return ['affected' => $this->repository->updateById($table, $id, $fill)];
    }

    /**
     * 统计。
     *
     * @return array{total: int, issued: int}
     */
    public function stats(string $table): array
    {
        $cols = $this->columns($table);
        $where = in_array('deleted_at', $cols, true) ? ['deleted_at' => 0] : [];

        return [
            'total'  => $this->repository->count($table, $where),
            'issued' => in_array('status', $cols, true)
                ? $this->repository->count($table, array_merge($where, ['status' => 1]))
                : 0,
        ];
    }

    /**
     * @return array<int, string>
     */
    public function columns(string $table): array
    {
        return $this->repository->columns($table);
    }

    /**
     * 删除（软删除优先），返回受影响行数。
     *
     * @param array<int, int> $ids
     * @return array{affected: int}
     */
    private function deleteByIds(string $table, array $ids): array
    {
        $cols = $this->columns($table);
        if (in_array('deleted_at', $cols, true)) {
            $fill = ['deleted_at' => time()];
            if (in_array('updated_at', $cols, true)) {
                $fill['updated_at'] = time();
            }
            return ['affected' => $this->repository->deleteByIds($table, $ids, true, $fill)];
        }
        return ['affected' => $this->repository->deleteByIds($table, $ids, false)];
    }

    /**
     * 仅保留表中真实存在、且非系统维护的列。
     */
    private function fillable(string $table, array $data): array
    {
        $cols = array_flip($this->columns($table));
        $fill = [];
        foreach ($data as $key => $value) {
            if (!is_string($key) || !isset($cols[$key]) || in_array($key, self::GUARDED, true)) {
                continue;
            }
            if (is_array($value) || is_object($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            }
            $fill[$key] = $value;
        }
        return $fill;
    }
}
