<?php

namespace app\api\v1\controller;

use app\service\DeliveryRuleService;
use app\service\DeliveryService;
use app\service\ExpressService;
use support\Request;

/**
 * C 端控制器：物流配送。
 *
 * 对应管理端「物流配送 → 物流公司 / 配送模板」。
 * 小程序侧用于结算页选择快递公司与配送方式、试算运费。
 */
class DeliveryController extends BaseController
{
    protected ExpressService $expressService;
    protected DeliveryService $deliveryService;
    protected DeliveryRuleService $ruleService;

    public function __construct()
    {
        $this->expressService = new ExpressService();
        $this->deliveryService = new DeliveryService();
        $this->ruleService = new DeliveryRuleService();
    }

    /**
     * 快递公司列表（下单时可选）
     *
     * GET /api/v1/delivery/expresses
     */
    public function getExpresses(Request $request)
    {
        $appId = (int) $request->get('app_id', 0);
        $expresses = $this->expressService->getExpressList($appId);

        return $this->success(['list' => $expresses, 'total' => count($expresses)]);
    }

    /**
     * 配送方式列表
     *
     * GET /api/v1/delivery/list
     */
    public function getList(Request $request)
    {
        $appId = (int) $request->get('app_id', 0);
        // C 端只返回启用中且有名称的配送方式（管理端分页列表走 admin 接口）
        $list = $this->deliveryService->getActiveList($appId, 100);

        return $this->success([
            'list'      => $list,
            'total'     => count($list),
            'page'      => 1,
            'page_size' => count($list),
        ]);
    }

    /**
     * 配送方式详情（含计费规则）
     *
     * GET /api/v1/delivery/detail?id=1
     */
    public function getDetail(Request $request)
    {
        $id = (int) $request->get('id', 0);
        if (!$id) {
            return $this->error('配送方式ID不能为空');
        }

        try {
            $delivery = $this->deliveryService->getDetail($id);
        } catch (\Exception $e) {
            return $this->error('配送方式不存在', 404);
        }

        return $this->success($delivery);
    }

    /**
     * 运费试算
     *
     * GET /api/v1/delivery/freight?delivery_id=1&quantity=2&amount=99.00&region_id=110000
     *
     * 计费规则（yxshop_delivery_rules）：
     * - delivery_type 10=按件数 20=按金额 30=两者同时满足
     * - first/first_fee        首件（首额）与其运费
     * - additional/additional_fee 续件（续额）与其运费
     * - min_price/min_num      免邮门槛（金额 / 件数）
     * - region                 适用地区，空或 '0' 表示全国
     */
    public function getFreight(Request $request)
    {
        $deliveryId = (int) $request->get('delivery_id', 0);
        $quantity = max(1, (int) $request->get('quantity', 1));
        $amount = round((float) $request->get('amount', 0), 2);
        $regionId = (int) $request->get('region_id', 0);

        if (!$deliveryId) {
            return $this->error('请选择配送方式');
        }

        $result = $this->ruleService->calcFreight($deliveryId, $quantity, $amount, $regionId);
        $rule = $result['rule'];
        if ($rule) {
            $result['rule'] = is_object($rule) && method_exists($rule, 'toArray') ? $rule->toArray() : (array) $rule;
        }

        return $this->success($result);
    }
}
