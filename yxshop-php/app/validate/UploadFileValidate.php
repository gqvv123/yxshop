<?php

namespace app\validate;

use think\Validate;

/**
 * 上传文件验证器
 */
class UploadFileValidate extends Validate
{
    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'storage' => 'require|max:20',
        'group_id' => 'require|number|min:0',
        'file_url' => 'require|max:255',
        'file_name' => 'require|max:255',
        'file_size' => 'require|number|min:0',
        'file_type' => 'require|max:20',
        'extension' => 'require|max:20',
        'is_user' => 'in:0,1',
        'deleted_at' => 'in:0,1',
        'app_id' => 'require|number|min:1',
        'user_id' => 'require|number|min:1',
    ];

    /**
     * 错误消息
     * @var array
     */
    protected $message = [
        'storage.require' => '存储方式不能为空',
        'storage.max' => '存储方式最多不能超过20个字符',
        'group_id.require' => '文件分组ID不能为空',
        'group_id.number' => '文件分组ID必须是数字',
        'group_id.min' => '文件分组ID不能小于0',
        'file_url.require' => '存储域名不能为空',
        'file_url.max' => '存储域名最多不能超过255个字符',
        'file_name.require' => '文件路径不能为空',
        'file_name.max' => '文件路径最多不能超过255个字符',
        'file_size.require' => '文件大小不能为空',
        'file_size.number' => '文件大小必须是数字',
        'file_size.min' => '文件大小不能小于0',
        'file_type.require' => '文件类型不能为空',
        'file_type.max' => '文件类型最多不能超过20个字符',
        'extension.require' => '文件扩展名不能为空',
        'extension.max' => '文件扩展名最多不能超过20个字符',
        'is_user.in' => '是否为C端用户上传值必须是0或1',
        'deleted_at.in' => '软删除值必须是0或1',
        'app_id.require' => '小程序ID不能为空',
        'app_id.number' => '小程序ID必须是数字',
        'app_id.min' => '小程序ID不能小于1',
        'user_id.require' => '用户ID不能为空',
        'user_id.number' => '用户ID必须是数字',
        'user_id.min' => '用户ID不能小于1',
    ];

    /**
     * 验证场景
     * @var array
     */
    protected $scene = [
        'create' => ['storage', 'group_id', 'file_url', 'file_name', 'file_size', 'file_type', 'extension', 'is_user', 'deleted_at', 'app_id', 'user_id'],
        'update' => ['group_id', 'is_user', 'deleted_at'],
    ];
}