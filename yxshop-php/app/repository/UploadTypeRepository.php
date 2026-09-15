<?php

namespace app\repository;

use app\model\UploadType;

/**
 * 上传类型仓储类
 */
class UploadTypeRepository extends BaseRepository
{
    protected $model = UploadType::class;

    /**
     * 获取类型列表
     */
    public function getTypes($appId = 0)
    {
        $query = $this->query()
            ->where('is_show', 1)
            ->orderBy('sort', 'asc');

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->get();
    }

    /**
     * 根据扩展名查找类型
     */
    public function findByExt($ext, $appId = 0)
    {
        $query = $this->query()
            ->where('is_show', 1)
            ->whereJsonContains('type_ext', $ext);

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->first();
    }

    /**
     * 根据MIME类型查找类型
     */
    public function findByMime($mime, $appId = 0)
    {
        $query = $this->query()
            ->where('is_show', 1)
            ->whereJsonContains('type_mime', $mime);

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->first();
    }

    /**
     * 获取类型统计
     */
    public function getTypeStats($appId = 0)
    {
        $query = $this->query();

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return [
            'total' => $query->count(),
            'show' => $query->where('is_show', 1)->count(),
            'hide' => $query->where('is_show', 0)->count(),
        ];
    }
}
