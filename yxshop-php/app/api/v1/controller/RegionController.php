<?php

namespace app\api\v1\controller;

use app\service\RegionService;
use support\Request;

/**
 * C 端控制器：地区数据。
 *
 * 对应管理端「系统设置 → 地区管理」。
 * 小程序侧用于收货地址的省市区级联选择。
 */
class RegionController extends BaseController
{
    protected RegionService $regionService;

    public function __construct()
    {
        $this->regionService = new RegionService();
    }

    /**
     * 地区树（省 → 市 → 区）
     *
     * GET /api/v1/region/tree
     */
    public function getTree(Request $request)
    {
        $pid = (int) $request->get('pid', 0);
        $tree = $this->regionService->getRegionTree($pid);

        return $this->success(['list' => $tree, 'total' => count($tree)]);
    }

    /**
     * 省份列表
     *
     * GET /api/v1/region/provinces
     */
    public function getProvinces(Request $request)
    {
        $list = $this->regionService->getProvinces();

        return $this->success(['list' => $list, 'total' => count($list)]);
    }

    /**
     * 城市列表
     *
     * GET /api/v1/region/cities?pid=1
     */
    public function getCities(Request $request)
    {
        $pid = (int) $request->get('pid', 0);
        if (!$pid) {
            return $this->error('省份ID不能为空');
        }
        $list = $this->regionService->getCities($pid);

        return $this->success(['list' => $list, 'total' => count($list)]);
    }

    /**
     * 区县列表
     *
     * GET /api/v1/region/districts?pid=1
     */
    public function getDistricts(Request $request)
    {
        $pid = (int) $request->get('pid', 0);
        if (!$pid) {
            return $this->error('城市ID不能为空');
        }
        $list = $this->regionService->getDistricts($pid);

        return $this->success(['list' => $list, 'total' => count($list)]);
    }
}
