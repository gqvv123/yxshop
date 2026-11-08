<?php

namespace app\admin\controller;

use support\Request;
use app\service\MiniPageTemplateService;

class MiniPageTemplateController extends BaseController
{
    protected $templateService;

    public function __construct()
    {
        parent::__construct();
        $this->templateService = new MiniPageTemplateService();
    }

    public function index(Request $request)
    {
        $appId = $this->getAppId($request);
        $templates = $this->templateService->getTemplateList($appId);
        return $this->success($templates);
    }

    public function store(Request $request)
    {
        $data = $request->post();
        $data['app_id'] = $this->getAppId($request);
        $template = $this->templateService->createTemplate($data);
        return $this->success($template, '创建成功');
    }

    public function createPage(Request $request, $id)
    {
        $pageName = $request->post('page_name');
        $pageType = $request->post('page_type', 20);

        if (empty($pageName)) {
            return $this->error('页面名称不能为空');
        }

        $page = $this->templateService->createPageFromTemplate($id, $pageName, $pageType);
        return $this->success($page, '基于模板创建页面成功');
    }

    public function destroy(Request $request, $id)
    {
        $this->templateService->deleteTemplate($id);
        return $this->success(null, '删除成功');
    }

    /**
     * 获取内置文件模板列表
     */
    public function builtinTemplates(Request $request)
    {
        $templates = $this->templateService->getBuiltinTemplates();
        return $this->success($templates);
    }

    /**
     * 获取内置模板详情
     */
    public function builtinTemplateDetail(Request $request, $templateId)
    {
        $data = $this->templateService->getBuiltinTemplate($templateId);
        if (!$data) {
            return $this->error('模板不存在', 404);
        }
        return $this->success($data);
    }

    /**
     * 导入内置模板到数据库
     */
    public function importBuiltinTemplate(Request $request, $templateId)
    {
        $appId = $this->getAppId($request);
        $result = $this->templateService->importBuiltinTemplate($templateId, $appId);
        return $this->success($result, '导入成功');
    }

    /**
     * 直接从内置模板创建页面
     */
    public function createPageFromBuiltin(Request $request, $templateId)
    {
        $appId = $this->getAppId($request);
        $pageName = $request->post('page_name', '');
        $pageType = $request->post('page_type');

        $pageTypeVal = null;
        if ($pageType !== null && $pageType !== '') {
            $pageTypeVal = $this->templateService->normalizePageType($pageType);
        }

        $result = $this->templateService->createPageFromBuiltinTemplate($templateId, $appId, $pageName, $pageTypeVal);
        return $this->success($result, '从模板创建页面成功');
    }
}