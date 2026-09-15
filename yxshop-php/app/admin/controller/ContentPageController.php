<?php

namespace app\admin\controller;

use support\Request;
use app\service\ContentPageService;

class ContentPageController extends BaseController
{
    protected $contentPageService;

    public function __construct()
    {
        parent::__construct();
        $this->contentPageService = new ContentPageService();
    }

    public function index(Request $request)
    {
        $appId = $this->getAppId($request);
        $pageSize = (int) $request->get('page_size', 20);
        $keyword = $request->get('keyword', '');
        $pageType = $request->get('page_type', '');
        $result = $this->contentPageService->getList($appId, $pageSize, $keyword, $pageType);
        return $this->success($result);
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $data['app_id'] = $this->getAppId($request);
        $result = $this->contentPageService->create($data);
        return $this->success($result, '创建成功');
    }

    public function show(Request $request, $id)
    {
        $result = $this->contentPageService->getDetail($id);
        if (!$result) {
            return $this->error('记录不存在');
        }
        return $this->success($result);
    }

    public function update(Request $request, $id)
    {
        $data = $request->all();
        $result = $this->contentPageService->update($id, $data);
        return $this->success(null, '更新成功');
    }

    public function destroy(Request $request, $id)
    {
        $this->contentPageService->delete($id);
        return $this->success(null, '删除成功');
    }
}