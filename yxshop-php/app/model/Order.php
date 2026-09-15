<?php

namespace app\model;

/**
 * 订单模型
 */
class Order extends BaseModel
{
    protected $table = 'yxshop_orders';

    protected $with = [];

    protected $fillable = [
        'order_no', 'total_price', 'coupon_id', 'coupon_price', 'pay_price', 'update_price',
        'buyer_note', 'pay_status', 'pay_time', 'express_price', 'delivery_status',
        'receipt_status', 'audit_status', 'audit_remark', 'audit_time',
        'status', 'transaction_id', 'is_comment', 'user_id', 'app_id',
        'promotion_type', 'rebate', 'item_id', 'end_time',
        'give_integral', 'express_no', 'discount_id', 'discount_price',
        'receiver_name', 'receiver_phone', 'seller_note', 'delivery_time', 'is_integral',
        'receipt_time', 'brand_id',
        'source', 'auto_confirm_time', 'deleted_at'
    ];

    protected $casts = [
        'total_price' => 'decimal:2',
        'coupon_price' => 'decimal:2',
        'pay_price' => 'decimal:2',
        'update_price' => 'decimal:2',
        'express_price' => 'decimal:2',
        'discount_price' => 'decimal:2',
        'give_integral' => 'decimal:2',
        'rebate' => 'decimal:2',
        'pay_status' => 'integer',
        'delivery_status' => 'integer',
        'receipt_status' => 'integer',
        'audit_status' => 'integer',
        'audit_time' => 'integer',
        'status' => 'integer',
        'is_comment' => 'integer',
        'is_integral' => 'integer',

        'promotion_type' => 'integer',

        'pay_time' => 'integer',
        'delivery_time' => 'integer',
        'receipt_time' => 'integer',
        'end_time' => 'integer',

        'auto_confirm_time' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer'
    ];

    // 订单状态常量
    const ORDER_STATUS_PENDING = 10;      // 进行中
    const ORDER_STATUS_CANCEL = 20;       // 已取消
    const ORDER_STATUS_COMPLETE = 30;    // 已完成

    // 支付状态常量
    const PAY_STATUS_UNPAID = 10;         // 未付款
    const PAY_STATUS_PAID = 20;           // 已付款

    // 发货状态常量
    const DELIVERY_STATUS_UNSHIPPED = 10; // 未发货
    const DELIVERY_STATUS_SHIPPED = 20;   // 已发货

    // 收货状态常量
    const RECEIPT_STATUS_UNRECEIVED = 10; // 未收货
    const RECEIPT_STATUS_RECEIVED = 20;   // 已收货

    // 退款状态常量
    const REFUND_STATUS_NONE = 10;        // 无退款
    const REFUND_STATUS_REFUNDING = 20;   // 退款中
    const REFUND_STATUS_REFUNDED = 30;    // 已退款

    // 订单来源常量
    const ORDER_SOURCE_MINIPROGRAM = 10;  // 小程序
    const ORDER_SOURCE_H5 = 20;           // H5
    const ORDER_SOURCE_APP = 30;          // APP

