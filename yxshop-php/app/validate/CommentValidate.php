<?php

namespace app\validate;

use think\Validate;

/**
 * 评价验证器
 */
class CommentValidate extends Validate
{
    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'score' => 'require|in:10,20,30',
        'content' => 'require|max:1000',
        'is_picture' => 'in:0,1',
        'is_anonymous' => 'in:0,1',
        'is_recommend' => 'in:0,1',
        'status' => 'in:0,1',
        'user_id' => 'require|number|min:1',
        'order_id' => 'require|number|min:1',
        'item_id' => 'require|number|min:1',
        'app_id' => 'require|number|min:1',
    ];

    /**
     * 错误消息
     * @var array
     */
    protected $message = [
        'score.require' => '评分不能为空',
        'score.in' => '评分值必须是10、20或30',
        'content.require' => '评价内容不能为空',
        'content.max' => '评价内容最多不能超过1000个字符',
        'is_picture.in' => '是否为图片评价值必须是0或1',
        'is_anonymous.in' => '是否匿名评价值必须是0或1',
        'is_recommend.in' => '是否推荐值必须是0或1',
        'status.in' => '状态值必须是0或1',
        'user_id.require' => '用户ID不能为空',
        'user_id.number' => '用户ID必须是数字',
        'user_id.min' => '用户ID不能小于1',
        'order_id.require' => '订单ID不能为空',
        'order_id.number' => '订单ID必须是数字',
        'order_id.min' => '订单ID不能小于1',
        'item_id.require' => '商品ID不能为空',
        'item_id.number' => '商品ID必须是数字',
        'item_id.min' => '商品ID不能小于1',
        'app_id.require' => '应用ID不能为空',
        'app_id.number' => '应用ID必须是数字',
        'app_id.min' => '应用ID不能小于1',
    ];

    /**
     * 验证场景
     * @var array
     */
    protected $scene = [
        'create' => ['score', 'content', 'is_picture', 'is_anonymous', 'user_id', 'order_id', 'item_id', 'app_id'],
        'update' => ['is_recommend', 'status'],
        'reply' => ['reply_content'],
    ];
}