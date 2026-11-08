<?php

namespace app\validate;

use think\Validate;

/**
 * 品牌验证器
 */
class BrandValidate extends Validate
{
    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'id'      => 'require|number|min:1',
        'name'    => 'require|max:60',
        'logo'    => 'max:80',
        'phone'   => 'max:80',
        'address' => 'max:255',
        'url'     => 'max:255',
        'seo_title' => 'max:200',
        'seo_keywords' => 'max:500',
        'seo_description' => 'max:1000',
        'sort_order' => 'number',
        'is_hot'  => 'in:0,1',
        'app_id'  => 'number|min:0',
    ];

    /**
     * 错误消息
     * @var array
     */
    protected $message = [
        'name.require' => '品牌名称不能为空',
        'name.max' => '品牌名称最多不能超过60个字符',
        'logo.max' => '品牌logo最多不能超过80个字符',
        'phone.max' => '电话最多不能超过80个字符',
        'address.max' => '商家地址最多不能超过255个字符',
        'url.max' => '品牌地址最多不能超过255个字符',
        'seo_title.max' => 'SEO标题最多不能超过200个字符',
        'seo_keywords.max' => 'SEO关键词最多不能超过500个字符',
        'seo_description.max' => 'SEO描述最多不能超过1000个字符',
        'sort_order.number' => '排序必须是数字',
        'is_hot.in' => '是否推荐值必须是0或1',
        'app_id.number' => '应用ID必须是数字',
        'app_id.min' => '应用ID不能小于0',
        'id.require' => '品牌ID不能为空',
        'id.number' => '品牌ID必须是数字',
        'id.min' => '品牌ID不能小于1',
    ];

    /**
     * 验证场景
     * @var array
     */
    protected $scene = [
        'create' => ['name', 'logo', 'phone', 'address', 'url', 'seo_title', 'seo_keywords', 'seo_description', 'sort_order', 'is_hot', 'app_id'],
        'update' => ['id', 'name', 'logo', 'phone', 'address', 'url', 'seo_title', 'seo_keywords', 'seo_description', 'sort_order', 'is_hot', 'app_id'],
    ];
}