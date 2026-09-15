<?php

namespace app\repository;

use app\model\AppStorageQuota;

/**
 * 应用存储配额仓储
 */
class AppStorageQuotaRepository extends BaseRepository
{
    protected $model = AppStorageQuota::class;

    /**
     * 按应用ID获取配额记录
     */
    public function getByAppId(int $appId): ?AppStorageQuota
    {
        return $this->query()->where('app_id', $appId)->first();
    }

    /**
     * 更新或创建配额记录
     */
    public function updateOrCreate(int $appId, array $data): AppStorageQuota
    {
        $quota = $this->getByAppId($appId);
        if (!$quota) {
            $quota = $this->create(['app_id' => $appId]);
        }
        foreach ($data as $key => $value) {
            $quota->{$key} = $value;
        }
        $quota->save();
        return $quota;
    }

    /**
     * 增加已用配额
     */
    public function increaseUsedBytes(int $appId, int $bytes): ?AppStorageQuota
    {
        $quota = $this->getByAppId($appId);
        if ($quota) {
            $quota->used_bytes = $quota->used_bytes + $bytes;
            $quota->save();
        }
        return $quota;
    }

    /**
     * 减少已用配额
     */
    public function decreaseUsedBytes(int $appId, int $bytes): ?AppStorageQuota
    {
        $quota = $this->getByAppId($appId);
        if ($quota) {
            $quota->used_bytes = max(0, $quota->used_bytes - $bytes);
            $quota->save();
        }
        return $quota;
    }
}
