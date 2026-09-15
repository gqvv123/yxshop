<?php

namespace app\api\v1\controller;

use support\Request;
use app\service\UserAddressService;
use app\service\RegionService;
use app\validate\UserAddressValidate;
use app\exception\ValidationException;

class AddressController extends BaseController
{
    /** @var UserAddressService */
    protected $addressService;

    /** @var RegionService */
    protected $regionService;

    public function __construct()
    {
        $this->addressService = new UserAddressService();
        $this->regionService = new RegionService();
    }

    /**
     * 获取地址列表
     */
    public function getList(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }
        $appId = (int) $request->get('app_id', 0);
        $list = $this->addressService->getUserAddresses($userId, $appId);
        return $this->success($list);
    }

    /**
     * 添加地址
     */
    public function add(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }

        // 兼容 JSON body 与 form-data
        $data = $request->post() ?: json_decode($request->rawBody(), true) ?: [];
        $data['user_id'] = $userId;
        if (empty($data['app_id'])) {
            $user = $request->user ?? null;
            $data['app_id'] = $user->app_id ?? 1;
        }

        // 小程序 picker 返回的是地区文本名称，需解析为 region_id
        $data = $this->regionService->resolveRegionIds($data);

        // 字段对齐 yxshop_user_addresses 表（B 方案）；
        // 业务规则交给 UserAddressValidate.scene('create') 校验
        $validate = new UserAddressValidate();
        if (!$validate->scene('create')->check($data)) {
            throw new ValidationException($validate->getError());
        }

        $address = $this->addressService->createUserAddress($data);
        return $this->success($address, '添加成功');
    }

    /**
     * 更新地址
     */
    public function update(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }
        $id = $request->post('id');
        if (!$id) {
            return $this->error('地址ID不能为空');
        }
        $data = $request->post();
        unset($data['id'], $data['user_id']);

        // 小程序 picker 返回的是地区文本名称，需解析为 region_id
        $data = $this->regionService->resolveRegionIds($data);

        $validate = new UserAddressValidate();
        if (!$validate->scene('update')->check($data)) {
            throw new ValidationException($validate->getError());
        }

        $this->addressService->updateUserAddress($id, $data, $userId);
        return $this->success(null, '更新成功');
    }

    /**
     * 删除地址
     */
    public function delete(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }
        $id = $request->post('id');
        if (!$id) {
            return $this->error('地址ID不能为空');
        }

        $this->addressService->deleteUserAddress($id, $userId);
        return $this->success(null, '删除成功');
    }

    /**
     * 设置默认地址
     */
    public function setDefault(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }
        $id = $request->post('id');
        if (!$id) {
            return $this->error('地址ID不能为空');
        }

        $this->addressService->setDefaultAddress($id, $userId);
        return $this->success(null, '设置成功');
    }
}
