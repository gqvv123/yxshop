<?php

namespace app\repository;

use app\model\UserAddress;

/**
 * 用户地址仓储
 *
 * 字段对齐 yxshop_user_addresses 表（B 方案）：
 *   name / phone / province_id / city_id / district_id / detail / zip_code / label / is_default
 * 软删除由 BaseModel not_deleted 全局 scope 覆盖。
 */
class UserAddressRepository extends BaseRepository
{
    protected $model = UserAddress::class;

    /**
     * 获取用户地址列表（默认地址优先）
     * 预加载地区关联，追加 province_name/city_name/district_name 供小程序展示
     */
    public function getUserAddresses($userId, $appId = 0)
    {
        $query = $this->query()
            ->where('user_id', $userId)
            ->with(['province', 'city', 'district'])
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc');

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        $list = $query->get();

        return $list;
    }

    /**
     * 获取用户默认地址（找不到则返回任一可用地址）
     * 预加载地区关联，追加 province_name/city_name/district_name 供小程序展示
     */
    public function getUserDefaultAddress($userId, $appId = 0)
    {
        $query = $this->query()
            ->where('user_id', $userId)
            ->where('is_default', 1)
            ->with(['province', 'city', 'district']);
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        $default = $query->first();
        if ($default) {
            $default->province_name = $default->province ? $default->province->name : '';
            $default->city_name     = $default->city ? $default->city->name : '';
            $default->district_name = $default->district ? $default->district->name : '';
            return $default;
        }
        // 兜底：找任一未删的
        $fallback = $this->query()->where('user_id', $userId)->with(['province', 'city', 'district']);
        if ($appId > 0) {
            $fallback->where('app_id', $appId);
        }
        $row = $fallback->orderBy('created_at', 'desc')->first();
        if ($row) {
            $row->province_name = $row->province ? $row->province->name : '';
            $row->city_name     = $row->city ? $row->city->name : '';
            $row->district_name = $row->district ? $row->district->name : '';
        }
        return $row;
    }

    /**
     * 按 (id, user_id) 精确查（用于归属校验）
     */
    public function findUserAddress(int $id, int $userId)
    {
        return $this->query()
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();
    }

    /**
     * 取消用户所有默认地址（用于切换默认时）
     */
    public function clearDefaultForUser(int $userId, ?int $excludeId = null): int
    {
        $query = $this->query()
            ->where('user_id', $userId)
            ->where('is_default', 1);
        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }
        return $query->update(['is_default' => 0, 'updated_at' => time()]);
    }

    /**
     * 设置默认地址（事务由 Service 调用方控制）
     */
    public function setDefaultAddress($addressId, $userId)
    {
        $this->clearDefaultForUser($userId, (int) $addressId);
        return $this->query()
            ->where('id', $addressId)
            ->where('user_id', $userId)
            ->update(['is_default' => 1, 'updated_at' => time()]);
    }

    /**
     * 用户地址统计（每项 clone 防累加 bug）
     */
    public function getUserAddressStats($userId, $appId = 0)
    {
        $base = $this->query()->where('user_id', $userId);
        if ($appId > 0) {
            $base->where('app_id', $appId);
        }
        return [
            'total'   => (clone $base)->count(),
            'default' => (clone $base)->where('is_default', 1)->count(),
        ];
    }

    /**
     * 搜索（字段对齐 B 方案：name/phone/detail）
     */
    public function searchUserAddresses($userId, $keyword, $appId = 0)
    {
        $escaped = str_replace(['%', '_'], ['\\%', '\\_'], (string) $keyword);
        $query = $this->query()
            ->where('user_id', $userId)
            ->where(function ($q) use ($escaped) {
                $q->where('name', 'like', "%{$escaped}%")
                  ->orWhere('phone', 'like', "%{$escaped}%")
                  ->orWhere('detail', 'like', "%{$escaped}%");
            })
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc');

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->get();
    }

    /**
     * 后台分页列表
     */
    public function getPaginatedByApp(int $appId, int $pageSize = 20)
    {
        return $this->query()
            ->where('app_id', $appId)
            ->with('user')
            ->orderBy('id', 'desc')
            ->paginate($pageSize);
    }
}
