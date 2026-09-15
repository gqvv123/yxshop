<?php

namespace app\controller;

use support\Request;
use support\Response;
use app\service\ThemeService;
use app\exception\BusinessException;
use app\exception\NotFoundException;

class ThemeController extends BaseController
{
    protected $themeService;

    public function __construct()
    {
        $this->themeService = new ThemeService();
    }

    public function list(Request $request): Response
    {
        $themes = $this->themeService->getList();
        return $this->success($themes);
    }

    public function current(Request $request): Response
    {
        $appId = $this->getAppId($request);
        $current = $this->themeService->getCurrent($appId);
        return $this->success($current);
    }

    public function apply(Request $request): Response
    {
        $data = $request->post();
        $themeId = $data['theme_id'] ?? null;
        if (!$themeId) {
            throw new BusinessException('主题ID不能为空');
        }
        $this->themeService->apply((int)$themeId, 0);
        return $this->success(['theme_id' => $themeId], '主题应用成功');
    }

    public function custom(Request $request): Response
    {
        $data = $request->post();
        $appId = $this->getAppId($request);
        if (empty($data['name'])) {
            throw new BusinessException('主题名称不能为空');
        }
        $this->themeService->custom($data, $appId);
        return $this->success(null, '自定义主题保存成功');
    }

    public function delete(Request $request, $id): Response
    {
        $this->themeService->delete((int)$id);
        return $this->success(null, '删除成功');
    }
}
