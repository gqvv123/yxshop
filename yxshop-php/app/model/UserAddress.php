<?php

namespace app\model;

/**
 * 用户地址模型
 */
class UserAddress extends BaseModel
{
    protected $table = 'yxshop_user_addresses';

    protected $fillable = [
        'user_id', 'name', 'phone', 'province_id', 'city_id', 'district_id',
        'detail', 'zip_code', 'is_default', 'label', 'app_id'
    ];

    protected $appends = ['province_name', 'city_name', 'district_name'];

    protected $casts = [
        'is_default' => 'integer',
        'user_id' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer'
    ];

    /**
     * 用户关联
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function province()
    {
        return $this->belongsTo(Region::class, 'province_id', 'id');
    }

    public function city()
    {
        return $this->belongsTo(Region::class, 'city_id', 'id');
    }

    public function district()
    {
        return $this->belongsTo(Region::class, 'district_id', 'id');
    }

    /**
     * 省名称
     */
    public function getProvinceNameAttribute()
    {
        return $this->province->name ?? '';
    }

    /**
     * 市名称
     */
    public function getCityNameAttribute()
    {
        return $this->city->name ?? '';
    }

    /**
     * 区名称
     */
    public function getDistrictNameAttribute()
    {
        return $this->district->name ?? '';
    }

    /**
     * 完整地址字符串（需 with(['province','city','district']) 预加载，避免 N+1）
     */
    public function getFullAddressAttribute()
    {
        $province = $this->province->name ?? '';
        $city = $this->city->name ?? '';
        $district = $this->district->name ?? '';
        return $province . $city . $district . $this->detail;
    }
}
