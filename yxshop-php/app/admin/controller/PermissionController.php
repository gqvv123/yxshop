<?php

namespace app\admin\controller;

use support\Request;

/**
 * 管理端控制器：Permission Controller。
 *
 * @package app\admin\controller
 */
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
