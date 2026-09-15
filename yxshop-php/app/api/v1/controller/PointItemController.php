<?php

namespace app\api\v1\controller;

use support\Request;
use app\service\ItemService;

/**
 * 积分商品列表
 *
 * 4 层架构：Controller -> Service -> Repository -> Model
 * 控制器禁止直接 use app\model\*
 */
class PointItemController extends BaseController
{
    private ItemService $itemService;

    public function __construct()
    {
        $this->itemService = new ItemService();
    }

    public function getList(Request $request)
    {
        $appId = (int) $request->get('app_id', 0);
        $ids = $request->get('ids');
        $page = max(1, (int) $request->get('page', 1));
        $pageSize = min(50, max(1, (int) $request->get('page_size', 20)));

        $result = $this->itemService->getPointExchangeItems($appId, $page, $pageSize, $ids);

        return $this->success($result);
    }
}
