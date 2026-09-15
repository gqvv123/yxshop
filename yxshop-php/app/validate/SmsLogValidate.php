<?php

namespace app\validate;

use think\Validate;

/**
 * 短信日志验证器
 */
class SmsLogValidate extends Validate
{
    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'user_id' => 'require|number|min:1',
        'phone' => 'require|max:20|regex:/^1[3-9]\d{9}$/',
        'sms_type' => 'require|in:10,20,30,40,50',
        'sms_content' => 'require|max:500',
        'sms_status' => 'require|in:10,20,30,40',
        'sms_result' => 'max:200',
        'ip' => 'max:50',
        'app_id' => 'require|number|min:1',
    ];

    /**
     * 错误消息
     * @var array
     */
    protected $message = [
        'user_id.require' => '用户ID不能为空',
        'user_id.number' => '用户ID必须是数字',
        'user_id.min' => '用户ID不能小于1',
        'phone.require' => '手机号不能为空',
        'phone.max' => '手机号最多不能超过20个字符',
        'phone.regex' => '手机号格式不正确',
        'sms_type.require' => '短信类型不能为空',
        'sms_type.in' => '短信类型值必须是10,20,30,40,50其中之一',
        'sms_content.require' => '短信内容不能为空',
        'sms_content.max' => '短信内容最多不能超过500个字符',
        'sms_status.require' => '发送状态不能为空',
        'sms_status.in' => '发送状态值必须是10,20,30,40其中之一',
        'sms_result.max' => '发送结果最多不能超过200个字符',
        'ip.max' => 'IP地址最多不能超过50个字符',
        'app_id.require' => '小程序ID不能为空',
        'app_id.number' => '小程序ID必须是数字',
        'app_id.min' => '小程序ID不能小于1',
    ];

    /**
     * 验证场景
     * @var array
     */
    protected $scene = [
        'create' => ['user_id', 'phone', 'sms_type', 'sms_content', 'sms_status', 'sms_result', 'ip', 'app_id'],
        'update' => ['sms_status', 'sms_result'],
    ];
}