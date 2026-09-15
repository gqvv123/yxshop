<?php

namespace app\controller;

use support\Request;
use support\Response;
use app\service\UserAddressService;
use app\exception\BusinessException;

/**
 * 用户地址 Web 端控制器（与 api/v1/AddressController 区分：本控制器走 web 模板）
 * 字段对齐 yxshop_user_addresses 表（B 方案：name/phone/province_id/city_id/district_id/detail）。
 */
class UserAddressController extends BaseController
{
    /** @var UserAddressService */
    protected $addressService;

    public function __construct()
    {
        $this->addressService = new UserAddressService();
    }

    public function list(Request $request): Response
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            throw new BusinessException('用户未登录');
        }
        return $this->success($this->addressService->getUserAddresses($userId, $this->getAppId($request)));
    }

    public function getDefault(Request $request): Response
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            throw new BusinessException('用户未登录');
        }
        return $this->success($this->addressService->getDefaultAddress($userId, $this->getAppId($request)));
    }

    public function store(Request $request): Response
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            throw new BusinessException('用户未登录');
        }
        $data = $request->post();
        $data['user_id'] = $userId;
        if (empty($data['app_id'])) {
            $data['app_id'] = $this->getAppId($request);
        }
        $address = $this->addressService->createUserAddress($data);
        return $this->success(['id' => $address->id], '创建成功');
    }

    public function update(Request $request, $id): Response
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            throw new BusinessException('用户未登录');
        }
        $data = $request->post();
        unset($data['id'], $data['user_id']); // 防越权改 user_id
        $this->addressService->updateUserAddress($id, $data, $userId);
        return $this->success(null, '更新成功');
    }

    public function destroy(Request $request, $id): Response
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            throw new BusinessException('用户未登录');
        }
        $this->addressService->deleteUserAddress($id, $userId);
        return $this->success(null, '删除成功');
    }

    public function setDefault(Request $request): Response
    {
        $userId = $this->getCurrentUserId($request);
        $addressId = $request->post('address_id') ?? $request->post('id');
        if (!$userId || !$addressId) {
            throw new BusinessException('用户ID和地址ID不能为空');
        }
        $this->addressService->setDefaultAddress($addressId, $userId);
        return $this->success(null, '设置成功');
    }
}
