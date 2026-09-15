<?php

namespace app\admin\controller;

use support\Request;
use support\Response;
use app\service\cache\TieredCache;

class CommonController extends BaseController
{
    public function clearCache(Request $request): Response
    {
        try {
            TieredCache::flush();
        } catch (\Throwable $e) {
            return $this->error('清除缓存失败：' . $e->getMessage());
        }

        return $this->success([], '缓存清除成功');
    }

    public function dictAll(Request $request): Response
    {
        $dict = [
            'order_status' => [
                ['value' => 0, 'label' => '待付款'],
                ['value' => 1, 'label' => '待发货'],
                ['value' => 2, 'label' => '已发货'],
                ['value' => 3, 'label' => '已完成'],
                ['value' => 4, 'label' => '已取消'],
                ['value' => 5, 'label' => '已退款'],
            ],
            'pay_status' => [
                ['value' => 0, 'label' => '未支付'],
                ['value' => 1, 'label' => '已支付'],
                ['value' => 2, 'label' => '已退款'],
            ],
            'item_status' => [
                ['value' => 0, 'label' => '下架'],
                ['value' => 1, 'label' => '上架'],
            ],
            'user_status' => [
                ['value' => 0, 'label' => '禁用'],
                ['value' => 1, 'label' => '正常'],
            ],
            'comment_status' => [
                ['value' => 0, 'label' => '隐藏'],
                ['value' => 1, 'label' => '显示'],
            ],
            'coupon_type' => [
                ['value' => 1, 'label' => '满减券'],
                ['value' => 2, 'label' => '折扣券'],
                ['value' => 3, 'label' => '无门槛券'],
            ],
            'service_status' => [
                ['value' => 0, 'label' => '待处理'],
                ['value' => 1, 'label' => '处理中'],
                ['value' => 2, 'label' => '已完成'],
                ['value' => 3, 'label' => '已关闭'],
            ],
        ];

        return $this->success($dict);
    }
}