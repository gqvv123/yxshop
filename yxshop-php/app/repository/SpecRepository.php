<?php

namespace app\repository;

use app\model\ItemSpec;

class SpecRepository extends BaseRepository
{
    protected $model = ItemSpec::class;

    /**
     * 分页列表（带规格值）
     */
    public function getList($appId = 0, $pageSize = 20, $keyword = '')
    {
        $query = $this->query()->with(['specItems' => function ($q) {
            $q->orderBy('order', 'asc');
        }]);

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        if ($keyword) {
            $query->where('name', 'like', '%' . $keyword . '%');
        }

        return $query->orderBy('sort_order', 'asc')->orderBy('id', 'desc')->paginate($pageSize);
    }

    /**
     * 获取启用的规格列表（供商品编辑选择）
     */
    public function getEnabledList($appId = 0)
    {
        $query = $this->query()
            ->where('status', ItemSpec::STATUS_ENABLED)
            ->with(['specItems' => function ($q) {
                $q->orderBy('order', 'asc');
            }]);

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->orderBy('sort_order', 'asc')->orderBy('id', 'desc')->get();
    }

    /**
     * 详情（带规格值）
     */
    public function findWithItems($id)
    {
        return $this->query()
            ->with(['specItems' => function ($q) {
                $q->orderBy('order', 'asc');
            }])
            ->find($id);
    }
}
