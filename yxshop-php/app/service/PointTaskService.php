<?php

namespace app\service;

use app\repository\PointTaskRepository;
use app\repository\UserPointTaskRepository;
use app\repository\SignRepository;
use app\repository\OrderRepository;
use app\repository\CommentRepository;
use app\model\PointTask;
use app\model\UserPointTask;
use app\exception\BusinessException;
use Exception;

/**
 * 积分任务服务
 *
 * @property PointTaskRepository $repository
 */
class PointTaskService extends BaseService
{
    private UserPointTaskRepository $userPointTaskRepository;
    private SignRepository $signRepository;
    private OrderRepository $orderRepository;
    private CommentRepository $commentRepository;
    private IntegralService $integralService;
    private UserService $userService;

    public function __construct(?PointTaskRepository $repository = null)
    {
        parent::__construct($repository ?? new PointTaskRepository());
        $this->userPointTaskRepository = new UserPointTaskRepository();
        $this->signRepository = new SignRepository();
        $this->orderRepository = new OrderRepository(new \app\model\Order());
        $this->commentRepository = new CommentRepository();
        $this->integralService = new IntegralService();
        $this->userService = new UserService();
    }

    /**
     * 获取积分任务列表（含完成状态）
     */
    public function getTaskList(int $userId, int $appId = 0)
    {
        $tasks = $this->repository->getActiveTasks($appId);
        $today = date('Y-m-d');
        $todayStart = strtotime($today . ' 00:00:00');
        $todayEnd = strtotime($today . ' 23:59:59');

        return $tasks->map(function ($task) use ($userId, $todayStart, $todayEnd) {
            // 查找用户完成记录
            $query = $this->userPointTaskRepository->query()
                ->where('user_id', $userId)
                ->where('task_id', $task->id);

            // 每日任务只查今天的记录
            if ($task->type == PointTask::TYPE_DAILY) {
                $query->whereBetween('completed_at', [$todayStart, $todayEnd]);
            }

            $userTask = $query->orderBy('id', 'desc')->first();

            $canClaim = false;
            $statusText = '未完成';

            if ($userTask) {
                if ($userTask->status == UserPointTask::STATUS_CLAIMED) {
                    $statusText = '已领取';
                    $canClaim = false;
                } elseif ($userTask->status == UserPointTask::STATUS_COMPLETED) {
                    $statusText = '待领取';
                    $canClaim = true;
                }
            } else {
                // 检查任务是否已完成但未记录
                $isCompleted = $this->checkTaskCompleted($task->code, $userId);
                if ($isCompleted) {
                    $statusText = '待领取';
                    $canClaim = true;
                }
            }

            return [
                'id'             => $task->id,
                'name'           => $task->name,
                'code'           => $task->code,
                'type'           => $task->type,
                'type_text'      => [1 => '每日', 2 => '一次性', 3 => '每周'][$task->type] ?? '每日',
                'reward_points'  => $task->reward_points,
                'condition_desc' => $task->condition_desc ?? '',
                'icon'           => $task->icon ?? '',
                'can_claim'      => $canClaim,
                'status_text'    => $statusText,
            ];
        });
    }

    /**
     * 领取任务奖励
     */
    public function claimReward(int $userId, $taskId)
    {
        $task = $this->repository->findActive($taskId);
        if (!$task) {
            throw new BusinessException('任务不存在或已关闭');
        }

        $user = $this->userService->findOrFail($userId);
        $appId = (int) ($user->app_id ?? 0);
        $today = date('Y-m-d');
        $todayStart = strtotime($today . ' 00:00:00');
        $todayEnd = strtotime($today . ' 23:59:59');

        // 查找是否已领取
        $query = $this->userPointTaskRepository->query()
            ->where('user_id', $userId)
            ->where('task_id', $taskId);

        if ($task->type == PointTask::TYPE_DAILY) {
            $query->whereBetween('completed_at', [$todayStart, $todayEnd]);
        }

        $existing = $query->orderBy('id', 'desc')->first();

        if ($existing && $existing->status == UserPointTask::STATUS_CLAIMED) {
            throw new BusinessException('奖励已领取，请勿重复操作');
        }

        // 校验任务是否已完成
        $isCompleted = $this->checkTaskCompleted($task->code, $userId);
        if (!$isCompleted) {
            throw new BusinessException('任务尚未完成');
        }

        // 创建或更新完成记录
        if ($existing && $existing->status == UserPointTask::STATUS_COMPLETED) {
            $existing->status = UserPointTask::STATUS_CLAIMED;
            $existing->claimed_at = time();
            $existing->save();
        } else {
            $this->userPointTaskRepository->create([
                'user_id'      => $userId,
                'task_id'      => $taskId,
                'status'       => UserPointTask::STATUS_CLAIMED,
                'completed_at' => time(),
                'claimed_at'   => time(),
                'app_id'       => $appId,
                'created_at'   => time(),
            ]);
        }

        // 发放积分
        if ($task->reward_points > 0) {
            $this->integralService->addIntegral(
                $userId,
                $task->reward_points,
                '任务奖励：' . $task->name,
                IntegralService::TYPE_EARN,
                0
            );
        }

        $newIntegral = (float) $this->userService->findOrFail($userId)->integral;

        return [
            'reward_points' => $task->reward_points,
            'new_integral'  => $newIntegral,
        ];
    }

    /**
     * 检查任务是否已完成
     */
    private function checkTaskCompleted(string $code, int $userId): bool
    {
        switch ($code) {
            case PointTask::CODE_SIGN:
                return $this->signRepository->getUserTodaySign($userId) !== null;

            case PointTask::CODE_PROFILE:
                $user = $this->userService->findOrFail($userId);
                return !empty($user->nickname) && !empty($user->avatar_url) && !empty($user->phone);

            case PointTask::CODE_FIRST_ORDER:
                return $this->orderRepository->query()
                    ->where('user_id', $userId)
                    ->where('pay_status', 1)
                    ->exists();

            case PointTask::CODE_REVIEW:
                return $this->commentRepository->query()
                    ->where('user_id', $userId)
                    ->exists();

            case PointTask::CODE_SHARE:
                return true;

            default:
                return false;
        }
    }
}
