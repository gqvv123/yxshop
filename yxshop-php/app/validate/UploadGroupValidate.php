<?php

namespace app\validate;

use think\Validate;

/**
 * 上传文件分组验证器
 */
class UploadGroupValidate extends Validate
{
    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'group_type' => 'require|max:10',
        'group_name' => 'require|max:30',
        'sort' => 'number|min:0',
        'app_id' => 'require|number|min:1',
    ];

    /**
     * 错误消息
     * @var array
     */
    protected $message = [
        'group_type.require' => '文件类型不能为空',
        'group_type.max' => '文件类型最多不能超过10个字符',
        'group_name.require' => '分类名称不能为空',
        'group_name.max' => '分类名称最多不能超过30个字符',
        'sort.number' => '分类排序必须是数字',
        'sort.min' => '分类排序不能小于0',
        'app_id.require' => '小程序ID不能为空',
        'app_id.number' => '小程序ID必须是数字',
        'app_id.min' => '小程序ID不能小于1',
    ];

    /**
     * 验证场景
     * @var array
     */
    protected $scene = [
        'create' => ['group_type', 'group_name', 'sort', 'app_id'],
        'update' => ['group_type', 'group_name', 'sort'],
    ];
}