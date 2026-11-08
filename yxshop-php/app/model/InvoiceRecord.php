<?php

namespace app\model;

/**
 * 数据模型：开票记录。
 *
 * @package app\model
 */
class InvoiceRecord extends BaseModel
{
    protected $table = 'yxshop_invoice_records';

    protected $fillable = [
        'order_id', 'invoice_header_id', 'invoice_content', 'invoice_amount',
        'invoice_no', 'status', 'fail_reason', 'invoiced_at', 'app_id',
    ];

    protected $casts = [
        'order_id'          => 'integer',
        'invoice_header_id' => 'integer',
        'invoice_amount'    => 'decimal:2',
        'status'            => 'integer',
        'invoiced_at'       => 'integer',
        'app_id'            => 'integer',
        'created_at'        => 'integer',
        'updated_at'        => 'integer',
    ];

    const STATUS_PENDING = 1;   // 待开票
    const STATUS_ISSUED  = 2;   // 已开票
    const STATUS_FAILED  = 3;   // 开票失败
    const STATUS_REVOKED = 4;   // 已撤销

    /** 状态文案 */
    public function getStatusTextAttribute(): string
    {
        return [
            self::STATUS_PENDING => '待开票',
            self::STATUS_ISSUED  => '已开票',
            self::STATUS_FAILED  => '开票失败',
            self::STATUS_REVOKED => '已撤销',
        ][$this->status] ?? '未知';
    }

    /** 关联抬头 */
    public function header()
    {
        return $this->belongsTo(InvoiceHeader::class, 'invoice_header_id', 'id');
    }
}
