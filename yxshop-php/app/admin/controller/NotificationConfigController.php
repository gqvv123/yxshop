<?php

namespace app\admin\controller;

use support\Request;
use app\service\NotificationConfigService;

class NotificationConfigController extends BaseController
{
    protected $notificationConfigService;

    public function __construct()
    {
        parent::__construct();
        $this->notificationConfigService = new NotificationConfigService();
    }

    public function index(Request $request)
    {
        $appId = $this->getAppId($request);
        $pageSize = (int) $request->get('page_size', 20);
        $keyword = $request->get('keyword', '');
        $result = $this->notificationConfigService->getList($appId, $pageSize, $keyword);
        return $this->success($result);
    }

    public function store(Request $request)
    {
        $data = $request->post();
        $data['app_id'] = $this->getAppId($request);
        $result = $this->notificationConfigService->create($data);
        return $this->success($result, '创建成功');
    }

    public function show(Request $request, $id)
    {
        $result = $this->notificationConfigService->getDetail($id);
        if (!$result) {
            return $this->error('记录不存在');
        }
        return $this->success($result);
    }

    public function update(Request $request, $id)
    {
        $data = $request->post();
        $result = $this->notificationConfigService->update($id, $data);
        return $this->success(null, '更新成功');
    }

    public function destroy(Request $request, $id)
    {
        $this->notificationConfigService->delete($id);
        return $this->success(null, '删除成功');
    }
}
