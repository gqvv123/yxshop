<?php

namespace app\admin\controller;

use support\Request;
use app\service\AuthService;

class PermissionController extends BaseController
{

    public function index(Request $request)
    {
        $appId = $this->getAppId($request);
        $authTree = $this->authService->getAuthTree($appId);
        return $this->success($authTree);
    }

    public function tree(Request $request)
    {
        $appId = $this->getAppId($request);
        $authTree = $this->authService->getAuthTree($appId);
        return $this->success($authTree);
    }
}