    /**
     * 订单用户
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * 订单商品
     */
    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id', 'id');
    }

    public function goods()
    {
        return $this->items();
    }

    public function orderDetails()
    {
        return $this->items();
    }

    public function orderItems()
    {
        return $this->items();
    }

    public function orderGoods()
    {
        return $this->items();
    }

    /**
     * 订单地址
     */
    public function address()
    {
        return $this->hasOne(OrderAddress::class, 'order_id', 'id');
    }

    /**
     * 订单物流
     */
    public function delivery()
    {
        return $this->hasMany(OrderDelivery::class, 'order_id', 'id');
    }

    /**
     * 订单优惠券
     */
    public function coupon()
    {
        return $this->belongsTo(Coupon::class, 'coupon_id', 'id');
    }

    /**
     * 订单优惠活动
     */
    public function promotion()
    {
        return $this->belongsTo(Promotion::class, 'promotion_id', 'id');
    }

    /**
     * 订单评价
     */
    public function comments()
    {
        return $this->hasMany(Comment::class, 'order_id', 'id');
    }

    /**
     * 订单售后
     */
    public function services()
    {
        return $this->hasMany(Service::class, 'order_id', 'id');
    }

    /**
     * 订单分销记录
     */
    public function agentOrders()
    {
        return $this->hasMany(OrderAgent::class, 'order_id', 'id');
    }

    /**
     * 订单变化日志
     */
    public function logs()
    {
        return $this->hasMany(OrderLog::class, 'order_id', 'id');
    }

    /**
     * 获取订单状态文本
     */
    public function getOrderStatusTextAttribute()
    {
        $statusMap = [
            self::ORDER_STATUS_PENDING => '进行中',
            self::ORDER_STATUS_CANCEL => '已取消',
            self::ORDER_STATUS_COMPLETE => '已完成'
        ];
        return $statusMap[$this->status] ?? '未知';
    }

    /**
     * 获取支付状态文本
     */
    public function getPayStatusTextAttribute()
    {
        $statusMap = [
            self::PAY_STATUS_UNPAID => '未付款',
            self::PAY_STATUS_PAID => '已付款'
        ];
        return $statusMap[$this->pay_status] ?? '未知';
    }

    /**
     * 获取发货状态文本
     */
    public function getDeliveryStatusTextAttribute()
    {
        $statusMap = [
            self::DELIVERY_STATUS_UNSHIPPED => '未发货',
            self::DELIVERY_STATUS_SHIPPED => '已发货'
        ];
        return $statusMap[$this->delivery_status] ?? '未知';
    }

    /**
     * 获取收货状态文本
     */
    public function getReceiptStatusTextAttribute()
    {
        $statusMap = [
            self::RECEIPT_STATUS_UNRECEIVED => '未收货',
            self::RECEIPT_STATUS_RECEIVED => '已收货'
        ];
        return $statusMap[$this->receipt_status] ?? '未知';
    }

    /**
     * 检查是否可以支付
     */
    public function canPay()
    {
        return $this->status == self::ORDER_STATUS_PENDING && 
               $this->pay_status == self::PAY_STATUS_UNPAID;
    }

    /**
     * 检查是否可以发货
     */
    public function canShip()
    {
        return $this->status == self::ORDER_STATUS_PENDING && 
               $this->pay_status == self::PAY_STATUS_PAID && 
               $this->delivery_status == self::DELIVERY_STATUS_UNSHIPPED;
    }

    /**
     * 检查是否可以收货
     */
    public function canReceive()
    {
        return $this->status == self::ORDER_STATUS_PENDING && 
               $this->delivery_status == self::DELIVERY_STATUS_SHIPPED && 
               $this->receipt_status == self::RECEIPT_STATUS_UNRECEIVED;
    }

    /**
     * 检查是否可以取消
     */
    public function canCancel()
    {
        return $this->status == self::ORDER_STATUS_PENDING && 
               $this->pay_status == self::PAY_STATUS_UNPAID;
    }

    /**
     * 检查是否可以申请售后
     */
    public function canApplyService()
    {
        return $this->status == self::ORDER_STATUS_COMPLETE && 
               $this->pay_status == self::PAY_STATUS_PAID;
    }

    /**
     * 检查是否可以评价
     */
    public function canComment()
    {
        return $this->status == self::ORDER_STATUS_COMPLETE && 
               $this->pay_status == self::PAY_STATUS_PAID && 
               $this->is_comment == 0;
    }

    /**
     * 生成订单号
     */
    public static function generateOrderNo()
    {
        $prefix = date('YmdHis');
        $random = bin2hex(random_bytes(4));
        return $prefix . $random;
    }

    /**
     * 计算订单总金额
     */
    public function calculateTotal()
    {
        $total = 0;
        foreach ($this->goods as $good) {
            $total += $good->total_price;
        }
        return $total;
    }

    /**
     * 计算实际支付金额
     */
    public function calculatePayPrice()
    {
        $total = $this->total_price + $this->express_price;
        $total -= $this->coupon_price + $this->discount_price;
        $total += $this->update_price;
        return max(0, $total);
    }

    /**
     * 记录订单变化日志
     */
    public function logChange($changeType, $changeField, $oldValue, $newValue, $reason = '', $operatorType = 20, $operatorId = 0, $operatorName = '')
    {
        OrderLog::create([
            'order_id' => $this->id,
            'order_no' => $this->order_no,
            'change_type' => $changeType,
            'change_field' => $changeField,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'change_reason' => $reason,
            'operator_type' => $operatorType,
            'operator_id' => $operatorId,
            'operator_name' => $operatorName,
            'app_id' => $this->app_id,
            'created_at' => time()
        ]);
    }

    /**
     * 更新订单状态
     */
    public function updateStatus($status, $reason = '', $operatorType = 20, $operatorId = 0, $operatorName = '')
    {
        $oldStatus = $this->status;
        $this->status = $status;
        $this->save();

        $this->logChange(80, 'status', $oldStatus, $status, $reason, $operatorType, $operatorId, $operatorName);
    }

    /**
     * 更新支付状态
     */
    public function updatePayStatus($status, $transactionId = '', $reason = '', $operatorType = 20, $operatorId = 0, $operatorName = '')
    {
        $oldStatus = $this->pay_status;
        $this->pay_status = $status;
        if ($status == self::PAY_STATUS_PAID) {
            $this->pay_time = time();
            $this->transaction_id = $transactionId;
        }
        $this->save();

        $this->logChange(20, 'pay_status', $oldStatus, $status, $reason, $operatorType, $operatorId, $operatorName);
    }

    /**
     * 更新发货状态
     */
    public function updateDeliveryStatus($status, $expressNo = '', $expressId = 0, $reason = '', $operatorType = 20, $operatorId = 0, $operatorName = '')
    {
        $oldStatus = $this->delivery_status;
        $this->delivery_status = $status;
        if ($status == self::DELIVERY_STATUS_SHIPPED) {
            $this->delivery_time = time();
            $this->express_no = $expressNo;
        }
        $this->save();

        $this->logChange(30, 'delivery_status', $oldStatus, $status, $reason, $operatorType, $operatorId, $operatorName);
    }

    /**
     * 更新收货状态
     */
    public function updateReceiptStatus($status, $reason = '', $operatorType = 20, $operatorId = 0, $operatorName = '')
    {
        $oldStatus = $this->receipt_status;
        $this->receipt_status = $status;
        if ($status == self::RECEIPT_STATUS_RECEIVED) {
            $this->receipt_time = time();
            $this->status = self::ORDER_STATUS_COMPLETE;
        }
        $this->save();

        $this->logChange(40, 'receipt_status', $oldStatus, $status, $reason, $operatorType, $operatorId, $operatorName);
    }

    /**
     * 获取配送状态选项
     * @return array
     */
    public static function delevaryStatus()
    {
        return [
            'pending' => self::ORDER_STATUS_PENDING,
            'confirmed' => self::ORDER_STATUS_PENDING + 1,
            'processing' => self::ORDER_STATUS_PENDING + 2,
            'shipped' => self::DELIVERY_STATUS_SHIPPED,
            'delivered' => self::ORDER_STATUS_COMPLETE,
            'cancel' => self::ORDER_STATUS_CANCEL,
            'return' => self::ORDER_STATUS_CANCEL + 1
        ];
    }
}
