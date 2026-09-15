<?php

namespace app\validate;

use think\Validate;

/**
 * 订单验证器
 */
class OrderValidate extends Validate
{
    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'order_no' => 'require|max:32',
        'user_id' => 'require|number|min:1',
        'total_price' => 'require|number|min:0',
        'pay_price' => 'require|number|min:0',
        'express_price' => 'number|min:0',
        'pay_status' => 'in:10,20',
        'delivery_status' => 'in:10,20',
        'receipt_status' => 'in:10,20',
        'status' => 'in:10,20,30',
        'app_id' => 'require|number|min:1',
        'address_id' => 'require|number|min:1',
        'items' => 'require|array',

    ];

    /**
     * 错误消息
     * @var array
     */
    protected $message = [
        'order_no.require' => '订单号不能为空',
        'order_no.max' => '订单号最多不能超过32个字符',
        'user_id.require' => '用户ID不能为空',
        'user_id.number' => '用户ID必须是数字',
        'user_id.min' => '用户ID不能小于1',
        'total_price.require' => '订单金额不能为空',
        'total_price.number' => '订单金额必须是数字',
        'total_price.min' => '订单金额不能小于0',
        'pay_price.require' => '实际付款金额不能为空',
        'pay_price.number' => '实际付款金额必须是数字',
        'pay_price.min' => '实际付款金额不能小于0',
        'express_price.number' => '运费金额必须是数字',
        'express_price.min' => '运费金额不能小于0',
        'pay_status.in' => '付款状态值必须是10或20',
        'delivery_status.in' => '发货状态值必须是10或20',
        'receipt_status.in' => '收货状态值必须是10或20',
        'status.in' => '订单状态值必须是10、20或30',
        'app_id.require' => '应用ID不能为空',
        'app_id.number' => '应用ID必须是数字',
        'app_id.min' => '应用ID不能小于1',
        'address_id.require' => '收货地址不能为空',
        'address_id.number' => '收货地址ID必须是数字',
        'address_id.min' => '收货地址ID不能小于1',
        'items.require' => '订单商品不能为空',
        'items.array' => '订单商品格式错误',

    ];

    /**
     * 验证场景
     * @var array
     */
    protected $scene = [
        'create' => ['order_no', 'user_id', 'total_price', 'pay_price', 'express_price', 'app_id'],
        'api_create' => ['address_id', 'items', 'app_id'],
        'update' => ['pay_status', 'delivery_status', 'receipt_status', 'status'],
        'pay' => ['pay_status', 'pay_price'],
        'deliver' => ['delivery_status'],
        'receive' => ['receipt_status'],
    ];
}