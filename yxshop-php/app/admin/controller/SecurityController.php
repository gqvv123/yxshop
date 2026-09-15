<?php

namespace app\admin\controller;

use support\Request;
use support\Response;
use app\service\SecurityService;
use app\exception\BusinessException;

class SecurityController extends BaseController
{
    protected $securityService;

    public function __construct()
    {
        parent::__construct();
        $this->securityService = new SecurityService();
    }

    public function showSecurity(Request $request): Response
    {
        $config = $this->securityService->getSecurityConfig();
        return $this->success($config);
    }

    public function storeSecurity(Request $request): Response
    {
        $admin = $request->admin ?? null;
        if (!$admin || !$admin->isSuper()) {
            throw new BusinessException('仅超级管理员可修改安全策略');
        }

        $data = $request->post();
        $this->securityService->saveSecurityConfig($data, $admin->id);

        return $this->success(null, '安全策略已更新');
    }

    public function unlockAdmin(Request $request, $id): Response
    {
        $admin = $request->admin ?? null;
        if (!$admin || !$admin->isSuper()) {
            throw new BusinessException('仅超级管理员可解锁账号');
        }

        $this->securityService->unlockAdmin((int) $id, $admin->id);

        return $this->success(null, '账号已解锁');
    }
}