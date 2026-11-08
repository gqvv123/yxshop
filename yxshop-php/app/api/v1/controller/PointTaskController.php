<?php

namespace app\api\v1\controller;

use support\Request;
use app\service\PointTaskService;
use app\service\UserService;

class PointTaskController extends BaseController
{
    protected $pointTaskService;
    protected $userService;

    public function __construct()
    {
        $this->pointTaskService = new PointTaskService();
        $this->userService = new UserService();
    }

    /**
     * 积分任务列表（含完成状态）
     *
     * GET /api/v1/point/tasks
     */
    public function list(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }

        $user = $this->userService->findOrFail($userId);
        $appId = (int) ($user->app_id ?? 0);

        $list = $this->pointTaskService->getTaskList($userId, $appId);

        return $this->success([
            'list'  => $list,
            'total' => $list->count(),
        ]);
    }

    /**
     * 领取任务奖励
     *
     * POST /api/v1/point/tasks/claim  task_id=1
     */
    public function claim(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }

        $taskId = $request->post('task_id', '');
        if (empty($taskId)) {
            return $this->error('任务ID不能为空');
        }

        try {
            $result = $this->pointTaskService->claimReward($userId, $taskId);

            return $this->success($result, '领取成功，获得' . $result['reward_points'] . '积分');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
