<?php

namespace app\validate;

use think\Validate;

/**
 * 优惠券验证器
 */
class CouponValidate extends Validate
{
    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'name' => 'require|max:255',
        'type' => 'in:1,2,3',
        'discount_amount' => 'number|min:0',
        'discount_rate' => 'number|between:0,100',
        'min_amount' => 'number|min:0',
        'expiry_type' => 'in:10,20',
        'scope' => 'in:10,20',
        'total_quantity' => 'number|min:-1',
        'app_id' => 'require|number|min:1',
        'status' => 'in:0,1',
    ];

    /**
     * 错误消息
     * @var array
     */
    protected $message = [
        'name.require' => '优惠券名称不能为空',
        'name.max' => '优惠券名称最多不能超过255个字符',
        'type.in' => '优惠券类型值必须是1、2或3',
        'discount_amount.number' => '减免金额必须是数字',
        'discount_amount.min' => '减免金额不能小于0',
        'discount_rate.number' => '折扣率必须是数字',
        'discount_rate.between' => '折扣率必须在0-100之间',
        'min_amount.number' => '最低消费金额必须是数字',
        'min_amount.min' => '最低消费金额不能小于0',
        'expiry_type.in' => '到期类型值必须是10或20',
        'scope.in' => '适用范围值必须是10或20',
        'total_quantity.number' => '发放总数量必须是数字',
        'total_quantity.min' => '发放总数量不能小于-1',
        'app_id.require' => '应用ID不能为空',
        'app_id.number' => '应用ID必须是数字',
        'app_id.min' => '应用ID不能小于1',
        'status.in' => '状态值必须是0或1',
    ];

    /**
     * 验证场景
     * @var array
     */
    protected $scene = [
        'create' => ['name', 'type', 'discount_amount', 'discount_rate', 'min_amount', 'expiry_type', 'scope', 'total_quantity', 'app_id'],
        'update' => ['name', 'type', 'discount_amount', 'discount_rate', 'min_amount', 'expiry_type', 'scope', 'total_quantity'],
        'status' => ['status'],
    ];
}