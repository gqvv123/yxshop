<?php

namespace app\model;

/**
 * 订单物流模型
 */
class OrderDelivery extends BaseModel
{
    protected $table = 'yxshop_order_deliveries';

    protected $fillable = [
        'order_id', 'order_no', 'express_id', 'express_name', 'express_no', 'express_code',
        'company', 'type', 'delivery_time', 'receive_time', 'receipt_time', 'status', 'remark', 'app_id',
        'created_at', 'updated_at'
    ];

    protected $casts = [
        'order_id' => 'integer',
        'express_id' => 'integer',
        'delivery_time' => 'integer',
        'receive_time' => 'integer',
        'status' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer'
    ];

    // 状态常量
    const STATUS_PENDING = 10;   // 待发货
    const STATUS_SHIPPED = 20;   // 已发货
    const STATUS_IN_TRANSIT = 30; // 运输中
    const STATUS_DELIVERED = 40;  // 已送达
    const STATUS_RECEIVED = 50;   // 已签收

    /**
     * 订单关联
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    /**
     * 快递公司关联
     */
    public function express()
    {
        return $this->belongsTo(Express::class, 'express_id', 'id');
    }

    /**
     * 应用关联
     */
    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'id');
    }

    /**
     * 获取状态文本
     */
    public function getStatusTextAttribute()
    {
        $statusMap = [
            self::STATUS_PENDING => '待发货',
            self::STATUS_SHIPPED => '已发货',
            self::STATUS_IN_TRANSIT => '运输中',
            self::STATUS_DELIVERED => '已送达',
            self::STATUS_RECEIVED => '已签收',
        ];
        return $statusMap[$this->status] ?? '未知';
    }

    /**
     * 更新状态
     */
    public function updateStatus($status, $remark = '')
    {
        $this->status = $status;
        if ($remark) {
            $this->remark = $remark;
        }
        
        if ($status == self::STATUS_SHIPPED) {
            $this->delivery_time = time();
        } elseif ($status == self::STATUS_RECEIVED) {
            $this->receive_time = time();
        }
        
        $this->save();
    }
}
