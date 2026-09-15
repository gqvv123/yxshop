<?php

namespace app\admin\controller;

use support\Request;
use app\service\ItemAttributeService;

class ItemAttributeController extends BaseController
{
    protected $itemAttributeService;

    public function __construct()
    {
        parent::__construct();
        $this->itemAttributeService = new ItemAttributeService();
    }

    public function index(Request $request)
    {
        $appId = $this->getAppId($request);

        // 供商品编辑选择：GET /item-attributes?enabled=1
        if ($request->get('enabled')) {
            $result = $this->itemAttributeService->getEnabledList($appId);
            return $this->success($result);
        }

        $pageSize = (int) $request->get('page_size', 20);
        $keyword = $request->get('keyword', '');
        $result = $this->itemAttributeService->getList($appId, $pageSize, $keyword);
        return $this->success($result);
    }

    public function store(Request $request)
    {
        $data = $request->post();
        $data['app_id'] = $this->getAppId($request);
        $result = $this->itemAttributeService->create($data);
        return $this->success($result, '创建成功');
    }

    public function show(Request $request, $id)
    {
        $result = $this->itemAttributeService->getDetail($id);
        if (!$result) {
            return $this->error('记录不存在');
        }
        return $this->success($result);
    }

    public function update(Request $request, $id)
    {
        $data = $request->post();
        $result = $this->itemAttributeService->update($id, $data);
        return $this->success(null, '更新成功');
    }

    public function destroy(Request $request, $id)
    {
        $this->itemAttributeService->delete($id);
        return $this->success(null, '删除成功');
    }
}
