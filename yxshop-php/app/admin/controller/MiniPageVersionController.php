<?php

namespace app\admin\controller;

use support\Request;
use app\service\MiniPageVersionService;

class MiniPageVersionController extends BaseController
{
    protected $versionService;

    public function __construct()
    {
        parent::__construct();
        $this->versionService = new MiniPageVersionService();
    }

    public function index(Request $request, $id)
    {
        $page = (int) $request->input('page', 1);
        $pageSize = (int) $request->input('page_size', 10);
        $result = $this->versionService->getVersionList($id, $page, $pageSize);
        return $this->paginate($result);
    }

    public function show(Request $request, $id, $versionId)
    {
        $version = $this->versionService->getVersionDetail($id, $versionId);
        return $this->success($version);
    }

    public function rollback(Request $request, $id, $versionId)
    {
        $page = $this->versionService->rollbackVersion($id, $versionId);
        return $this->success($page, '回滚成功，页面已进入编辑态');
    }
}