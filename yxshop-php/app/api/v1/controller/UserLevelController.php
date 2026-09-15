<?php

namespace app\api\v1\controller;

use support\Request;
use app\service\UserService;
use app\service\UserLevelService;

/**
 * 用户等级
 *
 * 4 层架构：Controller -> Service -> Repository -> Model
 * 控制器禁止直接 use app\model\*
 */
class UserLevelController extends BaseController
{
    private UserService $userService;
    private UserLevelService $levelService;

    public function __construct()
    {
        $this->userService = new UserService();
        $this->levelService = new UserLevelService();
    }

    /**
     * 用户等级详情
     *
     * GET /api/v1/user/level
     * 返回：当前等级、下一等级、升级进度、全部等级体系、专属权益
     */
    public function detail(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }

        $user = $this->userService->find($userId);
        if (!$user) {
            return $this->error('用户不存在');
        }

        $appId = (int) ($user->app_id ?? 0);

        // 当前等级
        $currentLevel = $this->levelService->getByLevel($user->level ?? 1, $appId);

        if (!$currentLevel) {
            $currentLevel = $this->levelService->getDefaultLevel($appId);
        }

        // 下一等级
        $nextLevel = null;
        $allLevelsData = $this->levelService->getLevelList($appId);

        foreach ($allLevelsData as $level) {
            if (($currentLevel === null || $level->level > ($currentLevel->level ?? 0))) {
                if ($nextLevel === null || $level->level < $nextLevel->level) {
                    $nextLevel = $level;
                }
            }
        }

        // 全部等级体系
        $allLevels = $allLevelsData->map(function ($level) use ($currentLevel) {
            $isCurrent = $currentLevel && $level->id === $currentLevel->id;
            return [
                'id'          => $level->id,
                'name'        => $level->name,
                'level'       => $level->level,
                'experience'  => $level->experience,
                'discount'    => (float) $level->discount,
                'agio'        => (float) $level->agio,
                'description' => $level->description ?? '',
                'is_default'  => (int) $level->is_default,
                'is_current'  => $isCurrent,
            ];
        });

        // 升级进度计算
        $currentExp = $currentLevel->experience ?? 0;
        $nextExp = $nextLevel?->experience ?? $currentExp;
        $progress = 0;
        if ($nextLevel && $nextExp > $currentExp) {
            $userExp = (float) $user->total_spent;
            $progress = min(100, max(0, round(($userExp - $currentExp) / ($nextExp - $currentExp) * 100, 1)));
        } else {
            $progress = 100;
        }

        // 专属权益
        $benefits = $this->getLevelBenefits($currentLevel);

        return $this->success([
            'current_level' => [
                'id'          => $currentLevel?->id ?? 0,
                'name'        => $currentLevel?->name ?? '普通会员',
                'level'       => $currentLevel?->level ?? 0,
                'discount'    => (float) ($currentLevel?->discount ?? 10),
                'agio'        => (float) ($currentLevel?->agio ?? 10),
                'description' => $currentLevel?->description ?? '',
            ],
            'next_level' => $nextLevel ? [
                'id'          => $nextLevel->id,
                'name'        => $nextLevel->name,
                'level'       => $nextLevel->level,
                'experience'  => $nextLevel->experience,
                'discount'    => (float) $nextLevel->discount,
            ] : null,
            'progress'    => $progress,
            'user_exp'    => (float) $user->total_spent,
            'need_exp'    => $nextLevel ? max(0, $nextLevel->experience - (float) $user->total_spent) : 0,
            'all_levels'  => $allLevels,
            'benefits'    => $benefits,
        ]);
    }

    /**
     * 获取等级权益列表
     */
    private function getLevelBenefits($level): array
    {
        if (!$level) {
            return [];
        }

        $benefits = [];

        if ($level->discount < 10) {
            $benefits[] = [
                'icon'  => 'discount',
                'title' => '购物折扣',
                'desc'  => '享受 ' . $level->discount . ' 折优惠',
            ];
        }

        if ($level->agio > 0) {
            $benefits[] = [
                'icon'  => 'points',
                'title' => '积分加速',
                'desc'  => '购物积分 x' . $level->agio . ' 倍',
            ];
        }

        $benefits[] = [
            'icon'  => 'service',
            'title' => '专属客服',
            'desc'  => '优先客服服务',
        ];

        if ($level->level >= 2) {
            $benefits[] = [
                'icon'  => 'gift',
                'title' => '生日礼包',
                'desc'  => '生日月专属优惠券',
            ];
        }

        if ($level->level >= 3) {
            $benefits[] = [
                'icon'  => 'truck',
                'title' => '免运费',
                'desc'  => '全场免运费',
            ];
        }

        return $benefits;
    }
}
