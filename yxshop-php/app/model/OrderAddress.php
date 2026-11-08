<?php

namespace app\model;

/**
 * 订单地址模型（字段对齐 yxshop_order_addresses 表）
 *
 * 下单时会把用户地址快照到本表：优先保存省市县名称（region 基础数据可能缺失，
 * 名称随快照一起存才不会出现「地址只剩门牌号」），名称缺失时再回落到 yxshop_regions。
 */
class OrderAddress extends BaseModel
{
    protected $table = 'yxshop_order_addresses';

    protected $fillable = [
        'order_id', 'user_id', 'name', 'phone',
        'province_id', 'city_id', 'region_id',
        'province', 'city', 'district',
        'detail', 'postal_code', 'is_default', 'app_id',
    ];

    protected $appends = ['province_name', 'city_name', 'district_name', 'full_address'];

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

    public function provinceRegion()
    {
        return $this->belongsTo(Region::class, 'province_id', 'id');
    }

    public function cityRegion()
    {
        return $this->belongsTo(Region::class, 'city_id', 'id');
    }

    public function districtRegion()
    {
        return $this->belongsTo(Region::class, 'region_id', 'id');
    }

    /**
     * 名称优先取快照，其次回落地区表
     */
    public function resolveRegionName(string $field): string
    {
        $name = (string) ($this->getAttribute($field) ?? '');
        if ($name !== '') {
            return $name;
        }
        $relation = $field . 'Region';
        return (string) ($this->{$relation}->name ?? '');
    }

    public function getProvinceNameAttribute()
    {
        return $this->resolveRegionName('province');
    }

    public function getCityNameAttribute()
    {
        return $this->resolveRegionName('city');
    }

    public function getDistrictNameAttribute()
    {
        return $this->resolveRegionName('district');
    }

    /**
     * 完整地址字符串
     */
    public function getFullAddressAttribute()
    {
        return $this->getProvinceNameAttribute()
            . $this->getCityNameAttribute()
            . $this->getDistrictNameAttribute()
            . $this->detail;
    }
}
