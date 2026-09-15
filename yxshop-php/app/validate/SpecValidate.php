<?php

namespace app\validate;

use think\Validate;

/**
 * 规格验证器
 */
class SpecValidate extends Validate
{
    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'type_id' => 'require|number|min:1',
        'name' => 'require|max:55',
        'order' => 'number|min:0',
        'search_index' => 'in:0,1',
        'app_id' => 'require|number|min:1',
        'sort_order' => 'number',
    ];

    /**
     * 错误消息
     * @var array
     */
    protected $message = [
        'type_id.require' => '规格类型不能为空',
        'type_id.number' => '规格类型必须是数字',
        'type_id.min' => '规格类型不能小于1',
        'name.require' => '规格名称不能为空',
        'name.max' => '规格名称最多不能超过55个字符',
        'order.number' => '排序必须是数字',
        'order.min' => '排序不能小于0',
        'search_index.in' => '是否需要检索值必须是0或1',
        'app_id.require' => '小程序ID不能为空',
        'app_id.number' => '小程序ID必须是数字',
        'app_id.min' => '小程序ID不能小于1',
        'sort_order.number' => '排序必须是数字',
    ];

    /**
     * 验证场景
     * @var array
     */
    protected $scene = [
        'create' => ['type_id', 'name', 'order', 'search_index', 'app_id', 'sort_order'],
        'update' => ['type_id', 'name', 'order', 'search_index', 'sort_order'],
    ];
}