<?php

namespace app\validate;

use think\Validate;

/**
 * 管理员操作日志验证器
 */
class AdminLogValidate extends Validate
{
    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'admin_id' => 'require|number|min:1',
        'admin_name' => 'require|max:100',
        'module' => 'require|max:50',
        'action' => 'require|max:50',
        'operation_type' => 'require|in:10,20,30,40,50,60,70,80',
        'request_url' => 'max:500',
        'request_method' => 'max:10',
        'operation_result' => 'in:10,20',
        'ip' => 'max:50',
        'app_id' => 'require|number|min:1',
    ];

    /**
     * 错误消息
     * @var array
     */
    protected $message = [
        'admin_id.require' => '管理员ID不能为空',
        'admin_id.number' => '管理员ID必须是数字',
        'admin_id.min' => '管理员ID不能小于1',
        'admin_name.require' => '管理员名称不能为空',
        'admin_name.max' => '管理员名称最多不能超过100个字符',
        'module.require' => '操作模块不能为空',
        'module.max' => '操作模块最多不能超过50个字符',
        'action.require' => '操作动作不能为空',
        'action.max' => '操作动作最多不能超过50个字符',
        'operation_type.require' => '操作类型不能为空',
        'operation_type.in' => '操作类型值必须是10,20,30,40,50,60,70,80其中之一',
        'request_url.max' => '请求URL最多不能超过500个字符',
        'request_method.max' => '请求方法最多不能超过10个字符',
        'operation_result.in' => '操作结果值必须是10或20',
        'ip.max' => 'IP地址最多不能超过50个字符',
        'app_id.require' => '应用ID不能为空',
        'app_id.number' => '应用ID必须是数字',
        'app_id.min' => '应用ID不能小于1',
    ];

    /**
     * 验证场景
     * @var array
     */
    protected $scene = [
        'create' => ['admin_id', 'admin_name', 'module', 'action', 'operation_type', 'request_url', 'request_method', 'operation_result', 'ip', 'app_id'],
        'update' => ['admin_name', 'module', 'action', 'operation_type', 'request_url', 'request_method', 'operation_result', 'ip'],
    ];
}