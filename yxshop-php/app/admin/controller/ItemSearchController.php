<?php

namespace app\admin\controller;

use support\Request;
use app\service\ItemSearchService;

class ItemSearchController extends BaseController
{
    protected $itemSearchService;

    public function __construct()
    {
        parent::__construct();
        $this->itemSearchService = new ItemSearchService();
    }

    public function index(Request $request)
    {
        $appId = $this->getAppId($request);
        $pageSize = (int) $request->get('page_size', 20);
        $keyword = $request->get('keyword', '');
        $result = $this->itemSearchService->getList($appId, $pageSize, $keyword);
        return $this->success($result);
    }

    public function store(Request $request)
    {
        $data = $request->post();
        $data['app_id'] = $this->getAppId($request);
        $result = $this->itemSearchService->create($data);
        return $this->success($result, '创建成功');
    }

    public function show(Request $request, $id)
    {
        $result = $this->itemSearchService->getDetail($id);
        if (!$result) {
            return $this->error('记录不存在');
        }
        return $this->success($result);
    }

    public function update(Request $request, $id)
    {
        $data = $request->post();
        $result = $this->itemSearchService->update($id, $data);
        return $this->success(null, '更新成功');
    }

    public function destroy(Request $request, $id)
    {
        $this->itemSearchService->delete($id);
        return $this->success(null, '删除成功');
    }
    public function clear(Request $request)
    {
        $appId = $this->getAppId($request);
        $this->itemSearchService->clear($appId);
        return $this->success(null, '已清空');
    }
}
