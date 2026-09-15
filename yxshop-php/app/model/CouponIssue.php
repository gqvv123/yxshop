<?php

namespace app\model;

class CouponIssue extends BaseModel
{
    protected $table = 'yxshop_coupon_issues';

    protected $usesSnowflake = false;

    public $incrementing = true;

    protected $keyType = 'int';

    protected $softDeleteEnabled = false;

    const TYPE_MANUAL = 'manual';
    const TYPE_AUTO   = 'auto';
    const TYPE_CODE   = 'code';
    const TYPE_BATCH  = 'batch';

    protected $fillable = [
        'coupon_id', 'issue_type', 'quantity', 'used_quantity',
        'operator_id', 'operator_name', 'remark', 'app_id',
    ];

    protected $casts = [
        'coupon_id' => 'integer',
        'quantity' => 'integer',
        'used_quantity' => 'integer',
        'operator_id' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
    ];

    public function coupon()
    {
        return $this->belongsTo(Coupon::class, 'coupon_id', 'id');
    }
}
