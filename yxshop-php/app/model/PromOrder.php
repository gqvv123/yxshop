<?php

namespace app\model;

/**
 * 促销订单模型
 */
class PromOrder extends BaseModel
{
    protected $table = 'yxshop_prom_orders';
    protected $primaryKey = 'id';

    protected $fillable = [
        'prom_id',
        'order_id',
        'user_id',
        'prom_type',
        'prom_discount',
        'prom_amount',
        'prom_status',
        'prom_time',
        'app_id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'prom_id' => 'integer',
        'order_id' => 'integer',
        'user_id' => 'integer',
        'prom_type' => 'integer',
        'prom_discount' => 'decimal:2',
        'prom_amount' => 'decimal:2',
        'prom_status' => 'integer',
        'prom_time' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer',
    ];

    // 促销类型常量
    const TYPE_DISCOUNT = 1;       // 折扣促销
    const TYPE_COUPON = 2;         // 优惠券促销
    const TYPE_GIFT = 3;           // 赠品促销
    const TYPE_POINTS = 4;         // 积分促销

    // 促销状态常量
    const STATUS_PENDING = 0;      // 待处理
    const STATUS_SUCCESS = 1;      // 成功
    const STATUS_FAILED = 2;       // 失败
    const STATUS_CANCELLED = 3;    // 已取消

    /**
     * 获取促销类型文本
     */
    public function getPromTypeTextAttribute()
    {
        $types = [
            self::TYPE_DISCOUNT => '折扣促销',
            self::TYPE_COUPON => '优惠券促销',
            self::TYPE_GIFT => '赠品促销',
            self::TYPE_POINTS => '积分促销',
        ];

        return $types[$this->prom_type] ?? '未知';
    }

    /**
     * 获取促销状态文本
     */
    public function getPromStatusTextAttribute()
    {
        $statuses = [
            self::STATUS_PENDING => '待处理',
            self::STATUS_SUCCESS => '成功',
            self::STATUS_FAILED => '失败',
            self::STATUS_CANCELLED => '已取消',
        ];

        return $statuses[$this->prom_status] ?? '未知';
    }

    /**
     * 关联促销活动
     */
    public function promotion()
    {
        return $this->belongsTo(Promotion::class, 'prom_id', 'id');
    }

    /**
     * 关联订单
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * 关联应用
     */
    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'id');
    }
}
