<?php

namespace app\api\v1\controller;

use support\Request;
use app\service\SignService;
use app\service\IntegralService;
use app\service\UserService;

/**
 * 签到
 *
 * 4 层架构：Controller -> Service -> Repository -> Model
 * 控制器禁止直接 use app\model\*
 */
class SignController extends BaseController
{
    protected $signService;
    protected $integralService;
    protected $userService;

    public function __construct()
    {
        $this->signService = new SignService();
        $this->integralService = new IntegralService();
        $this->userService = new UserService();
    }

    /**
     * 签到状态
     *
     * GET /api/v1/sign/status
     * 返回：今日是否已签到、连续天数、签到积分规则
     */
    public function status(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }

        $user = $this->userService->find($userId);
        $appId = (int) ($user->app_id ?? 0);

        $signedToday = $this->signService->isUserSignedToday($userId, $appId);
        $continuousDays = $this->signService->getUserContinuousDays($userId, $appId);
        $totalSigns = $this->signService->getUserSignStats($userId, $appId);

        // 签到积分规则
        $rules = [
            ['days' => 1,  'points' => 1,  'label' => '每日签到'],
            ['days' => 3,  'points' => 3,  'label' => '连续3天'],
            ['days' => 7,  'points' => 6,  'label' => '连续7天'],
            ['days' => 30, 'points' => 51, 'label' => '连续30天'],
        ];

        return $this->success([
            'signed_today'    => $signedToday,
            'continuous_days' => $continuousDays,
            'total_signs'     => $totalSigns['total_signs'] ?? 0,
            'total_points'    => $totalSigns['total_points'] ?? 0,
            'integral_balance'=> (float) ($user->integral ?? 0),
            'rules'           => $rules,
        ]);
    }

    /**
     * 执行签到
     *
     * POST /api/v1/sign/do
     */
    public function do(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }

        $user = $this->userService->find($userId);
        $appId = (int) ($user->app_id ?? 0);

        try {
            $sign = $this->signService->userSign($userId, $appId);

            // 发放签到积分到用户积分账户
            if ($sign->sign_points > 0) {
                $this->integralService->addIntegral(
                    $userId,
                    $sign->sign_points,
                    '签到奖励（连续' . $sign->sign_continuous . '天）',
                    IntegralService::TYPE_EARN,
                    0
                );
            }

            $newIntegral = $this->userService->find($userId)->integral ?? 0;

            return $this->success([
                'sign_id'         => $sign->sign_id ?? $sign->id,
                'sign_date'       => $sign->sign_date,
                'sign_points'     => $sign->sign_points,
                'sign_continuous' => $sign->sign_continuous,
                'sign_total'      => $sign->sign_total,
                'sign_reward'     => $sign->sign_reward,
                'new_integral'    => (float) $newIntegral,
            ], '签到成功，获得' . $sign->sign_points . '积分');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 签到记录（近30天日历）
     *
     * GET /api/v1/sign/records
     */
    public function records(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }

        $user = $this->userService->find($userId);
        $appId = (int) ($user->app_id ?? 0);

        $signs = $this->signService->getUserSigns($userId, $appId, 30);

        // 构建日历数据
        $calendar = [];
        $today = date('Y-m-d');
        for ($i = 29; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $calendar[] = [
                'date'      => $date,
                'is_today'  => $date === $today,
                'signed'    => false,
                'points'    => 0,
            ];
        }

        // 填入签到记录
        $signDates = [];
        foreach ($signs as $sign) {
            $dateStr = is_object($sign->sign_date) ? $sign->sign_date->format('Y-m-d') : date('Y-m-d', strtotime($sign->sign_date));
            $signDates[$dateStr] = $sign;
        }

        foreach ($calendar as &$day) {
            if (isset($signDates[$day['date']])) {
                $day['signed'] = true;
                $day['points'] = (int) $signDates[$day['date']]->sign_points;
            }
        }
        unset($day);

        return $this->success([
            'calendar'        => $calendar,
            'continuous_days' => $this->signService->getUserContinuousDays($userId, $appId),
            'total_signs'     => $this->signService->getUserSignStats($userId, $appId),
        ]);
    }
}
