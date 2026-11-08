<?php

namespace app\validate;

use think\Validate;

/**
 * 小程序验证器
 */
class AppValidate extends Validate
{
    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'name' => 'require|max:50',
        'app_id' => 'require|max:50',
        'secret' => 'require|max:50',
        'mch_id' => 'max:50',
        'api_key' => 'max:255',
        'ver' => 'max:30',
        'logo_id' => 'require|number|min:1',
        'category_style' => 'in:10,20,30',
        'share_title' => 'require|max:10',
        'owner_id' => 'number|min:1',
        'secondary_color' => 'max:100',
        'primary_color' => 'require|max:100',
    ];

    /**
     * 错误消息
     * @var array
     */
    protected $message = [
        'name.require' => '小程序名称不能为空',
        'name.max' => '小程序名称最多不能超过50个字符',
        'app_id.require' => '小程序AppID不能为空',
        'app_id.max' => '小程序AppID最多不能超过50个字符',
        'secret.require' => '小程序AppSecret不能为空',
        'secret.max' => '小程序AppSecret最多不能超过50个字符',
        'mch_id.max' => '微信商户号最多不能超过50个字符',
        'api_key.max' => '微信支付密钥最多不能超过255个字符',
        'ver.max' => '版本号最多不能超过30个字符',
        'logo_id.require' => '图片地址不能为空',
        'logo_id.number' => '图片地址必须是数字',
        'logo_id.min' => '图片地址不能小于1',
        'category_style.in' => '分类页样式值必须是10,20,30其中之一',
        'share_title.require' => '分享标题不能为空',
        'share_title.max' => '分享标题最多不能超过10个字符',
        'owner_id.number' => '所属用户必须是数字',
        'owner_id.min' => '所属用户不能小于1',
        'secondary_color.max' => '商店色系最多不能超过100个字符',
        'primary_color.require' => '门店色调不能为空',
        'primary_color.max' => '门店色调最多不能超过100个字符',
    ];

    /**
     * 验证场景
     * @var array
     */
    protected $scene = [
        'create' => ['name', 'app_id', 'secret', 'mch_id', 'api_key', 'ver', 'logo_id', 'category_style', 'share_title', 'owner_id', 'secondary_color', 'primary_color'],
        'update' => ['name', 'app_id', 'secret', 'mch_id', 'api_key', 'ver', 'logo_id', 'category_style', 'share_title', 'owner_id', 'secondary_color', 'primary_color'],
    ];
}