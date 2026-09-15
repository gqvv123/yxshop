<?php

namespace app\model;

class AfterSalesLog extends BaseModel
{
    protected $table = 'yxshop_after_sales_logs';

    protected $usesSnowflake = false;

    public $incrementing = true;

    protected $keyType = 'int';

    protected $softDeleteEnabled = false;

    protected $fillable = [
        'after_sales_id', 'action', 'before_status', 'after_status',
        'actor_id', 'actor_name', 'remark', 'extra', 'created_at'
    ];

    protected $casts = [
        'after_sales_id' => 'integer',
        'before_status' => 'integer',
        'after_status' => 'integer',
        'actor_id' => 'integer',
        'created_at' => 'integer'
    ];

    public function afterSales()
    {
        return $this->belongsTo(AfterSales::class, 'after_sales_id', 'id');
    }

    public function actor()
    {
        return $this->belongsTo(Admin::class, 'actor_id', 'id');
    }
}
