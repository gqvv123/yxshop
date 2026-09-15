<?php

namespace app\validate;

use think\Validate;

/**
 * 分类验证器
 */
class CategoryValidate extends Validate
{
    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'name' => 'require|max:255',
        'parent_id' => 'number|min:0',
        'app_id' => 'number|min:0',
    ];

    protected $message = [
        'name.require' => '分类名称不能为空',
        'name.max' => '分类名称最多不能超过255个字符',
        'parent_id.number' => '父级分类ID必须是数字',
        'parent_id.min' => '父级分类ID不能小于0',
        'app_id.number' => '应用ID必须是数字',
        'app_id.min' => '应用ID不能小于0',
    ];

    /**

     * 验证场景
     * @var array
     */
    protected $scene = [
        'create' => ['name', 'parent_id', 'app_id'],
        'update' => ['name', 'parent_id'],
    ];
}