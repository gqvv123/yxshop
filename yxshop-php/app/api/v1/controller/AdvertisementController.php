<?php

namespace app\api\v1\controller;

use app\service\AdvertisementService;
use support\Request;

/**
 * C 端控制器：广告位。
 *
 * 对应管理端「内容管理 → 广告管理」。
 * 小程序侧用于首页轮播图、楼层横幅等运营位。
 */
class AdvertisementController extends BaseController
{
    protected AdvertisementService $advertisementService;

    public function __construct()
    {
        $this->advertisementService = new AdvertisementService();
    }

    /**
     * 有效广告列表
     *
     * GET /api/v1/advertisement/list?position=home_banner&limit=10
     *
     * 只返回当前处于有效期且启用的广告。
     */
    public function getList(Request $request)
    {
        $appId = (int) $request->get('app_id', 0);
        $position = trim((string) $request->get('position', ''));
        $limit = min(50, max(1, (int) $request->get('limit', 20)));

        $ads = $this->advertisementService->getActiveAds($position ?: null, $appId);

        // getActiveAds 返回 Collection<Eloquent 模型>，
        // 模型必须用 toArray() 转换；(array) $model 会得到带 "\0*\0attributes" 之类
        // 受保护属性前缀的畸形键，前端取不到字段。
        $records = [];
        foreach ($ads as $ad) {
            $record = is_object($ad) && method_exists($ad, 'toArray') ? $ad->toArray() : (array) $ad;
            if ((int) ($record['is_show'] ?? 1) !== 1) {
                continue;
            }
            $records[] = $record;
        }

        return $this->success([
            'list'  => array_slice($records, 0, $limit),
            'total' => count($records),
        ]);
    }
}
