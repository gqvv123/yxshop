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
        // count 是历史遗留的「广告内容」列（mediumtext），前端广告表单并不提供该字段，
        // 若保持必填会导致广告新增必然失败，这里改为可选；上限按 mediumtext 实际容量。
        'count' => 'max:16777215',
        'app_id' => 'number|min:0',
    ];

    /**
     * 错误消息
     * @var array
     */
    protected $message = [
        'title.require' => '广告标题不能为空',
        'title.max' => '广告标题最多不能超过255个字符',
        'count.max' => '广告内容过长',
        'app_id.number' => '应用ID必须是数字',
        'app_id.min' => '应用ID不能小于0',
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