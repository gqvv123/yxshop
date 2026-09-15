<?php

namespace app\admin\controller;

use support\Request;
use app\service\MiniThemeService;

class MiniThemeController extends BaseController
{
    protected $themeService;

    public function __construct()
    {
        parent::__construct();
        $this->themeService = new MiniThemeService();
    }

    public function index(Request $request)
    {
        $appId = $this->getAppId($request);
        $themes = $this->themeService->getThemeList($appId);
        return $this->success($themes);
    }

    public function store(Request $request)
    {
        $data = $request->post();
        $data['app_id'] = $this->getAppId($request);
        $theme = $this->themeService->createTheme($data);
        return $this->success($theme, '创建成功');
    }

    public function update(Request $request, $id)
    {
        $data = $request->post();
        $theme = $this->themeService->updateTheme($id, $data);
        return $this->success($theme, '更新成功');
    }

    public function destroy(Request $request, $id)
    {
        $this->themeService->deleteTheme($id);
        return $this->success(null, '删除成功');
    }

    public function apply(Request $request, $id)
    {
        $pageId = $request->post('page_id');
        if (empty($pageId)) {
            return $this->error('页面ID不能为空');
        }
        $page = $this->themeService->applyTheme($id, $pageId);
        return $this->success($page, '应用成功');
    }
}