<?php

namespace app\model;

class PromItem extends BaseModel
{
    protected $table = 'yxshop_prom_items';
    protected $primaryKey = 'id';

    protected $fillable = [
        'name', 'type', 'expression', 'description',
        'start_time', 'end_time', 'is_close', 'group',
        'prom_img', 'app_id', 'item_id',
    ];

    protected $casts = [
        'type' => 'integer',
        'start_time' => 'integer',
        'end_time' => 'integer',
        'is_close' => 'integer',
        'app_id' => 'integer',
        'item_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer',
    ];

    const STATUS_DISABLED = 0;
    const STATUS_ENABLED = 1;
    const STATUS_SOLD_OUT = 2;

    public function getPromStatusTextAttribute()
    {
        $statuses = [
            self::STATUS_DISABLED => '禁用',
            self::STATUS_ENABLED => '启用',
            self::STATUS_SOLD_OUT => '售罄',
        ];

        return $statuses[$this->prom_status] ?? '未知';
    }

    public function promotion()
    {
        return $this->belongsTo(Promotion::class, 'prom_id', 'id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }

    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'id');
    }
}