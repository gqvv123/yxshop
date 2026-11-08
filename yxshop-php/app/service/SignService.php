<?php

namespace app\service;

use app\repository\SignRepository;
use app\model\Sign;
use app\exception\BusinessException;
use Exception;

/**
 * 签到服务类
 *
 * @property SignRepository $repository
 */
class SignService extends BaseService
{
    public function __construct(?SignRepository $repository = null)
    {
        parent::__construct($repository ?? new SignRepository());
    }

    /**
     * 用户签到
     */
    public function userSign($userId, $appId = 0)
    {
        try {
            $this->logInfo('用户签到开始', ['user_id' => $userId, 'app_id' => $appId]);

            // 检查今日是否已签到
            if ($this->repository->getUserTodaySign($userId, $appId)) {
                throw new BusinessException('今日已签到');
            }

            // 获取连续签到天数
            $continuousDays = $this->repository->getUserContinuousDays($userId, $appId);
            $newContinuousDays = $continuousDays + 1;

            // 计算签到积分
            $points = $this->calculateSignPoints($newContinuousDays);

            // 创建签到记录
            $sign = $this->repository->create([
                'user_id' => $userId,
                'sign_date' => date('Y-m-d'),
                'sign_points' => $points,
                'sign_continuous' => $newContinuousDays,
                'sign_total' => $this->getUserTotalSigns($userId, $appId) + 1,
                'sign_reward' => $this->getSignReward($newContinuousDays),
                'sign_status' => Sign::STATUS_NORMAL,
                'app_id' => $appId,
            ]);

            $this->logInfo('用户签到成功', ['sign_id' => $sign->sign_id, 'points' => $points]);
            return $sign;

        } catch (Exception $e) {
            $this->logError('用户签到失败', [
                'user_id' => $userId,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 获取用户签到记录
     */
    public function getUserSigns($userId, $appId = 0, $limit = 30)
    {
        try {
            return $this->repository->getUserSigns($userId, $appId, $limit);

        } catch (Exception $e) {
            $this->logError('获取用户签到记录失败', [
                'user_id' => $userId,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 检查用户今日是否已签到
     */
    public function isUserSignedToday($userId, $appId = 0)
    {
        try {
            return $this->repository->getUserTodaySign($userId, $appId) !== null;

        } catch (Exception $e) {
            $this->logError('检查用户今日签到状态失败', [
                'user_id' => $userId,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * 获取用户连续签到天数
     */
    public function getUserContinuousDays($userId, $appId = 0)
    {
        try {
            return $this->repository->getUserContinuousDays($userId, $appId);

        } catch (Exception $e) {
            $this->logError('获取用户连续签到天数失败', [
                'user_id' => $userId,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * 获取签到统计
     */
    public function getSignStats($startDate = null, $endDate = null, $appId = 0)
    {
        try {
            return $this->repository->getSignStats($startDate, $endDate, $appId);

        } catch (Exception $e) {
            $this->logError('获取签到统计失败', [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 获取用户签到统计
     */
    public function getUserSignStats($userId, $appId = 0)
    {
        try {
            return $this->repository->getUserSignStats($userId, $appId);

        } catch (Exception $e) {
            $this->logError('获取用户签到统计失败', [
                'user_id' => $userId,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 计算签到积分
     */
    private function calculateSignPoints($continuousDays)
    {
        // 基础积分
        $basePoints = 1;

        // 连续签到奖励
        if ($continuousDays >= 7) {
            return $basePoints + 5; // 连续7天额外奖励5积分
        } elseif ($continuousDays >= 3) {
            return $basePoints + 2; // 连续3天额外奖励2积分
        }

        return $basePoints;
    }

    /**
     * 获取签到奖励
     */
    private function getSignReward($continuousDays)
    {
        $rewards = [];

        // 连续签到奖励
        if ($continuousDays % 7 == 0) {
            $rewards[] = ['type' => 'coupon', 'value' => '签到7天奖励券'];
        }

        if ($continuousDays % 30 == 0) {
            $rewards[] = ['type' => 'points', 'value' => 50];
        }

        return $rewards;
    }

    /**
     * 获取用户总签到次数
     */
    private function getUserTotalSigns($userId, $appId = 0)
    {
        return $this->repository->countUserSigns($userId, $appId);
    }
}
