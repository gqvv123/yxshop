<?php

namespace app\model;

class Promotion extends BaseModel
{
    protected $table = 'yxshop_promotions';

    protected $fillable = [
        'title','type','rule','start_time','end_time','status','app_id','created_at','updated_at'
    ];

    protected $casts = [
        'rule' => 'array',
        'type' => 'integer',
        'status' => 'integer',
        'app_id' => 'integer',
        'start_time' => 'integer',
        'end_time' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer'
    ];

    public function app()
    {
        return $this->belongsTo(App::class, 'app_id');
    }

    public function promItems()
    {
        return $this->hasMany(PromItem::class, 'prom_id');
    }

    public function promGoods()
    {
        return $this->promItems();
    }

    public function promOrders()
    {
        return $this->hasMany(PromOrder::class, 'prom_id');
    }

    public function items()
    {
        return $this->belongsToMany(Item::class, 'yxshop_promotion_items', 'promotion_id', 'item_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'promotion_id');
    }

    public function isActive()
    {
        $now = time();
        return $this->status && $this->start_time <= $now && $this->end_time >= $now;
    }
}


