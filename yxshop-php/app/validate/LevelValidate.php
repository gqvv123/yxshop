<?php

namespace app\validate;

use think\Validate;

/**
 * 会员等级验证器
 */
class LevelValidate extends Validate
{
    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'integral' => 'require|number|min:0',
        'app_id' => 'require|number|min:1',
        'key' => 'require|max:100',
        'values' => 'max:100',
        'agio' => 'number',
        'name' => 'max:100',
        'sort' => 'number',
        'desc' => 'max:255',
        'shop_money' => 'require|number|min:0',
    ];

    /**
     * 错误消息
     * @var array
     */
    protected $message = [
        'integral.require' => '积分不能为空',
        'integral.number' => '积分必须是数字',
        'integral.min' => '积分不能小于0',
        'app_id.require' => '小程序ID不能为空',
        'app_id.number' => '小程序ID必须是数字',
        'app_id.min' => '小程序ID不能小于1',
        'key.require' => '等级标识不能为空',
        'key.max' => '等级标识最多不能超过100个字符',
        'values.max' => '等级值最多不能超过100个字符',
        'agio.number' => '扣折必须是数字',
        'name.max' => '等级名称最多不能超过100个字符',
        'sort.number' => '排序必须是数字',
        'desc.max' => '等级描述最多不能超过255个字符',
        'shop_money.require' => '消费金额不能为空',
        'shop_money.number' => '消费金额必须是数字',
        'shop_money.min' => '消费金额不能小于0',
    ];

    /**
     * 验证场景
     * @var array
     */
    protected $scene = [
        'create' => ['integral', 'app_id', 'key', 'values', 'agio', 'name', 'sort', 'desc', 'shop_money'],
        'update' => ['integral', 'key', 'values', 'agio', 'name', 'sort', 'desc', 'shop_money'],
    ];
}