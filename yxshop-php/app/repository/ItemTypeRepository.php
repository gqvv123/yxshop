<?php

namespace app\repository;

use app\model\ItemType;

/**
 * 商品类型Repository
 */
class ItemTypeRepository extends BaseRepository
{
    protected $model = ItemType::class;

    /**
     * 获取类型列表
     */
    public function getTypes($appId = 0)
    {
        $query = $this->query();
        
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        
        return $query->orderBy('sort', 'asc')
                    ->orderBy('type_id', 'asc')
                    ->get();
    }

    /**
     * 获取可用类型
     */
    public function getAvailableTypes($appId = 0)
    {
        $query = $this->query();
        
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        
        return $query->where('status', 1)
                    ->orderBy('sort', 'asc')
                    ->get();
    }

    /**
     * 获取类型统计
     */
    public function getTypeStats($appId = 0)
    {
        $base = $this->model->newQuery();

        if ($appId > 0) {
            $base->where('app_id', $appId);
        }

        return [
            'total'    => (clone $base)->count(),
            'active'   => (clone $base)->where('status', 1)->count(),
            'inactive' => (clone $base)->where('status', 0)->count(),
        ];
    }

    /**
     * 分页列表（支持名称关键字搜索）
     */
    public function paginatedList(int $appId = 0, int $pageSize = 20, string $keyword = '')
    {
        $query = $this->model->newQuery();
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        if ($keyword !== '') {
            $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $keyword);
            $query->where('name', 'like', '%' . $escaped . '%');
        }
        return $query->orderBy('created_at', 'desc')->paginate($pageSize);
    }
}
