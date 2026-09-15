<?php

namespace app\validate;

use think\Validate;

/**
 * 用户地址验证器
 */
class UserAddressValidate extends Validate
{
    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'name' => 'require|max:30',
        'phone' => 'require|max:20|regex:/^1[3-9]\d{9}$/',
        'province_id' => 'require|number|min:0',
        'city_id' => 'require|number|min:0',
        'district_id' => 'require|number|min:0',
        'detail' => 'require|max:255',
        'zip_code' => 'max:10',
        'is_default' => 'in:0,1',
        'label' => 'max:20',
        'user_id' => 'require|number|min:1',
        'app_id' => 'require|number|min:1',
    ];

    /**
     * 错误消息
     * @var array
     */
    protected $message = [
        'name.require' => '收货人姓名不能为空',
        'name.max' => '收货人姓名最多不能超过30个字符',
        'phone.require' => '联系电话不能为空',
        'phone.max' => '联系电话最多不能超过20个字符',
        'phone.regex' => '联系电话格式不正确',
        'province_id.require' => '所在省份ID不能为空',
        'province_id.number' => '所在省份ID必须是数字',
        'province_id.min' => '所在省份ID不能小于0',
        'city_id.require' => '所在城市ID不能为空',
        'city_id.number' => '所在城市ID必须是数字',
        'city_id.min' => '所在城市ID不能小于0',
        'district_id.require' => '所在区ID不能为空',
        'district_id.number' => '所在区ID必须是数字',
        'district_id.min' => '所在区ID不能小于0',
        'detail.require' => '详细地址不能为空',
        'detail.max' => '详细地址最多不能超过255个字符',
        'zip_code.max' => '邮政编码最多不能超过10个字符',
        'is_default.in' => '是否默认地址值必须是0或1',
        'label.max' => '地址标签最多不能超过20个字符',
        'user_id.require' => '用户ID不能为空',
        'user_id.number' => '用户ID必须是数字',
        'user_id.min' => '用户ID不能小于1',
        'app_id.require' => '应用ID不能为空',
        'app_id.number' => '应用ID必须是数字',
        'app_id.min' => '应用ID不能小于1',
    ];

    /**
     * 验证场景
     * @var array
     */
    protected $scene = [
        'create' => ['name', 'phone', 'province_id', 'city_id', 'district_id', 'detail', 'zip_code', 'is_default', 'label', 'user_id', 'app_id'],
        'update' => ['name', 'phone', 'province_id', 'city_id', 'district_id', 'detail', 'zip_code', 'is_default', 'label'],
    ];
}