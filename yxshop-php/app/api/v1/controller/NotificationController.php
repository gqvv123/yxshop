<?php

namespace app\api\v1\controller;

use support\Request;
use app\service\NotificationSendService;

class NotificationController extends BaseController
{
    /** @var NotificationSendService */
    protected $notificationService;

    public function __construct()
    {
        $this->notificationService = new NotificationSendService();
    }

    /**
     * 获取用户通知列表
     */
    public function getList(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }
        $page = (int) $request->get('page', 1);
        $pageSize = (int) $request->get('page_size', 20);
        return $this->success($this->notificationService->getUserInbox($userId, $page, $pageSize));
    }

    /**
     * 标记通知为已读
     */
    public function markRead(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }
        $id = $request->post('id');
        if (!$id) {
            return $this->error('通知ID不能为空');
        }
        $ok = $this->notificationService->markRead((int) $id, $userId);
        return $this->success(null, $ok ? '已标记已读' : '消息不存在或已读');
    }

    /**
     * 获取未读通知数量
     */
    public function getUnreadCount(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }
        return $this->success(['count' => $this->notificationService->getUserUnreadCount($userId)]);
    }

    /**
     * 批量标记为已读
     */
    public function markMultipleRead(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }
        $ids = (array) $request->post('ids', []);
        if (empty($ids)) {
            return $this->error('通知ID列表不能为空');
        }
        $affected = $this->notificationService->markMultipleRead($ids, $userId);
        return $this->success(['affected' => $affected], '已标记 ' . $affected . ' 条');
    }
}
