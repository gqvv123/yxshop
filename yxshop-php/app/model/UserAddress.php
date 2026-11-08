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
        'province', 'city', 'district',
        'detail', 'zip_code', 'is_default', 'label', 'app_id'
    ];

    protected $appends = ['province_name', 'city_name', 'district_name', 'full_address'];

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
        return $this->belongsTo(Region::class, 'district_id', 'id');
    }

    /** 省名称：优先用地址自身存的名称，其次回落到地区表 */
    public function resolveRegionName(string $field): string
    {
        $name = (string) ($this->getAttribute($field) ?? '');
        if ($name !== '') {
            return $name;
        }
        $relation = $field . 'Region';
        return (string) ($this->{$relation}->name ?? '');
    }

    /**
     * 省名称
     */
    public function getProvinceNameAttribute()
    {
        return $this->resolveRegionName('province');
    }

    /**
     * 市名称
     */
    public function getCityNameAttribute()
    {
        return $this->resolveRegionName('city');
    }

    /**
     * 区名称
     */
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
