<?php

namespace app\model;

/**
 * 订单地址模型（字段对齐 yxshop_order_addresses 表，B 方案：region_id 关联 yxshop_regions）
 */
class OrderAddress extends BaseModel
{
    protected $table = 'yxshop_order_addresses';

    protected $fillable = [
        'order_id', 'user_id', 'name', 'phone',
        'province_id', 'city_id', 'region_id',
        'detail', 'postal_code', 'is_default', 'app_id',
    ];

    protected $appends = ['province_name', 'city_name', 'district_name'];

    protected $casts = [
        'order_id' => 'integer',
        'user_id' => 'integer',
        'province_id' => 'integer',
        'city_id' => 'integer',
        'region_id' => 'integer',
        'is_default' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
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
        return $this->belongsTo(Region::class, 'region_id', 'id');
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
     * 完整地址字符串（按需 with(['province','city','district']) 预加载）
     */
    public function getFullAddressAttribute()
    {
        $province = $this->province->name ?? '';
        $city = $this->city->name ?? '';
        $district = $this->district->name ?? '';
        return $province . $city . $district . $this->detail;
    }
}
