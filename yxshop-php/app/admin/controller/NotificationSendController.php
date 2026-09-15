<?php

namespace app\admin\controller;

use support\Request;
use app\service\NotificationSendService;

class NotificationSendController extends BaseController
{
    protected $notificationSendService;

    public function __construct()
    {
        parent::__construct();
        $this->notificationSendService = new NotificationSendService();
    }

    public function index(Request $request)
    {
        $appId = $this->getAppId($request);
        $pageSize = (int) $request->get('page_size', 20);
        $keyword = $request->get('keyword', '');
        $result = $this->notificationSendService->getList($appId, $pageSize, $keyword);
        return $this->success($result);
    }

    public function store(Request $request)
    {
        $data = $request->post();
        $data['app_id'] = $this->getAppId($request);
        $result = $this->notificationSendService->create($data);
        return $this->success($result, '创建成功');
    }

    public function show(Request $request, $id)
    {
        $result = $this->notificationSendService->getDetail($id);
        if (!$result) {
            return $this->error('记录不存在');
        }
        return $this->success($result);
    }

    public function update(Request $request, $id)
    {
        $data = $request->post();
        $result = $this->notificationSendService->update($id, $data);
        return $this->success(null, '更新成功');
    }

    public function destroy(Request $request, $id)
    {
        $this->notificationSendService->delete($id);
        return $this->success(null, '删除成功');
    }
    public function detail(Request $request, $id)
    {
        $result = $this->notificationSendService->getDetail($id);
        return $this->success($result);
    }
}
