<?php

namespace app\admin\controller;

use support\Request;
use app\service\NotificationTemplateService;

class NotificationTemplateController extends BaseController
{
    protected $templateService;

    public function __construct()
    {
        parent::__construct();
        $this->templateService = new NotificationTemplateService();
    }

    public function index(Request $request)
    {
        $appId = $this->getAppId($request);
        $page = (int) $request->get('page', 1);
        $limit = (int) $request->get('page_size', 20);
        $templateName = $request->get('template_name', '');
        $templateType = $request->get('template_type', '');

        $templates = $this->templateService->getTemplateList($page, $limit, [
            'template_name' => $templateName,
            'template_type' => $templateType,
            'app_id' => $appId
        ]);
        return $this->success($templates);
    }

    public function show(Request $request, $id)
    {
        $template = $this->templateService->getTemplateById($id);
        if (!$template) {
            return $this->errorNotFound('通知模板不存在');
        }
        return $this->success($template);
    }

    public function store(Request $request)
    {
        $data = $request->post();
        $data['app_id'] = $this->getAppId($request);
        $result = $this->templateService->createTemplate($data);
        if (!$result) {
            return $this->error($this->templateService->getError() ?: '添加失败');
        }
        return $this->success($result, '添加成功');
    }

    public function update(Request $request, $id)
    {
        $data = $request->post();
        $result = $this->templateService->updateTemplate($id, $data);
        if (!$result) {
            return $this->error($this->templateService->getError() ?: '更新失败');
        }
        return $this->success($result, '更新成功');
    }

    public function destroy(Request $request, $id)
    {
        $result = $this->templateService->deleteTemplate($id);
        if (!$result) {
            return $this->error($this->templateService->getError() ?: '删除失败');
        }
        return $this->success(null, '删除成功');
    }

    public function updateStatus(Request $request, $id)
    {
        $status = $request->post('status');
        $result = $this->templateService->updateTemplateStatus($id, $status);
        if (!$result) {
            return $this->error($this->templateService->getError() ?: '更新失败');
        }
        return $this->success(null, '更新成功');
    }

    public function preview(Request $request, $id)
    {
        $template = $this->templateService->getTemplateById($id);
        if (!$template) {
            return $this->errorNotFound('通知模板不存在');
        }
        return $this->success($template);
    }

    public function test(Request $request, $id)
    {
        $testData = $request->post('test_data', []);
        $result = $this->templateService->testTemplate($id, $testData);
        if (!$result) {
            return $this->error($this->templateService->getError() ?: '测试发送失败');
        }
        return $this->success(null, '测试发送成功');
    }
}
