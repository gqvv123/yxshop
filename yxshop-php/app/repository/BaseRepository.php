<?php

namespace app\repository;

use app\model\BaseModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * 基础仓储类
 */
abstract class BaseRepository
{
    protected $model;

    public function __construct(?Model $model = null)
    {
        if ($model !== null) {
            $this->model = $model;
        } elseif (is_string($this->model)) {
            $this->model = new $this->model();
        }
    }

    /**
     * 获取模型实例
     */
    public function getModel(): Model
    {
        return $this->model;
    }

    /**
     * 根据ID查找
     */
    public function find($id): ?Model
    {
        return $this->model->find((string) $id);
    }

    /**
     * 根据ID查找或失败
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail($id): Model
    {
        return $this->model->findOrFail((string) $id);
    }

    /**
     * 根据条件查找
     */
    public function findBy($field, $value): ?Model
    {
        return $this->model->where($field, $value)->first();
    }

    /**
     * 根据条件查找所有
     */
    public function findAllBy($field, $value): Collection
    {
        return $this->model->where($field, $value)->get();
    }

    /**
     * 获取所有记录
     */
    public function all(): Collection
    {
        return $this->model->all();
    }

    /**
     * 通用：按 app 分页（按 created_at desc 或自定义 orderBy）
     * @param int $appId 应用ID
     * @param int $pageSize 每页数量
     * @param string $orderBy 排序字段
     * @param string $direction 排序方向
     */
    public function paginatedByApp(int $appId, int $pageSize = 20, string $orderBy = 'id', string $direction = 'desc'): LengthAwarePaginator
    {
        $query = $this->model->newQuery();
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->orderBy($orderBy, $direction)->paginate($pageSize);
    }

    /**
     * 通用：按 app 列表（不分页）
     * @param int $appId 应用ID
     * @param string $orderBy 排序字段
     * @param string $direction 排序方向
     */
    public function listByApp(int $appId, string $orderBy = 'id', string $direction = 'desc'): Collection
    {
        $query = $this->model->newQuery();
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->orderBy($orderBy, $direction)->get();
    }

    /**
     * 通用：按 app 计数
     */
    public function countByApp(int $appId): int
    {
        $query = $this->model->newQuery();
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->count();
    }

    /**
     * 分页查询
     * @param int $page 页码
     * @param int $limit 每页数量
     * @param array<string,mixed> $conditions 筛选条件
     */
    public function paginate(int $page = 1, int $limit = 15, array $conditions = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        // 应用条件
        foreach ($conditions as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }

        return $query->paginate($limit, ['*'], 'page', $page);
    }

    /**
     * 创建记录（Snowflake ID 由 Model creating 事件自动生成）
     * @param array<string,mixed> $data
     */
    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    /**
     * 批量创建
     * @param array<int,array<string,mixed>> $data
     */
    public function createMany(array $data): bool
    {
        return $this->model->insert($data);
    }

    /**
     * 更新记录
     * @param string|int $id 主键（Snowflake 字符串）
     * @param array<string,mixed> $data
     */
    public function update($id, array $data): Model
    {
        $model = $this->findOrFail($id);
        $model->fill($data);
        $model->save();
        return $model;
    }

    /**
     * 根据条件更新
     * @param array<string,mixed> $conditions
     * @param array<string,mixed> $data
     */
    public function updateWhere(array $conditions, array $data): int
    {
        $query = $this->model->newQuery();

        foreach ($conditions as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }

        return $query->update($data);
    }

    /**
     * 删除记录
     * @param string|int $id 主键（Snowflake 字符串）
     */
    public function delete($id): bool
    {
        $model = $this->findOrFail($id);
        return $model->delete();
    }

    /**
     * 软删除（deleted_at 设为当前时间戳）
     * @param string|int $id 主键（Snowflake 字符串）
     */
    public function softDelete($id): bool
    {
        $model = $this->findOrFail($id);
        return $model->softDelete();
    }

    /**
     * 恢复软删除（deleted_at 重置为 0）
     * @param string|int $id 主键（Snowflake 字符串）
     */
    public function restore($id): bool
    {
        $model = $this->findOrFail($id);
        return $model->restore();
    }

    /**
     * 根据条件删除
     * @param array<string,mixed> $conditions
     */
    public function deleteWhere(array $conditions): int
    {
        $query = $this->model->newQuery();

        foreach ($conditions as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }

        return $query->delete();
    }

    /**
     * 统计数量
     * @param array<string,mixed> $conditions
     */
    public function count(array $conditions = []): int
    {
        $query = $this->model->newQuery();

        foreach ($conditions as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }

        return $query->count();
    }

    /**
     * 检查是否存在
     * @param array<string,mixed> $conditions
     */
    public function exists(array $conditions): bool
    {
        $query = $this->model->newQuery();

        foreach ($conditions as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }

        return $query->exists();
    }

    /**
     * 获取第一条记录
     * @param array<string,mixed> $conditions
     */
    public function first(array $conditions = []): ?Model
    {
        $query = $this->model->newQuery();

        foreach ($conditions as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }

        return $query->first();
    }

