<?php

namespace app\admin\controller;

use support\Request;
use app\service\FileLogService;

class FileLogController extends BaseController
{
    protected $fileLogService;

    public function __construct()
    {
        parent::__construct();
        $this->fileLogService = new FileLogService();
    }

    public function index(Request $request)
    {
        $appId = $this->getAppId($request);
        $pageSize = (int) $request->get('page_size', 20);
        $keyword = $request->get('keyword', '');
        $result = $this->fileLogService->getList($appId, $pageSize, $keyword);
        return $this->success($result);
    }

    public function show(Request $request, $id)
    {
        $result = $this->fileLogService->getDetail($id);
        if (!$result) {
            return $this->error('记录不存在');
        }
        return $this->success($result);
    }
}
