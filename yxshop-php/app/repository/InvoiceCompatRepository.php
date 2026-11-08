<?php

namespace app\repository;

use support\Db;

/**
 * 发票兼容层数据仓储。
 *
 * 只负责纯数据操作（表结构探查、增删改查、分页），
 * 不做任何业务判断；业务规则见 \app\service\InvoiceCompatService。
 *
 * @package app\repository
 */
class InvoiceCompatRepository
{
    /** @var array<string, array<int, string>> 表名 => 列名列表 */
    private array $columnCache = [];

    /**
     * 读取表的列名列表（进程内缓存）。
     *
     * @return array<int, string>
     */
    public function columns(string $table): array
    {
        if (!isset($this->columnCache[$table])) {
            $rows = Db::select('SHOW COLUMNS FROM `' . $table . '`');
            $cols = [];
            foreach ($rows as $row) {
                $row = (array) $row;
                $cols[] = (string) $row['Field'];
            }
            $this->columnCache[$table] = $cols;
        }
        return $this->columnCache[$table];
    }

    /**
     * 条件分页查询。
     *
     * @param array<string, mixed> $filters 已按列白名单过滤过的筛选条件
     * @return array{list: array<int, array<string, mixed>>, total: int}
     */
    public function paginate(string $table, array $filters, int $page, int $size, string $sort, string $order): array
    {
        $query = Db::table($table);
        foreach ($filters as $key => $value) {
            if (is_array($value) && count($value) === 2 && $value[0] === 'like') {
                $query->where($key, 'like', $value[1]);
            } else {
                $query->where($key, $value);
            }
        }

        $total = (clone $query)->count();
        $list = $query->orderBy($sort, $order)
            ->offset(($page - 1) * $size)
            ->limit($size)
            ->get()
            ->toArray();

        return ['list' => $list, 'total' => (int) $total];
    }

    /**
     * 插入一条记录，返回自增 ID。
     */
    public function insert(string $table, array $fill): int
    {
        return (int) Db::table($table)->insertGetId($fill);
    }

    /**
     * 按 ID 更新，返回受影响行数。
     */
    public function updateById(string $table, $id, array $fill): int
    {
        return (int) Db::table($table)->where('id', $id)->update($fill);
    }

    /**
     * 按条件更新，返回受影响行数。
     */
    public function updateWhere(string $table, array $where, array $fill): int
    {
        $query = Db::table($table);
        foreach ($where as $key => $value) {
            $query->where($key, $value);
        }
        return (int) $query->update($fill);
    }

    /**
     * 按 ID 集合删除（软删除或物理删除），返回受影响行数。
     */
    public function deleteByIds(string $table, array $ids, bool $softDelete, array $softFill = []): int
    {
        $query = Db::table($table)->whereIn('id', $ids);
        if ($softDelete) {
            return (int) $query->update($softFill);
        }
        return (int) $query->delete();
    }

    /**
     * 按 ID 读取单列值。
     */
    public function valueById(string $table, string $column, $id)
    {
        return Db::table($table)->where('id', $id)->value($column);
    }

    /**
     * 统计条数（可带条件）。
     */
    public function count(string $table, array $where = []): int
    {
        $query = Db::table($table);
        foreach ($where as $key => $value) {
            $query->where($key, $value);
        }
        return (int) (clone $query)->count();
    }
}
