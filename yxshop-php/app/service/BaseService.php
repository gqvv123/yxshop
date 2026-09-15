<?php

namespace app\service;

use app\repository\BaseRepository;
use app\exception\BusinessException;
use support\Db;
use Exception;

/**
 * 基础服务类
 *
 * @property BaseRepository $repository
 */
abstract class BaseService
{
    protected $repository;

    protected $sensitiveLogFields = ['password', 'pay_password', 'openid', 'unionid', 'access_token', 'secret', 'api_key', 'private_key'];

    public function __construct(BaseRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * 获取仓储实例
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * 事务封装：统一入口，替代各 Service 散落的 Db::transaction
     *
     * @param callable $fn  返回值作为本方法返回值；抛异常自动回滚
     * @return mixed
     * @throws Exception
     */
    protected function transaction(callable $fn)
    {
        return Db::transaction($fn);
    }

    /**
     * 统一成功返回契约（与 UserAuthService 对齐）
     */
    protected function ok($data = null, string $message = 'success'): array
    {
        return ['success' => true, 'data' => $data, 'message' => $message];
    }

    /**
     * 统一失败返回契约
     */
    protected function fail(string $message, $data = null): array
    {
        return ['success' => false, 'data' => $data, 'message' => $message];
    }

    /**
     * 记录日志
     */
    protected function log($level, $message, $context = [])
    {
        $msg = is_string($message) ? $message : json_encode($message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $level = strtolower((string)$level);
        switch ($level) {
            case 'error':
                \support\Log::error($msg, $context);
                break;
            case 'warning':
            case 'warn':
                \support\Log::warning($msg, $context);
                break;
            case 'debug':
                \support\Log::debug($msg, $context);
                break;
            case 'info':
            default:
                \support\Log::info($msg, $context);
                break;
        }
    }

    /**
     * 记录信息日志
     */
    protected function logInfo($message, $context = [])
    {
        $this->log('info', $message, $context);
    }

    /**
     * 记录错误日志
     */
    protected function logError($message, $context = [])
    {
        $this->log('error', $message, $context);
    }

    /**
     * 记录警告日志
     */
    protected function logWarning($message, $context = [])
    {
        $this->log('warning', $message, $context);
    }

    /**
     * 记录调试日志
     */
    protected function logDebug($message, $context = [])
    {
        $this->log('debug', $message, $context);
    }

    /**
     * 抛出业务异常
     */
    protected function throwBusinessException($message, $code = 400, $data = [])
    {
        throw new \app\exception\BusinessException($message, $code, $data);
    }

    /**
     * 验证数据
     */
    protected function validate($data, $rules)
    {
        // 创建验证器实例
        $validator = new \think\Validate();
        
        // 设置验证规则
        $validator->rule($rules);
        
        // 执行验证
        if (!$validator->check($data)) {
            $this->throwBusinessException($validator->getError());
        }
        return true;
    }

    protected function validateWith($validateClass, $scene, $data)
    {
        $validate = new $validateClass();
        $validate->failException(false);
        if (!$validate->scene($scene)->check($data)) {
            $this->throwBusinessException((string)$validate->getError());
        }
        return true;
    }

    protected function filterNullValues(array $data)
    {
        return array_filter($data, fn($v) => $v !== null && $v !== '');
    }

    protected function maskSensitiveData(array $data): array
    {
        foreach ($this->sensitiveLogFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '***';
            }
        }
        return $data;
    }

    /**
     * 分页查询
     */
    public function paginate($page = 1, $limit = 15, $conditions = [])
    {
        try {
            return $this->repository->paginate($page, $limit, $conditions);
        } catch (Exception $e) {
            $this->logError('分页查询失败', [
                'page' => $page,
                'limit' => $limit,
                'conditions' => $conditions,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 根据ID查找
     */
    public function find($id)
    {
        try {
            return $this->repository->find($id);
        } catch (Exception $e) {
            $this->logError('查找记录失败', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 根据ID查找或失败
     */
    public function findOrFail($id)
    {
        try {
            return $this->repository->findOrFail($id);
        } catch (Exception $e) {
            $this->logError('查找记录失败', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 创建记录
     */
    public function create(array $data)
    {
        try {
            $this->logInfo('创建记录', ['data' => $this->maskSensitiveData($data)]);
            $result = $this->repository->create($data);
            $this->logInfo('创建记录成功', ['id' => $result->getKey()]);
            return $result;
        } catch (Exception $e) {
            $this->logError('创建记录失败', [
                'data' => $this->maskSensitiveData($data),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 更新记录
     */
    public function update($id, array $data)
    {
        try {
            $this->logInfo('更新记录', ['id' => $id, 'data' => $this->maskSensitiveData($data)]);
            $result = $this->repository->update($id, $data);
            $this->logInfo('更新记录成功', ['id' => $id]);
            return $result;
        } catch (Exception $e) {
            $this->logError('更新记录失败', [
                'id' => $id,
                'data' => $this->maskSensitiveData($data),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 删除记录
     */
    public function delete($id)
    {
        try {
            $this->logInfo('删除记录', ['id' => $id]);
            $result = $this->repository->delete($id);
            $this->logInfo('删除记录成功', ['id' => $id]);
            return $result;
        } catch (Exception $e) {
            $this->logError('删除记录失败', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 软删除
     */
    public function softDelete($id)
    {
        try {
            $this->logInfo('软删除记录', ['id' => $id]);
            $result = $this->repository->softDelete($id);
            $this->logInfo('软删除记录成功', ['id' => $id]);
            return $result;
        } catch (Exception $e) {
            $this->logError('软删除记录失败', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 恢复软删除
     */
    public function restore($id)
    {
        try {
            $this->logInfo('恢复软删除记录', ['id' => $id]);
            $result = $this->repository->restore($id);
            $this->logInfo('恢复软删除记录成功', ['id' => $id]);
            return $result;
        } catch (Exception $e) {
            $this->logError('恢复软删除记录失败', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 统计数量
     */
    public function count(array $conditions = [])
    {
        try {
            return $this->repository->count($conditions);
        } catch (Exception $e) {
            $this->logError('统计数量失败', [
                'conditions' => $conditions,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 检查是否存在
     */
    public function exists(array $conditions)
    {
        try {
            return $this->repository->exists($conditions);
        } catch (Exception $e) {
            $this->logError('检查存在性失败', [
                'conditions' => $conditions,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 获取第一条记录
     */
    public function first(array $conditions = [])
    {
        try {
            return $this->repository->first($conditions);
        } catch (Exception $e) {
            $this->logError('获取第一条记录失败', [
                'conditions' => $conditions,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 获取所有记录
     */
    public function all()
    {
        try {
            return $this->repository->all();
        } catch (Exception $e) {
            $this->logError('获取所有记录失败', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 通用：按 app 分页
     */
    public function paginatedByApp($appId, int $pageSize = 20, string $orderBy = 'id', string $direction = 'desc')
    {
        return $this->repository->paginatedByApp((int) $appId, $pageSize, $orderBy, $direction);
    }

    /**
     * 通用：按 app 列表
     */
    public function listByApp($appId, string $orderBy = 'id', string $direction = 'desc')
    {
        return $this->repository->listByApp((int) $appId, $orderBy, $direction);
    }

    /**
     * 搜索
     */
    public function search($keyword, array $fields = [], $page = 1, $limit = 15)
    {
        try {
            $this->logInfo('搜索记录', [
                'keyword' => $keyword,
                'fields' => $fields,
                'page' => $page,
                'limit' => $limit
            ]);
            return $this->repository->search($keyword, $fields, $page, $limit);
        } catch (Exception $e) {
            $this->logError('搜索记录失败', [
                'keyword' => $keyword,
                'fields' => $fields,
                'page' => $page,
                'limit' => $limit,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 批量操作
     */
    public function batch($action, array $ids, array $data = [])
    {
        try {
            $this->logInfo('批量操作', [
                'action' => $action,
                'ids' => $ids,
                'data' => $data
            ]);

            $result = false;
            switch ($action) {
                case 'delete':
                    $result = $this->repository->deleteWhere(['id' => $ids]);
                    break;
                case 'soft_delete':
                    $result = $this->repository->updateWhere(['id' => $ids], ['deleted_at' => time()]);
                    break;
                case 'restore':
                    $result = $this->repository->updateWhere(['id' => $ids], ['deleted_at' => 0]);
                    break;
                case 'update':
                    $result = $this->repository->updateWhere(['id' => $ids], $data);
                    break;
                default:
                    throw new BusinessException('不支持的操作类型');
            }

            $this->logInfo('批量操作成功', [
                'action' => $action,
                'ids' => $ids,
                'result' => $result
            ]);

            return $result;
        } catch (Exception $e) {
            $this->logError('批量操作失败', [
                'action' => $action,
                'ids' => $ids,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 获取统计信息
     *
     * 注意：BaseModel 全局 not_deleted scope 已自动过滤已删记录，
     * 故 active = total；inactive 需要 withTrashed 旁路才能查到。
     */
    public function getStats(array $conditions = [])
    {
        try {
            $base = $this->repository->query();

            foreach ($conditions as $field => $value) {
                if (is_array($value)) {
                    $base->whereIn($field, $value);
                } else {
                    $base->where($field, $value);
                }
            }

            // 每项 clone 防止 where 累加 bug
            return [
                'total' => (clone $base)->count(),
                'active' => (clone $base)->count(),  // 已经被 not_deleted scope 过滤
                // inactive 需用 withTrashed 旁路；不在这里直接返回，避免误用
            ];
        } catch (Exception $e) {
            $this->logError('获取统计信息失败', [
                'conditions' => $conditions,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 数据导出
     */
    public function export(array $conditions = [], array $fields = [], $format = 'csv')
    {
        try {
            $this->logInfo('导出数据', [
                'conditions' => $conditions,
                'fields' => $fields,
                'format' => $format
            ]);

            $query = $this->repository->query();
            
            foreach ($conditions as $field => $value) {
                if (is_array($value)) {
                    $query->whereIn($field, $value);
                } else {
                    $query->where($field, $value);
                }
            }

            $data = $query->get();
            
            if (empty($fields)) {
                $fields = array_keys($data->first()->toArray());
            }

            $result = [];
            foreach ($data as $item) {
                $row = [];
                foreach ($fields as $field) {
                    $row[$field] = $item->$field ?? '';
                }
                $result[] = $row;
            }

            $this->logInfo('导出数据成功', [
                'count' => count($result),
                'format' => $format
            ]);

            return $result;
        } catch (Exception $e) {
            $this->logError('导出数据失败', [
                'conditions' => $conditions,
                'fields' => $fields,
                'format' => $format,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