    /**
     * 获取最后一条记录
     * @param array<string,mixed> $conditions
     */
    public function last(array $conditions = []): ?Model
    {
        $query = $this->model->newQuery();

        foreach ($conditions as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }

        return $query->orderBy('id', 'desc')->first();
    }

    /**
     * 获取最大值
     * @param array<string,mixed> $conditions
     */
    public function max($field, array $conditions = [])
    {
        $query = $this->model->newQuery();

        foreach ($conditions as $fieldName => $value) {
            if (is_array($value)) {
                $query->whereIn($fieldName, $value);
            } else {
                $query->where($fieldName, $value);
            }
        }

        return $query->max($field);
    }

    /**
     * 获取最小值
     * @param array<string,mixed> $conditions
     */
    public function min($field, array $conditions = [])
    {
        $query = $this->model->newQuery();

        foreach ($conditions as $fieldName => $value) {
            if (is_array($value)) {
                $query->whereIn($fieldName, $value);
            } else {
                $query->where($fieldName, $value);
            }
        }

        return $query->min($field);
    }

    /**
     * 获取平均值
     * @param array<string,mixed> $conditions
     */
    public function avg($field, array $conditions = [])
    {
        $query = $this->model->newQuery();

        foreach ($conditions as $fieldName => $value) {
            if (is_array($value)) {
                $query->whereIn($fieldName, $value);
            } else {
                $query->where($fieldName, $value);
            }
        }

        return $query->avg($field);
    }

    /**
     * 获取总和
     * @param array<string,mixed> $conditions
     */
    public function sum($field, array $conditions = [])
    {
        $query = $this->model->newQuery();

        foreach ($conditions as $fieldName => $value) {
            if (is_array($value)) {
                $query->whereIn($fieldName, $value);
            } else {
                $query->where($fieldName, $value);
            }
        }

        return $query->sum($field);
    }

    /**
     * 获取查询构建器
     */
    public function query()
    {
        return $this->model->newQuery();
    }

    /**
     * 应用范围
     * @param string $scope 作用域名称
     * @param mixed ...$parameters 作用域参数
     */
    public function scope($scope, ...$parameters)
    {
        return $this->model->$scope(...$parameters);
    }

    /**
     * 批量更新（使用参数绑定防止SQL注入）
     * @param array<int,array<string,mixed>> $data 数据行
     * @param string $key 主键字段名
     */
    public function batchUpdate(array $data, $key = 'id'): int
    {
        if (empty($data)) {
            return 0;
        }

        $fields = array_keys($data[0]);
        $updateFields = array_diff($fields, [$key]);
        $bindings = [];
        $cases = [];

        foreach ($updateFields as $field) {
            $whens = [];
            foreach ($data as $row) {
                $whens[] = "WHEN ? THEN ?";
                $bindings[] = $row[$key];
                $bindings[] = $row[$field];
            }
            $cases[] = "{$field} = CASE " . implode(' ', $whens) . " END";
        }

        $idPlaceholders = implode(',', array_fill(0, count($data), '?'));
        foreach ($data as $row) {
            $bindings[] = $row[$key];
        }

        $sql = "UPDATE {$this->model->getTable()} SET " . implode(', ', $cases) . " WHERE {$key} IN ({$idPlaceholders})";

        return $this->model->getConnection()->update($sql, $bindings);
    }

    /**
     * 获取分页数据（自定义分页）
     * @param int $page 页码
     * @param int $limit 每页数量
     * @param array<string,mixed> $conditions 筛选条件
     * @param array<string,string> $orderBy 排序规则 ['field' => 'asc|desc']
     */
    public function getPaginatedData(int $page = 1, int $limit = 15, array $conditions = [], array $orderBy = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        // 应用条件
        foreach ($conditions as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }

        // 应用排序
        foreach ($orderBy as $field => $direction) {
            $query->orderBy($field, $direction);
        }

        $total = $query->count();
        $offset = ($page - 1) * $limit;
        $items = $query->offset($offset)->limit($limit)->get();

        return new LengthAwarePaginator(
            $items,
            $total,
            $limit,
            $page,
            [
                'path' => request()->url(),
                'pageName' => 'page',
            ]
        );
    }

    /**
     * 搜索（转义LIKE通配符防止通配符注入）
     * @param string $keyword 关键词
     * @param array<int,string> $fields 搜索字段
     * @param int $page 页码
     * @param int $limit 每页数量
     */
    public function search(string $keyword, array $fields = [], int $page = 1, int $limit = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        if (!empty($fields)) {
            $escapedKeyword = str_replace(['%', '_'], ['\\%', '\\_'], $keyword);
            $query->where(function ($q) use ($fields, $escapedKeyword) {
                foreach ($fields as $field) {
                    $q->orWhere($field, 'like', "%{$escapedKeyword}%");
                }
            });
        }

        $total = $query->count();
        $offset = ($page - 1) * $limit;
        $items = $query->offset($offset)->limit($limit)->get();

        return new LengthAwarePaginator(
            $items,
            $total,
            $limit,
            $page,
            [
                'path' => request()->url(),
                'pageName' => 'page',
            ]
        );
    }
}
