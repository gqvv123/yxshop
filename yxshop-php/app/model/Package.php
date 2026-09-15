<?php

namespace app\model;

class Package extends BaseModel
{
    protected $table = 'yxshop_packages';

    protected $fillable = [
        'name', 'type', 'items', 'price', 'original_price',
        'start_time', 'end_time', 'status', 'sort', 'app_id',
    ];

    protected $casts = [
        'type' => 'integer',
        'price' => 'float',
        'original_price' => 'float',
        'start_time' => 'integer',
        'end_time' => 'integer',
        'status' => 'integer',
        'sort' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer',
    ];

    const TYPE_FIXED = 1;
    const TYPE_CUSTOM = 2;

    public function isActive()
    {
        $now = time();
        return $this->status && $this->start_time <= $now && $this->end_time >= $now;
    }

    public function discount()
    {
        if ($this->original_price <= 0) {
            return 0;
        }
        return round(($this->original_price - $this->price) / $this->original_price * 100, 1);
    }
}
