<?php

namespace app\api\v1\controller;

use support\Request;
use app\service\UserFeedbackService;
use app\service\UserService;
use app\model\UserFeedback;

class FeedbackController extends BaseController
{
    protected $feedbackService;
    protected $userService;

    public function __construct()
    {
        $this->feedbackService = new UserFeedbackService();
        $this->userService = new UserService();
    }

    /**
     * 提交意见反馈
     *
     * POST /api/v1/feedback/submit
     * feedback_type=1, content=xxx, contact=xxx, images=xxx
     */
    public function submit(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }

        $content = trim($request->post('content', ''));
        if ($content === '') {
            return $this->error('反馈内容不能为空');
        }

        $user = $this->userService->findOrFail($userId);

        try {
            $feedback = $this->feedbackService->create([
                'user_id'       => $userId,
                'feedback_type' => (int) $request->post('feedback_type', UserFeedback::TYPE_SUGGESTION),
                'content'       => $content,
                'contact'       => $request->post('contact', ''),
                'images'        => $request->post('images', ''),
                'status'        => UserFeedback::STATUS_PENDING,
                'app_id'        => $user->app_id ?? 0,
            ]);

            return $this->success(['id' => $feedback->id], '提交成功，我们会尽快处理');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 我的反馈列表
     *
     * GET /api/v1/feedback/my-list?page=1&page_size=20
     */
    public function myList(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }

        $page = max(1, (int) $request->get('page', 1));
        $pageSize = min(50, max(1, (int) $request->get('page_size', 20)));

        $result = $this->feedbackService->getMyListPaginated($userId, $page, $pageSize);

        $items = collect($result->items())->map(function ($fb) {
            return [
                'id'             => $fb->id,
                'feedback_type'  => $fb->feedback_type,
                'type_text'      => [
                    UserFeedback::TYPE_SUGGESTION => '建议',
                    UserFeedback::TYPE_BUG        => '故障',
                    UserFeedback::TYPE_COMPLAINT  => '投诉',
                    UserFeedback::TYPE_OTHER      => '其他',
                ][$fb->feedback_type] ?? '其他',
                'content'        => $fb->content,
                'images'         => $fb->images,
                'reply_content'  => $fb->reply_content ?? '',
                'status'         => $fb->status,
                'status_text'    => [
                    UserFeedback::STATUS_PENDING  => '待处理',
                    UserFeedback::STATUS_REPLIED  => '已回复',
                    UserFeedback::STATUS_CLOSED   => '已关闭',
                ][$fb->status] ?? '未知',
                'created_at'     => $fb->created_at,
            ];
        })->values();

        return $this->success([
            'list'      => $items,
            'total'     => $result->total(),
            'page'      => $result->currentPage(),
            'page_size' => $result->perPage(),
            'last_page' => $result->lastPage(),
        ]);
    }
}
