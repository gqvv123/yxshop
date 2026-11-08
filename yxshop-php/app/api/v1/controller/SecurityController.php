<?php

namespace app\api\v1\controller;

use support\Request;
use app\service\UserService;
use app\exception\BusinessException;

/**
 * 账号安全
 *
 * 4 层架构：Controller -> Service -> Repository -> Model
 * 控制器禁止直接 use app\model\*
 */
class SecurityController extends BaseController
{
    private UserService $userService;

    public function __construct()
    {
        $this->userService = new UserService();
    }

    /**
     * 获取账号安全概览
     *
     * GET /api/v1/security/info
     */
    public function info(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }

        try {
            $data = $this->userService->getSecurityInfo($userId);
            return $this->success($data);
        } catch (BusinessException $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 注销账号（软删除）
     *
     * POST /api/v1/security/deactivate
     */
    public function deactivate(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }

        $password = (string) $request->post('password', '');
        if ($password === '') {
            return $this->error('请输入密码以确认身份');
        }

        try {
            $this->userService->deactivate($userId, $password);
            return $this->success(null, '账号已注销');
        } catch (BusinessException $e) {
            return $this->error($e->getMessage());
        }
    }
}
