<?php

namespace app\validate;

use think\Validate;

/**
 * 物流公司验证器
 */
class ExpressValidate extends Validate
{
    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'express_name' => 'require|max:255',
        'express_code' => 'require|max:30',
        'sort' => 'number|min:0',
        'app_id' => 'require|number|min:1',
    ];

    /**
     * 错误消息
     * @var array
     */
    protected $message = [
        'express_name.require' => '物流公司名称不能为空',
        'express_name.max' => '物流公司名称最多不能超过255个字符',
        'express_code.require' => '物流公司代码不能为空',
        'express_code.max' => '物流公司代码最多不能超过30个字符',
        'sort.number' => '排序必须是数字',
        'sort.min' => '排序不能小于0',
        'app_id.require' => '小程序ID不能为空',
        'app_id.number' => '小程序ID必须是数字',
        'app_id.min' => '小程序ID不能小于1',
    ];

    /**
     * 验证场景
     * @var array
     */
    protected $scene = [
        'create' => ['express_name', 'express_code', 'sort', 'app_id'],
        'update' => ['express_name', 'express_code', 'sort'],
    ];
}