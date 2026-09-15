<?php

namespace app\admin\controller;

use support\Request;
use app\service\UserFeedbackService;

class UserFeedbackController extends BaseController
{
    protected $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new UserFeedbackService();
    }

    public function index(Request $request)
    {
        $appId = $this->getAppId($request);
        $filters = [];
        if ($request->get('user_id')) {
            $filters['user_id'] = $request->get('user_id');
        }
        if ($request->get('feedback_type')) {
            $filters['feedback_type'] = $request->get('feedback_type');
        }
        if ($request->get('status') !== null) {
            $filters['status'] = $request->get('status');
        }
        $pageSize = (int) $request->get('page_size', 20);
        $result = $this->service->getPaginatedList($appId, $filters, $pageSize);
        return $this->success($result);
    }

    public function show(Request $request, $id)
    {
        $result = $this->service->getDetail($id);
        if (!$result) {
            return $this->error('记录不存在');
        }
        return $this->success($result);
    }

    public function reply(Request $request, $id)
    {
        $replierId = $this->admin['id'] ?? 0;
        $replyContent = $request->post('reply_content', '');
        $result = $this->service->reply($id, $replierId, $replyContent);
        return $this->success($result, '回复成功');
    }

    public function close(Request $request, $id)
    {
        $result = $this->service->close($id);
        return $this->success($result, '关闭成功');
    }

    public function batchDelete(Request $request)
    {
        $ids = $request->post('ids', []);
        if (empty($ids) || !is_array($ids)) {
            return $this->error('请选择要删除的记录');
        }
        $count = 0;
        foreach ($ids as $id) {
            try {
                // 管理端删除反馈记录：复用 service 的删除逻辑
                $this->service->delete($id);
                $count++;
            } catch (\Throwable $e) {
                // 跳过不存在的记录
            }
        }
        return $this->success(['count' => $count], "成功删除{$count}条记录");
    }

    public function destroy(Request $request, $id)
    {
        try {
            $this->service->delete($id);
            return $this->success(null, '删除成功');
        } catch (\Throwable $e) {
            return $this->error('删除失败：' . $e->getMessage());
        }
    }
}
