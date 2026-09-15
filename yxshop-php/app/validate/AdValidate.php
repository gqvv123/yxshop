<?php

namespace app\validate;

use think\Validate;

/**
 * 广告验证器
 */
class AdValidate extends Validate
{
    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'title' => 'require|max:255',
        'count' => 'require',
        'app_id' => 'require|number|min:1',
    ];

    /**
     * 错误消息
     * @var array
     */
    protected $message = [
        'title.require' => '广告标题不能为空',
        'title.max' => '广告标题最多不能超过255个字符',
        'count.require' => '广告内容不能为空',
        'app_id.require' => '应用ID不能为空',
        'app_id.number' => '应用ID必须是数字',
        'app_id.min' => '应用ID不能小于1',
    ];

    /**
     * 验证场景
     * @var array
     */
    protected $scene = [
        'create' => ['title', 'count', 'app_id'],
        'update' => ['title', 'count'],
    ];
}