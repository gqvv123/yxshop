<?php

namespace app\admin\controller;

use support\Request;
use app\service\CommentService;
use app\service\OrderService;
use app\service\UserService;

class CommentController extends BaseController
{
    protected $commentService;
    protected $userService;
    protected $orderService;

    public function __construct()
    {
        parent::__construct();
        $this->commentService = new CommentService();
        $this->userService = new UserService();
        $this->orderService = new OrderService();
    }

    public function index(Request $request)
    {
        $appId = $this->getAppId($request);
        $page = (int) $request->get('page', 1);
        $limit = (int) $request->get('page_size', 20);
        $rating = $request->get('rating', '');
        $status = $request->get('status', '');

        $comments = $this->commentService->getCommentList($page, $limit, [
            'rating' => $rating,
            'status' => $status,
            'app_id' => $appId
        ]);
        return $this->success($comments);
    }

    public function show(Request $request, $id)
    {
        $comment = $this->commentService->getCommentById($id);
        if (!$comment) {
            return $this->errorNotFound('评价记录不存在');
        }
        $user = $this->userService->findOrFail($comment->user_id);
        $order = $this->orderService->getAdminOrderDetails($comment->order_id);
        return $this->success(['comment' => $comment, 'user' => $user, 'order' => $order]);
    }

    public function reply(Request $request, $id)
    {
        $data = $request->post();
        $result = $this->commentService->replyComment($id, $data);
        if (!$result) {
            return $this->error('回复失败');
        }
        return $this->success($result, '回复成功');
    }

    public function updateStatus(Request $request, $id)
    {
        $status = $request->post('status');
        $result = $this->commentService->updateCommentStatus($id, $status);
        if (!$result) {
            return $this->error('更新失败');
        }
        return $this->success(null, '更新成功');
    }

    public function destroy(Request $request, $id)
    {
        $result = $this->commentService->deleteComment($id);
        if (!$result) {
            return $this->error('删除失败');
        }
        return $this->success(null, '删除成功');
    }

    public function stats(Request $request)
    {
        $appId = $this->getAppId($request);
        $stats = $this->commentService->getCommentStats($appId);
        return $this->success($stats);
    }

    public function export(Request $request)
    {
        $startDate = $request->get('start_date', '');
        $endDate = $request->get('end_date', '');
        $rating = $request->get('rating', '');
        $result = $this->commentService->exportComments($startDate, $endDate, $rating);
        if (!$result) {
            return $this->error('导出失败');
        }
        return $this->success($result, '导出成功');
    }
}
