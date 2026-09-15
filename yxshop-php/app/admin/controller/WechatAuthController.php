<?php

namespace app\admin\controller;

use support\Request;
use support\Response;
use app\service\WechatAuthService;
use app\exception\BusinessException;

class WechatAuthController extends BaseController
{
    protected $wechatAuthService;

    public function __construct()
    {
        parent::__construct();
        $this->wechatAuthService = new WechatAuthService();
    }

    public function qrcode(Request $request): Response
    {
        try {
            $result = $this->wechatAuthService->generateQrCode('admin_login');
            return $this->success($result);
        } catch (BusinessException $e) {
            return $this->error($e->getMessage(), $e->getCode() ?: -1);
        }
    }

    public function scanStatus(Request $request): Response
    {
        $sceneKey = $request->get('scene_key', '');
        if (empty($sceneKey)) {
            throw new BusinessException('参数错误');
        }

        $result = $this->wechatAuthService->pollScanStatus($sceneKey);
        return $this->success($result);
    }

    public function bindStatus(Request $request): Response
    {
        $admin = $request->admin ?? null;
        if (!$admin) {
            throw new BusinessException('未登录');
        }

        $result = $this->wechatAuthService->getBindStatus($admin->id);
        return $this->success($result);
    }

    public function bindQrcode(Request $request): Response
    {
        $admin = $request->admin ?? null;
        if (!$admin) {
            throw new BusinessException('未登录');
        }

        $password = $request->post('password', '');
        if (empty($password)) {
            throw new BusinessException('请输入密码');
        }

        $result = $this->wechatAuthService->bindWechat($admin->id, $password);
        return $this->success($result);
    }

    public function bindStatusPoll(Request $request): Response
    {
        $sceneKey = $request->get('scene_key', '');
        if (empty($sceneKey)) {
            throw new BusinessException('参数错误');
        }

        $result = $this->wechatAuthService->pollScanStatus($sceneKey);
        return $this->success($result);
    }

    public function unbind(Request $request): Response
    {
        $admin = $request->admin ?? null;
        if (!$admin) {
            throw new BusinessException('未登录');
        }

        $password = $request->post('password', '');
        if (empty($password)) {
            throw new BusinessException('请输入密码');
        }

        $this->wechatAuthService->unbindWechat($admin->id, $password);
        return $this->success(null, '解绑成功');
    }
}