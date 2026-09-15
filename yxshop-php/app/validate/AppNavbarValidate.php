<?php

namespace app\validate;

use think\Validate;

/**
 * 小程序导航栏验证器
 */
class AppNavbarValidate extends Validate
{
    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'title' => 'require|max:100',
        'text_color' => 'require|in:10,20',
        'background_color' => 'require|max:10',
    ];

    /**
     * 错误消息
     * @var array
     */
    protected $message = [
        'title.require' => '小程序标题不能为空',
        'title.max' => '小程序标题最多不能超过100个字符',
        'text_color.require' => '顶部导航文字颜色不能为空',
        'text_color.in' => '顶部导航文字颜色值必须是10或20',
        'background_color.require' => '顶部导航背景色不能为空',
        'background_color.max' => '顶部导航背景色最多不能超过10个字符',
    ];

    /**
     * 验证场景
     * @var array
     */
    protected $scene = [
        'create' => ['title', 'text_color', 'background_color'],
        'update' => ['title', 'text_color', 'background_color'],
    ];
}