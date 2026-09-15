<?php

namespace app\model;

/**
 * 地区模型
 */
class Region extends BaseModel
{
    protected $table = 'yxshop_regions';

    protected $fillable = [
        'pid', 'shortname', 'name', 'merger_name', 'level', 'pinyin', 'code', 'zip_code', 'first', 'lng', 'lat'
    ];

    protected $casts = [
        'pid' => 'integer',
        'level' => 'integer',
    ];

    /**
     * 父级地区
     */
    public function parent()
    {
        return $this->belongsTo(Region::class, 'pid', 'id');
    }

    public function children()
    {
        return $this->hasMany(Region::class, 'pid', 'id');
    }

    /**
     * 获取完整地址
     */
    public function getFullAddress()
    {
        $address = [$this->name];
        $parent = $this->parent;
        
        while ($parent) {
            array_unshift($address, $parent->name);
            $parent = $parent->parent;
        }
        
        return implode('', $address);
    }

    public function getParentIdAttribute()
    {
        return $this->attributes['pid'] ?? 0;
    }
}
