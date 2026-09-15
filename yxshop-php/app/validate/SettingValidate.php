<?php

namespace app\validate;

use think\Validate;

/**
 * 商城设置验证器
 */
class SettingValidate extends Validate
{
    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'key' => 'require|max:30',
        'describe' => 'max:255',
        'values' => 'require',
        'app_id' => 'require|number|min:1',
    ];

    /**
     * 错误消息
     * @var array
     */
    protected $message = [
        'key.require' => '设置项标识不能为空',
        'key.max' => '设置项标识最多不能超过30个字符',
        'describe.max' => '设置项描述最多不能超过255个字符',
        'values.require' => '设置内容不能为空',
        'app_id.require' => '小程序ID不能为空',
        'app_id.number' => '小程序ID必须是数字',
        'app_id.min' => '小程序ID不能小于1',
    ];

    /**
     * 验证场景
     * @var array
     */
    protected $scene = [
        'create' => ['key', 'describe', 'values', 'app_id'],
        'update' => ['key', 'describe', 'values'],
    ];
}