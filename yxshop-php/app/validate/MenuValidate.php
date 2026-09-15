<?php

namespace app\validate;

use think\Validate;

/**
 * 菜单验证器
 */
class MenuValidate extends Validate
{
    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'name' => 'require|max:255',
        'url' => 'require|max:255',
        'parent_id' => 'number|min:0',
        'sort' => 'number|min:0',
        'model' => 'max:255',
        'icon' => 'max:255',
        'app_id' => 'require|number|min:1',
        'is_show' => 'in:0,1',
        'deleted_at' => 'in:0,1',
    ];

    /**
     * 错误消息
     * @var array
     */
    protected $message = [
        'name.require' => '权限名称不能为空',
        'name.max' => '权限名称最多不能超过255个字符',
        'url.require' => '权限url不能为空',
        'url.max' => '权限url最多不能超过255个字符',
        'parent_id.number' => '父级id必须是数字',
        'parent_id.min' => '父级id不能小于0',
        'sort.number' => '排序必须是数字',
        'sort.min' => '排序不能小于0',
        'model.max' => '模块最多不能超过255个字符',
        'icon.max' => '图标最多不能超过255个字符',
        'app_id.require' => '小程序id不能为空',
        'app_id.number' => '小程序id必须是数字',
        'app_id.min' => '小程序id不能小于1',
        'is_show.in' => '是否显示值必须是0或1',
        'deleted_at.in' => '是否删除值必须是0或1',
    ];

    /**
     * 验证场景
     * @var array
     */
    protected $scene = [
        'create' => ['name', 'url', 'parent_id', 'sort', 'model', 'icon', 'app_id', 'is_show', 'deleted_at'],
        'update' => ['name' => 'max:255', 'url' => 'max:255', 'parent_id', 'sort', 'model', 'icon', 'is_show', 'deleted_at'],
    ];
}