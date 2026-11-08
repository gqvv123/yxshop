<?php

namespace app\model;

/**
 * 数据模型：发票抬头（用户维度）。
 *
 * @package app\model
 */
class InvoiceHeader extends BaseModel
{
    protected $table = 'yxshop_invoice_headers';

    protected $fillable = [
        'user_id', 'header_name', 'invoice_type', 'tax_no', 'reg_address',
        'reg_phone', 'bank_name', 'bank_account', 'is_default', 'app_id',
    ];

    protected $casts = [
        'user_id'      => 'integer',
        'invoice_type' => 'integer',
        'is_default'   => 'integer',
        'app_id'       => 'integer',
        'created_at'   => 'integer',
        'updated_at'   => 'integer',
        'deleted_at'   => 'integer',
    ];

    protected $softDeleteEnabled = true;

    const TYPE_PERSONAL = 1;   // 个人
    const TYPE_COMPANY  = 2;   // 企业

    /** 发票类型文案 */
    public function getTypeTextAttribute(): string
    {
        return [
            self::TYPE_PERSONAL => '个人',
            self::TYPE_COMPANY  => '企业',
        ][$this->invoice_type] ?? '个人';
    }
}
