<?php

namespace app\validate;

use think\Validate;

/**
 * 小程序页面验证器
 */
class AppPageValidate extends Validate
{
    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'type' => 'require|in:10,20',
        'name' => 'require|max:255',
        'data' => 'require',
        'app_id' => 'require|number|min:1',
        'deleted_at' => 'in:0,1',
    ];

    /**
     * 错误消息
     * @var array
     */
    protected $message = [
        'type.require' => '页面类型不能为空',
        'type.in' => '页面类型值必须是10或20',
        'name.require' => '页面名称不能为空',
        'name.max' => '页面名称最多不能超过255个字符',
        'data.require' => '页面数据不能为空',
        'app_id.require' => '小程序ID不能为空',
        'app_id.number' => '小程序ID必须是数字',
        'app_id.min' => '小程序ID不能小于1',
        'deleted_at.in' => '软删除值必须是0或1',
    ];

    /**
     * 验证场景
     * @var array
     */
    protected $scene = [
        'create' => ['type', 'name', 'data', 'app_id', 'deleted_at'],
        'update' => ['type', 'name', 'data', 'deleted_at'],
    ];
}