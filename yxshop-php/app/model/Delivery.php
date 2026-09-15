<?php

namespace app\model;

class Delivery extends BaseModel
{
    protected $table = 'yxshop_deliveries';

    protected $fillable = [
        'name', 'method', 'status', 'sort', 'app_id'
    ];

    protected $casts = [
        'method' => 'integer',
        'status' => 'integer',
        'sort' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer'
    ];

    const METHOD_BY_QUANTITY = 10;
    const METHOD_BY_WEIGHT = 20;

    public function rules()
    {
        return $this->hasMany(DeliveryRule::class, 'delivery_id', 'id');
    }

    public function items()
    {
        return $this->hasMany(Item::class, 'shipping_template_id', 'id');
    }

    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'id');
    }
}
