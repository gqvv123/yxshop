<?php

namespace app\validate;

use think\Validate;

/**
 * 支付记录验证器
 */
class PaymentValidate extends Validate
{
    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'order_id' => 'require|number|min:1',
        'order_no' => 'require|max:64',
        'user_id' => 'require|number|min:1',
        'payment_method' => 'require|max:20',
        'amount' => 'require|number|min:0',
        'status' => 'require|in:10,20,30,40,50',
        'transaction_id' => 'max:64',
        'refund_reason' => 'max:255',
        'app_id' => 'require|number|min:1',
    ];

    /**
     * 错误消息
     * @var array
     */
    protected $message = [
        'order_id.require' => '订单ID不能为空',
        'order_id.number' => '订单ID必须是数字',
        'order_id.min' => '订单ID不能小于1',
        'order_no.require' => '订单编号不能为空',
        'order_no.max' => '订单编号最多不能超过64个字符',
        'user_id.require' => '用户ID不能为空',
        'user_id.number' => '用户ID必须是数字',
        'user_id.min' => '用户ID不能小于1',
        'payment_method.require' => '支付方式不能为空',
        'payment_method.max' => '支付方式最多不能超过20个字符',
        'amount.require' => '支付金额不能为空',
        'amount.number' => '支付金额必须是数字',
        'amount.min' => '支付金额不能小于0',
        'status.require' => '支付状态不能为空',
        'status.in' => '支付状态值必须是10,20,30,40,50其中之一',
        'transaction_id.max' => '第三方事务号最多不能超过64个字符',
        'refund_reason.max' => '退款原因最多不能超过255个字符',
        'app_id.require' => '应用ID不能为空',
        'app_id.number' => '应用ID必须是数字',
        'app_id.min' => '应用ID不能小于1',
    ];

    /**
     * 验证场景
     * @var array
     */
    protected $scene = [
        'create' => ['order_id', 'order_no', 'user_id', 'payment_method', 'amount', 'status', 'transaction_id', 'refund_reason', 'app_id'],
        'api_create' => ['order_id', 'payment_method'],
        'update' => ['status', 'transaction_id', 'refund_reason'],
        'refund' => ['status', 'refund_reason'],
    ];
}