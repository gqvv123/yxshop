<?php

namespace app\model;

/**
 * AI 算力包模型
 *
 * 商家购买的 AI 算力额度包，按调用次数计费
 */
class AiCreditPackage extends BaseModel
{
    protected $table = 'yxshop_ai_credit_packages';

    protected $fillable = [
        'app_id', 'admin_id',
        'package_name', 'total_calls', 'used_calls', 'remaining_calls',
        'price', 'payment_status', 'payment_id',
        'expire_at', 'status', 'remark',
        'created_at', 'updated_at',
    ];

    protected $casts = [
        'app_id'          => 'integer',
        'admin_id'        => 'integer',
        'total_calls'     => 'integer',
        'used_calls'      => 'integer',
        'remaining_calls' => 'integer',
        'price'           => 'decimal:2',
        'payment_status'  => 'integer',
        'payment_id'      => 'integer',
        'expire_at'       => 'integer',
        'status'          => 'integer',
        'created_at'      => 'integer',
        'updated_at'      => 'integer',
    ];

    // 支付状态常量
    const PAYMENT_PENDING   = 0;  // 待支付
    const PAYMENT_PAID      = 1;  // 已支付
    const PAYMENT_CANCELLED = 2;  // 已取消

    // 算力包状态常量
    const STATUS_DISABLED  = 0;  // 已禁用
    const STATUS_ACTIVE    = 1;  // 有效
    const STATUS_EXHAUSTED = 2;  // 已用完
    const STATUS_EXPIRED   = 3;  // 已过期

    /**
     * 关联应用
     */
    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'id');
    }

    /**
     * 关联管理员
     */
    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id', 'id');
    }

    /**
     * 检查算力包是否可用
     */
    public function isUsable(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }
        if ($this->payment_status !== self::PAYMENT_PAID) {
            return false;
        }
        if ($this->remaining_calls <= 0) {
            return false;
        }
        if ($this->expire_at && $this->expire_at < time()) {
            return false;
        }
        return true;
    }

    /**
     * 获取支付状态文本
     */
    public function getPaymentStatusTextAttribute(): string
    {
        $map = [
            self::PAYMENT_PENDING   => '待支付',
            self::PAYMENT_PAID      => '已支付',
            self::PAYMENT_CANCELLED => '已取消',
        ];
        return $map[$this->payment_status] ?? '未知';
    }

    /**
     * 获取状态文本
     */
    public function getStatusTextAttribute(): string
    {
        $map = [
            self::STATUS_DISABLED  => '已禁用',
            self::STATUS_ACTIVE    => '有效',
            self::STATUS_EXHAUSTED => '已用完',
            self::STATUS_EXPIRED   => '已过期',
        ];
        return $map[$this->status] ?? '未知';
    }
}
