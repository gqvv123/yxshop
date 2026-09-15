<?php

namespace app\model;

class UserContract extends BaseModel
{
    protected $table = 'yxshop_user_contracts';

    protected $usesSnowflake = false;

    public $incrementing = true;

    protected $keyType = 'int';

    protected $softDeleteEnabled = false;

    const TYPE_PURCHASE     = 1; // 采购合同
    const TYPE_SALE         = 2; // 销售合同
    const TYPE_SERVICE      = 3; // 服务合同
    const TYPE_AGENCY       = 4; // 代理合同
    const TYPE_OTHER        = 9; // 其他

    const STATUS_DRAFT      = 0; // 草稿
    const STATUS_PENDING    = 1; // 待签署
    const STATUS_SIGNED     = 2; // 已签署
    const STATUS_EXPIRED    = 3; // 已过期
    const STATUS_TERMINATED = 4; // 已终止

    protected $fillable = [
        'user_id', 'contract_no', 'contract_name', 'contract_type',
        'party_a', 'party_b', 'amount', 'start_date', 'end_date',
        'file_url', 'status', 'remark', 'app_id',
        'signed_at', 'created_at', 'updated_at'
    ];

    protected $casts = [
        'user_id'       => 'integer',
        'contract_type' => 'integer',
        'amount'        => 'decimal:2',
        'status'        => 'integer',
        'app_id'        => 'integer',
        'start_date'    => 'integer',
        'end_date'      => 'integer',
        'signed_at'     => 'integer',
        'created_at'    => 'integer',
        'updated_at'    => 'integer'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'id');
    }
}
