<?php

namespace app\model;

/**
 * 售后申请模型
 *
 * 对应表：yxshop_after_sales（原 yxshop_services，重命名以匹配业务语义）
 * 字段命名以 yxshop_stuc.sql 中 yxshop_services 表为准（含 sub_status/sub_type/refund_id/express_no 等）
 */
class AfterSales extends BaseModel
{
    protected $table = 'yxshop_after_sales';

    protected $fillable = [
        'user_id', 'order_id', 'order_item_id', 'item_id',
        'score', 'content', 'is_picture', 'sort', 'status',
        'sub_status', 'sub_type', 'seller_remark',
        'buyer_name', 'buyer_phone', 'buyer_address',
        'num', 'delivery_status', 'delivery_time',
        'express_no', 'express_id',
        'reasons', 'reasons_text', 'item_status',
        'refund_id', 'refund_status', 'app_id',
        'version', 'deleted_at', 'process_deadline',
    ];

    protected $softDeleteEnabled = true;

    protected $casts = [
        'user_id' => 'integer',
        'order_id' => 'integer',
        'order_item_id' => 'integer',
        'item_id' => 'integer',
        'sub_status' => 'integer',
        'sub_type' => 'integer',
        'status' => 'integer',
        'delivery_status' => 'integer',
        'refund_status' => 'integer',
        'is_picture' => 'integer',
        'num' => 'integer',
        'delivery_time' => 'integer',
        'app_id' => 'integer',
        'version' => 'integer',
        'deleted_at' => 'integer',
        'process_deadline' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer',
    ];

    // 售后类型常量（兼容旧 Service 类）
    const TYPE_REFUND = 1;       // 退款
    const TYPE_RETURN = 2;       // 退货
    const TYPE_EXCHANGE = 3;     // 换货
    const TYPE_REPAIR = 4;       // 维修

    // 状态常量
    const STATUS_PENDING          = 10;  // 待处理
    const STATUS_APPROVED         = 20;  // 已通过（审核通过，等待退货/等待处理）
    const STATUS_RETURN_SHIPPED   = 25;  // 买家已发货（退货中）
    const STATUS_RETURN_RECEIVED  = 30;  // 卖家已收货
    const STATUS_REFUNDING        = 35;  // 退款中
    const STATUS_COMPLETED        = 40;  // 已完成
    const STATUS_REJECTED         = 50;  // 已拒绝
    const STATUS_CANCELLED        = 60;  // 用户已取消

    /** 各类型完整状态流转映射 */
    const STATUS_TRANSITIONS = [
        // 退款（sub_type=1）：待审核 → 通过 → 退款中 → 已完成
        self::STATUS_PENDING         => [self::STATUS_APPROVED, self::STATUS_REJECTED, self::STATUS_CANCELLED],
        self::STATUS_APPROVED        => [self::STATUS_REFUNDING, self::STATUS_REJECTED],  // 通过后直接进入退款流程
        self::STATUS_RETURN_SHIPPED  => [self::STATUS_RETURN_RECEIVED],
        self::STATUS_RETURN_RECEIVED => [self::STATUS_REFUNDING],
        self::STATUS_REFUNDING       => [self::STATUS_COMPLETED],
        self::STATUS_COMPLETED       => [],  // 终态
        self::STATUS_REJECTED        => [],  // 终态
        self::STATUS_CANCELLED       => [],  // 终态
    ];

    /** 操作日志动作常量 */
    const ACTION_APPLY       = 'apply';
    const ACTION_UPDATE      = 'update';
    const ACTION_APPROVE     = 'approve';
    const ACTION_REJECT      = 'reject';
    const ACTION_RETURN_SHIP = 'return_ship';   // 买家发货
    const ACTION_RECEIVE     = 'receive';        // 卖家确认收货
    const ACTION_REFUND      = 'refund';         // 发起退款
    const ACTION_COMPLETE    = 'complete';
    const ACTION_CANCEL      = 'cancel';

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id', 'id');
    }

    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'id');
    }

    /**
     * 售后附件图片列表
     */
    public function images()
    {
        return $this->hasMany(AfterSalesImage::class, 'after_sales_id', 'id');
    }

    /**
     * 售后处理细节 / 沟通记录（按时间正序）
     */
    public function details()
    {
        return $this->hasMany(AfterSalesDetail::class, 'after_sales_id', 'id')
            ->orderBy('created_at', 'asc');
    }

    public function getTypeTextAttribute()
    {
        return [
            self::TYPE_REFUND => '退款',
            self::TYPE_RETURN => '退货',
            self::TYPE_EXCHANGE => '换货',
            self::TYPE_REPAIR => '维修',
        ][$this->sub_type] ?? '未知';
    }

    public function getStatusTextAttribute()
    {
        return [
            self::STATUS_PENDING => '待处理',
            self::STATUS_APPROVED => '已通过',
            self::STATUS_REJECTED => '已拒绝',
            self::STATUS_COMPLETED => '已完成',
        ][$this->status] ?? '未知';
    }

    public function canProcess(): bool
    {
        return (int) $this->status === self::STATUS_PENDING;
    }

    public function logs()
    {
        return $this->hasMany(AfterSalesLog::class, 'after_sales_id', 'id')
            ->orderBy('created_at', 'asc');
    }

    public function refundRecords()
    {
        return $this->hasMany(RefundRecord::class, 'after_sales_id', 'id');
    }

    public function canTransitionTo(int $targetStatus): bool
    {
        $allowed = self::STATUS_TRANSITIONS[$this->status] ?? [];
        return in_array($targetStatus, $allowed, true);
    }

    public function getStatusText(): string
    {
        return [
            self::STATUS_PENDING         => '待处理',
            self::STATUS_APPROVED        => '已通过',
            self::STATUS_RETURN_SHIPPED  => '退货中',
            self::STATUS_RETURN_RECEIVED => '已收货',
            self::STATUS_REFUNDING       => '退款中',
            self::STATUS_COMPLETED       => '已完成',
            self::STATUS_REJECTED        => '已拒绝',
            self::STATUS_CANCELLED       => '已取消',
        ][$this->status] ?? '未知';
    }

    public function getTypeText(): string
    {
        return [
            self::TYPE_REFUND  => '退款',
            self::TYPE_RETURN  => '退货',
            self::TYPE_EXCHANGE=> '换货',
            self::TYPE_REPAIR  => '维修',
        ][$this->sub_type] ?? '未知';
    }

    /** 是否已超时 */
    public function isOverdue(): bool
    {
        if (!$this->process_deadline) {
            return false;
        }
        return $this->status === self::STATUS_PENDING && time() > $this->process_deadline;
    }
}
