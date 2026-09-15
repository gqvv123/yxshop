<?php

namespace app\admin\controller;

use support\Request;
use app\service\RegionService;

class RegionController extends BaseController
{
    protected $regionService;

    public function __construct()
    {
        parent::__construct();
        $this->regionService = new RegionService();
    }

    public function index(Request $request)
    {
        $appId = $this->getAppId($request);
        $pageSize = (int) $request->get('page_size', 20);
        $keyword = $request->get('keyword', '');
        $result = $this->regionService->getList($appId, $pageSize, $keyword);
        return $this->success($result);
    }

    public function store(Request $request)
    {
        $data = $request->post();
        $data['app_id'] = $this->getAppId($request);
        $result = $this->regionService->create($data);
        return $this->success($result, '创建成功');
    }

    public function show(Request $request, $id)
    {
        $result = $this->regionService->find($id);
        if (!$result) {
            return $this->error('记录不存在');
        }
        return $this->success($result);
    }

    public function update(Request $request, $id)
    {
        $data = $request->post();
        $result = $this->regionService->update($id, $data);
        return $this->success(null, '更新成功');
    }

    public function destroy(Request $request, $id)
    {
        $this->regionService->delete($id);
        return $this->success(null, '删除成功');
    }
    public function tree(Request $request)
    {
        $parentId = $request->get('parent_id', 0);
        $tree = $this->regionService->getRegionTree($parentId);
        return $this->success($tree);
    }
}
