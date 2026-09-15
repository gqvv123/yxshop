<?php

namespace app\admin\controller;

use support\Request;
use app\service\CouponService;
use app\validate\CouponValidate;
use app\exception\ValidationException;

class CouponController extends BaseController
{
    protected $couponService;

    public function __construct()
    {
        parent::__construct();
        $this->couponService = new CouponService();
    }

    public function index(Request $request)
    {
        $appId = $this->getAppId($request);
        $pageSize = (int) $request->get('page_size', 20);
        $coupons = $this->couponService->getPaginatedList($appId, $pageSize);
        return $this->paginate($coupons);
    }

    public function show(Request $request, $id)
    {
        $coupon = $this->couponService->findOrFail($id);
        return $this->success($coupon);
    }

    public function store(Request $request)
    {
        $data = $request->post();
        $data['app_id'] = $this->getAppId($request);

        $validate = new CouponValidate();
        $validate->failException(false);
        if (!$validate->scene('create')->check($data)) {
            throw new ValidationException($validate->getError());
        }

        $result = $this->couponService->create($data);
        return $this->success($result, '优惠券创建成功');
    }

    public function update(Request $request, $id)
    {
        $data = $request->post();

        $validate = new CouponValidate();
        $validate->failException(false);

        // 仅更新状态时使用 status 场景，避免 name 必填校验
        $scene = (count($data) === 1 && isset($data['status'])) ? 'status' : 'update';
        if (!$validate->scene($scene)->check($data)) {
            throw new ValidationException($validate->getError());
        }

        $result = $this->couponService->update($id, $data);
        return $this->success($result, '优惠券更新成功');
    }

    public function destroy(Request $request, $id)
    {
        $this->couponService->delete($id);
        return $this->success(null, '优惠券删除成功');
    }
}
