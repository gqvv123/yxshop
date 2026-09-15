<?php

namespace app\admin\controller;

use support\Request;
use app\service\PageSeoService;

class PageSeoController extends BaseController
{
    protected $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new PageSeoService();
    }

    public function index(Request $request)
    {
        $appId = $this->getAppId($request);
        $filters = [];
        if ($request->get('page_key')) {
            $filters['page_key'] = $request->get('page_key');
        }
        if ($request->get('lang_code')) {
            $filters['lang_code'] = $request->get('lang_code');
        }
        $pageSize = (int) $request->get('page_size', 20);
        $result = $this->service->getPaginatedList($appId, $filters, $pageSize);
        return $this->success($result);
    }

    public function store(Request $request)
    {
        $data = $request->post();
        $data['app_id'] = $this->getAppId($request);
        $result = $this->service->create($data);
        return $this->success($result, '创建成功');
    }

    public function show(Request $request, $id)
    {
        $result = $this->service->getDetail($id);
        if (!$result) {
            return $this->error('记录不存在');
        }
        return $this->success($result);
    }

    public function update(Request $request, $id)
    {
        $data = $request->post();
        $result = $this->service->update($id, $data);
        return $this->success($result, '更新成功');
    }

    public function destroy(Request $request, $id)
    {
        $this->service->delete($id);
        return $this->success(null, '删除成功');
    }

    public function upsert(Request $request)
    {
        $pageKey = $request->post('page_key', '');
        $langCode = $request->post('lang_code', 'zh-CN');
        $data = $request->post();
        $appId = $this->getAppId($request);
        $result = $this->service->upsert($pageKey, $langCode, $data, $appId);
        return $this->success($result, '保存成功');
    }
}
