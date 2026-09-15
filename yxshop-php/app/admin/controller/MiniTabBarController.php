<?php

namespace app\admin\controller;

use support\Request;
use app\service\MiniTabBarService;

class MiniTabBarController extends BaseController
{
    protected $tabBarService;

    public function __construct()
    {
        parent::__construct();
        $this->tabBarService = new MiniTabBarService();
    }

    public function show(Request $request)
    {
        $appId = $this->getAppId($request);
        $tabBar = $this->tabBarService->getTabBar($appId);
        return $this->success($tabBar);
    }

    public function update(Request $request)
    {
        $appId = $this->getAppId($request);
        $data = $request->post();
        $tabBar = $this->tabBarService->saveTabBar($appId, $data);
        return $this->success($tabBar, '保存成功');
    }
}