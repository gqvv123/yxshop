<?php

namespace app\repository;

use app\model\Express;

/**
 * 快递公司仓储类
 */
class ExpressRepository extends BaseRepository
{
    protected $model = Express::class;

    /**
     * 获取快递公司列表
     */
    public function getExpresses($appId = 0)
    {
        $query = $this->query()

            ->orderBy('sort', 'asc');

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->get();
    }

    /**
     * 根据代码查找快递公司
     */
    public function findByCode($code, $appId = 0)
    {
        $query = $this->query()->where('code', $code);

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->first();
    }

    /**
     * 搜索快递公司
     */
    public function searchExpresses($keyword, $appId = 0)
    {
        $query = $this->query()

            ->where('name', 'like', '%' . $keyword . '%')
            ->orderBy('sort', 'asc');

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->get();
    }

    /**
     * 获取快递公司统计
     */
    public function getExpressStats($appId = 0)
    {
        $query = $this->query();

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return [
            'total' => $query->count(),
            'show' => $query->where('status', 1)->count(),
            'hide' => $query->where('status', 0)->count(),
        ];
    }
}
