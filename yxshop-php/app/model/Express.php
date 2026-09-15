<?php

namespace app\model;

class Express extends BaseModel
{
    protected $table = 'yxshop_expresses';

    protected $fillable = [
        'name', 'code', 'sort', 'status', 'app_id'
    ];

    protected $casts = [
        'sort' => 'integer',
        'status' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer'
    ];

    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'id');
    }

    public function orderDeliveries()
    {
        return $this->hasMany(OrderDelivery::class, 'express_id', 'id');
    }
}
