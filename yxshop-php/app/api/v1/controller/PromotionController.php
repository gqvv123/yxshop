<?php

namespace app\api\v1\controller;

use app\service\PromItemService;
use app\service\PromotionService;
use support\Request;

/**
 * C 端控制器：促销活动。
 *
 * 对应管理端「营销管理 → 促销活动 / 商品促销 / 订单促销」。
 * 小程序侧用于秒杀、拼团、限时促销的展示与下单。
 */
class PromotionController extends BaseController
{
    protected PromotionService $promotionService;
    protected PromItemService $promItemService;

    public function __construct()
    {
        $this->promotionService = new PromotionService();
        $this->promItemService = new PromItemService();
    }

    /**
     * 促销活动列表（仅进行中 / 未结束的活动）
     *
     * GET /api/v1/promotion/list?page=1&page_size=10&type=1
     */
    public function getList(Request $request)
    {
        $appId = (int) $request->get('app_id', 0);
        $page = max(1, (int) $request->get('page', 1));
        $pageSize = min(50, max(1, (int) $request->get('page_size', 10)));
        $type = trim((string) $request->get('type', ''));

        $result = $this->promotionService->getList($appId, $page * $pageSize, '');
        // getList 返回 LengthAwarePaginator（不是数组），需要取 items()
        $list = array_map(static function ($item) {
            return is_object($item) && method_exists($item, 'toArray') ? $item->toArray() : (array) $item;
        }, $result->items());

        $now = time();
        $list = array_values(array_filter($list, static function (array $prom) use ($type, $now): bool {
            // 只保留状态启用、未结束、类型匹配的活动
            // 注意：$prom 是 toArray() 后的普通数组，必须用 [] 访问，
            // 若写成 $prom->xxx 会取到 null，导致所有记录被过滤掉
            if ((int) ($prom['status'] ?? 0) !== 1) {
                return false;
            }
            $endTime = (int) ($prom['end_time'] ?? 0);
            if ($endTime > 0 && $endTime < $now) {
                return false;
            }
            if ($type !== '' && (string) ($prom['type'] ?? '') !== $type) {
                return false;
            }
            return true;
        }));

        $total = count($list);
        $pageList = array_slice($list, ($page - 1) * $pageSize, $pageSize);

        // 补上每个活动的促销商品数与状态文案
        foreach ($pageList as &$prom) {
            $prom['status_text'] = $this->statusText($prom, $now);
            $prom['prom_item_count'] = count(
                $this->promItemService->getAvailablePromItems((int) $prom['id'], $appId)
            );
        }
        unset($prom);

        return $this->success([
            'list'      => $pageList,
            'total'     => $total,
            'page'      => $page,
            'page_size' => $pageSize,
        ]);
    }

    /**
     * 活动详情（含促销商品）
     *
     * GET /api/v1/promotion/detail?id=1
     */
    public function getDetail(Request $request)
    {
        $id = (int) $request->get('id', 0);
        if (!$id) {
            return $this->error('活动ID不能为空');
        }

        $prom = $this->promotionService->getDetail($id);
        if (!$prom) {
            return $this->error('活动不存在', 404);
        }
        $prom = is_object($prom) && method_exists($prom, 'toArray') ? $prom->toArray() : (array) $prom;

        $appId = (int) $request->get('app_id', 0);
        $prom['status_text'] = $this->statusText($prom, time());
        $prom['prom_items'] = $this->promItemService->getAvailablePromItems($id, $appId);

        return $this->success($prom);
    }

    /**
     * 促销商品列表
     *
     * GET /api/v1/promotion/items?prom_id=1
     */
    public function getItems(Request $request)
    {
        $promId = (int) $request->get('prom_id', 0);
        $appId = (int) $request->get('app_id', 0);

        $items = $this->promItemService->getAvailablePromItems($promId ?: null, $appId);

        return $this->success(['list' => $items, 'total' => count($items)]);
    }

    /**
     * 活动状态文案
     */
    private function statusText(array $prom, int $now): string
    {
        $start = (int) ($prom['start_time'] ?? 0);
        $end = (int) ($prom['end_time'] ?? 0);
        if ($start > 0 && $now < $start) {
            return '未开始';
        }
        if ($end > 0 && $now > $end) {
            return '已结束';
        }
        return '进行中';
    }
}
