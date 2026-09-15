<?php

namespace app\repository;

use app\model\ItemAttribute;

class ItemAttributeRepository extends BaseRepository
{
    protected $model = ItemAttribute::class;

    public function getList($appId = 0, $pageSize = 20, $keyword = '')
    {
        $query = $this->query()->orderBy('created_at', 'desc');

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        if ($keyword !== '') {
            $query->where('name', 'like', "%{$keyword}%");
        }

        return $query->paginate($pageSize);
    }

    /**
     * 获取属性列表（供商品编辑选择，不分页）
     */
    public function getEnabledList($appId = 0)
    {
        $query = $this->query()->orderBy('sort', 'asc')->orderBy('id', 'desc');

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->get();
    }
}
