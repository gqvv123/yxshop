<?php

namespace app\service;

use app\repository\UserRepository;
use app\repository\UserLevelRepository;
use app\repository\OrderRepository;
use app\exception\BusinessException;
use app\validate\UserValidate;
use Exception;

/**
 * 用户服务
 *
 * 4 层架构：所有数据访问通过 Repository 意图揭示方法；
 * 资金/积分变更委托到 UserMoneyService / IntegralService（原子化、含事务）。
 *
 * @property UserRepository $repository
 */
class UserService extends BaseService
{
    use UserFootprintTrait;

    use UserStatisticsTrait;

    use UserNotificationTrait;

    protected UserLevelRepository $levelRepository;
    protected UserMoneyService $moneyService;
    protected IntegralService $integralService;
    protected OrderRepository $orderRepo;

    public function __construct(?UserRepository $repository = null)
    {
        $repository = $repository ?? new UserRepository();
        parent::__construct($repository);
        $this->levelRepository = new UserLevelRepository();
        $this->moneyService = new UserMoneyService();
        $this->integralService = new IntegralService();
        $this->orderRepo = new OrderRepository();
    }

    // ============================================================
    // 注册 / 登录
    // ============================================================

    public function register(array $data)
    {
        try {
            $this->logInfo('用户注册开始', ['data' => $data]);
            $this->validateWith(UserValidate::class, 'create', $data);

            // 检查用户是否已存在
            $existingUser = $this->repository->findByOpenId($data['openid'] ?? '', $data['app_id'] ?? 0);
            if ($existingUser) {
                throw new BusinessException('用户已存在');
            }

            $userData = array_merge($data, [
                'created_at' => time(),
                'updated_at' => time(),
                'level_id'   => 1,
                'agio'       => 10.00,
                'is_dealer'  => 0,
            ]);

            $user = $this->create($userData);
            $this->sendRegisterNotification($user);

            $this->logInfo('用户注册成功', ['user_id' => $user->id]);
            return $user;
        } catch (Exception $e) {
            $this->logError('用户注册失败', ['data' => $data, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function login($openId, $appId, $ip = '')
    {
        try {
            $this->logInfo('用户登录开始', ['open_id' => $openId, 'app_id' => $appId]);

            $user = $this->repository->findByOpenId($openId, $appId);
            if (!$user) {
                throw new BusinessException('用户不存在');
            }
            if ($user->deleted_at > 0) {
                throw new BusinessException('用户已被禁用');
            }

            // 更新登录信息（更新 last_login_at/last_login_ip/login_count）
            $user->updateLoginInfo($ip);
            $this->sendLoginNotification($user);

            $this->logInfo('用户登录成功', ['user_id' => $user->id]);
            return $user;
        } catch (Exception $e) {
            $this->logError('用户登录失败', ['open_id' => $openId, 'app_id' => $appId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    // ============================================================
    // 资料 / 等级 / 分销
    // ============================================================

    public function updateProfile($userId, array $data)
    {
        try {
            $this->logInfo('更新用户信息开始', ['user_id' => $userId, 'data' => $data]);
            $this->validateWith(UserValidate::class, 'update', $data);

            $user = $this->findOrFail($userId);
            $oldData = $user->toArray();
            $user = $this->update($userId, $data);
            $this->logDataChange('yxshop_users', $userId, '修改', $oldData, $user->toArray());

            $this->logInfo('更新用户信息成功', ['user_id' => $userId]);
            return $user;
        } catch (Exception $e) {
            $this->logError('更新用户信息失败', ['user_id' => $userId, 'data' => $data, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function setLevel($userId, $levelId)
    {
        try {
            $this->logInfo('设置用户等级开始', ['user_id' => $userId, 'level_id' => $levelId]);

            $user = $this->findOrFail($userId);
            $level = $this->levelRepository->find($levelId);
            if (!$level) {
                throw new BusinessException('等级不存在');
            }

            $oldLevelId = $user->level_id;
            $user = $this->update($userId, ['level_id' => $levelId, 'agio' => $level->agio]);

            $this->logDataChange('yxshop_users', $userId, '修改',
                ['level_id' => $oldLevelId], ['level_id' => $levelId]);
            $this->sendLevelChangeNotification($user, $level);

            $this->logInfo('设置用户等级成功', ['user_id' => $userId, 'level_id' => $levelId]);
            return $user;
        } catch (Exception $e) {
            $this->logError('设置用户等级失败', ['user_id' => $userId, 'level_id' => $levelId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function setDealer($userId, $isDealer = 1)
    {
        try {
            $this->logInfo('设置分销商开始', ['user_id' => $userId, 'is_dealer' => $isDealer]);

            $user = $this->findOrFail($userId);
            $oldIsDealer = $user->is_dealer;
            $user = $this->update($userId, ['is_dealer' => $isDealer]);

            $this->logDataChange('yxshop_users', $userId, '修改',
                ['is_dealer' => $oldIsDealer], ['is_dealer' => $isDealer]);
            $this->sendDealerChangeNotification($user, $isDealer);

            $this->logInfo('设置分销商成功', ['user_id' => $userId, 'is_dealer' => $isDealer]);
            return $user;
        } catch (Exception $e) {
            $this->logError('设置分销商失败', ['user_id' => $userId, 'is_dealer' => $isDealer, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    // ============================================================
    // 积分 / 余额 —— 委托到 IntegralService / UserMoneyService
    // ============================================================

    /**
     * 增加积分（委托到 IntegralService，原子 increment + 事务 + 流水）
     */
    public function addIntegral($userId, $amount, $note = '', $type = 1, $orderId = 0)
    {
        try {
            $this->logInfo('增加用户积分开始', compact('userId', 'amount', 'note', 'type', 'orderId'));
            $log = $this->integralService->addIntegral($userId, $amount, $note, $type, $orderId);
            $this->logInfo('增加用户积分成功', ['user_id' => $userId, 'amount' => $amount, 'log_id' => $log->id ?? null]);
            return $this->findOrFail($userId);
        } catch (Exception $e) {
            $this->logError('增加用户积分失败', compact('userId', 'amount') + ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 减少积分（委托到 IntegralService，含余额校验）
     */
    public function reduceIntegral($userId, $amount, $note = '', $type = 2, $orderId = 0)
    {
        try {
            $this->logInfo('减少用户积分开始', compact('userId', 'amount', 'note', 'type', 'orderId'));
            $log = $this->integralService->reduceIntegral($userId, $amount, $note, $type, $orderId);
            $this->logInfo('减少用户积分成功', ['user_id' => $userId, 'amount' => $amount, 'log_id' => $log->id ?? null]);
            return $this->findOrFail($userId);
        } catch (Exception $e) {
            $this->logError('减少用户积分失败', compact('userId', 'amount') + ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 增加余额（委托到 UserMoneyService，原子 increment + 事务 + 流水）
     */
    public function addMoney($userId, $amount, $note = '')
    {
        try {
            $this->logInfo('增加用户余额开始', compact('userId', 'amount', 'note'));
            $user = $this->moneyService->addMoney($userId, $amount, $note);
            $this->logInfo('增加用户余额成功', ['user_id' => $userId, 'amount' => $amount, 'new_money' => $user->money]);
            return $user;
        } catch (Exception $e) {
            $this->logError('增加用户余额失败', compact('userId', 'amount', 'note') + ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 减少余额（委托到 UserMoneyService，含余额守卫防透支）
     */
    public function reduceMoney($userId, $amount, $note = '')
    {
        try {
            $this->logInfo('减少用户余额开始', compact('userId', 'amount', 'note'));
            $user = $this->moneyService->reduceMoney($userId, $amount, $note);
            $this->logInfo('减少用户余额成功', ['user_id' => $userId, 'amount' => $amount, 'new_money' => $user->money]);
            return $user;
        } catch (Exception $e) {
            $this->logError('减少用户余额失败', compact('userId', 'amount', 'note') + ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    // ============================================================
    // 报表 / 查询
    // ============================================================

    /**
     * 后台分页列表
     */
    public function getPaginatedList($appId, $pageSize = 20)
    {
        return $this->repository->getPaginatedByApp((int) $appId, (int) $pageSize);
    }

    /**
     * 用户总数
     */
    public function getCount($appId): int
    {
        return $this->repository->countByApp((int) $appId);
    }

    // ============================================================
    // 兼容旧 API Controller 的方法（v1/UserController 在用，返回数组契约）
    // ============================================================

    /**
     * 获取用户信息（v1 UserController::getInfo 调用）
     */
    public function getUserInfo($userId): array
    {
        try {
            $user = $this->findOrFail($userId);
            $data = $user->toArray();

            // Eloquent 的访问器不会自动进入 toArray()，这里显式补上个人中心需要的派生字段。
            // 之前缺 level_name 时，小程序 VIP 徽章会一直回退显示 "PRO"。
            $data['level_name'] = $user->level_name;
            $data['discount'] = (float) $user->discount;
            $data['is_vip'] = (int) ($user->level_id ?? 0) > 1;

            return ['success' => true, 'data' => $data, 'message' => 'ok'];
        } catch (Exception $e) {
            return ['success' => false, 'data' => null, 'message' => $e->getMessage()];
        }
    }

    /**
     * 更新用户信息（v1）
     */
    public function updateUserInfo($userId, array $data): array
    {
        try {
            $user = $this->updateProfile($userId, $data);
            return ['success' => true, 'data' => $user->toArray(), 'message' => '更新成功'];
        } catch (Exception $e) {
            return ['success' => false, 'data' => null, 'message' => $e->getMessage()];
        }
    }

    /**
     * 更新用户头像（v1）
     */
    public function updateUserAvatar($userId, $avatar): array
    {
        try {
            // 简化实现：avatar 形参既可能是 URL 字符串也可能是 UploadedFile，统一存 URL
            $avatarUrl = is_object($avatar) ? '' : (string) $avatar;
            if ($avatarUrl === '') {
                return ['success' => false, 'data' => null, 'message' => '头像不能为空'];
            }
            $user = $this->update($userId, ['avatar_url' => $avatarUrl]);
            return ['success' => true, 'data' => ['avatar_url' => $avatarUrl], 'message' => '更新成功'];
        } catch (Exception $e) {
            return ['success' => false, 'data' => null, 'message' => $e->getMessage()];
        }
    }

    /**
     * 修改密码（v1）
     */
    public function changePassword($userId, $oldPassword, $newPassword): array
    {
        try {
            $user = $this->findOrFail($userId);
            if (!empty($user->password) && !yxmall_pass_verify($oldPassword, $user->password)) {
                return ['success' => false, 'data' => null, 'message' => '原密码错误'];
            }
            if (strlen((string) $newPassword) < 6) {
                return ['success' => false, 'data' => null, 'message' => '密码长度至少 6 位'];
            }
            $this->update($userId, ['password' => yxmall_pass($newPassword)]);
            return ['success' => true, 'data' => null, 'message' => '密码修改成功'];
        } catch (Exception $e) {
            return ['success' => false, 'data' => null, 'message' => $e->getMessage()];
        }
    }

    /**
     * 获取用户订单（v1 UserController::getOrders 调用）
     */
    public function getUserOrders($userId, $page = 1, $pageSize = 20): array
    {
        try {
            $orderService = new OrderService();
            $list = $orderService->getOrderList((int) $userId, (int) $page, (int) $pageSize);
            return ['success' => true, 'data' => $list, 'message' => 'ok'];
        } catch (Exception $e) {
            return ['success' => false, 'data' => null, 'message' => $e->getMessage()];
        }
    }

    /**
     * 获取用户收藏（v1 UserController::getFavorites 调用）
     */
    public function getUserFavorites($userId, $page = 1, $pageSize = 20): array
    {
        try {
            $favRepo = new \app\repository\ItemFavoriteRepository();
            $list = $favRepo->getUserFavoritesPaginated((int) $userId, (int) $page, (int) $pageSize);
            return ['success' => true, 'data' => $list, 'message' => 'ok'];
        } catch (Exception $e) {
            return ['success' => false, 'data' => null, 'message' => $e->getMessage()];
        }
    }

    // ============================================================
    // 私有 - 通知 / 审计
    // ============================================================

    // ============================================================
    // C 端 — 个人中心聚合
    // ============================================================

    /**
     * 注销账号（软删除）
     */
    public function deactivate(int $userId, string $password): void
    {
        $user = $this->repository->find($userId);
        if (!$user) {
            throw new BusinessException('用户不存在');
        }

        if (!empty($user->password) && !yxmall_pass_verify($password, $user->password)) {
            throw new BusinessException('密码错误，无法注销');
        }

        $user->deleted_at = time();
        $user->save();

        $this->logDataChange('yxshop_users', $userId, 'deactivate', null, null);
    }

    /**
     * 获取用户详情（含推荐人信息和佣金统计）
     */
    public function getDetailWithRelations($id): array
    {
        $user = $this->findOrFail($id);
        $userData = $user->toArray();

        // 附加推荐人信息
        if (!empty($user->referrer_id)) {
            $referrer = $this->repository->find($user->referrer_id);
            $userData['referrer_info'] = $referrer ? [
                'id' => (string) $referrer->id,
                'nickname' => $referrer->nickname,
                'avatar_url' => $referrer->avatar_url,
                'phone' => $referrer->phone,
            ] : null;
        } else {
            $userData['referrer_info'] = null;
        }

        // 附加推荐人数
        $userData['referral_count'] = $this->repository->query()->where('referrer_id', $id)->count();
        $userData['referral_list'] = $this->repository->query()
            ->where('referrer_id', $id)
            ->select(['id', 'nickname', 'avatar_url', 'phone', 'created_at'])
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->toArray();

        // 附加分销佣金统计（商业版功能，开源版安全跳过）
        if (class_exists('\commercial\distribution\Repository\OrderAgentRepository')) {
            $orderAgentRepo = new \commercial\distribution\Repository\OrderAgentRepository();
            $userData['commission_stats'] = [
                'total' => $orderAgentRepo->sum('profit_money', ['user_id' => $id]),
                'pending' => $orderAgentRepo->sum('profit_money', ['user_id' => $id, 'confirm' => \commercial\distribution\Model\OrderAgent::STATUS_PENDING]),
                'settled' => $orderAgentRepo->sum('profit_money', ['user_id' => $id, 'confirm' => \commercial\distribution\Model\OrderAgent::STATUS_SETTLED]),
            ];
        }

        return $userData;
    }

    /**
     * 获取分页列表（含推荐人信息和统计）
     */
    public function getPaginatedListWithRelations(int $appId, int $pageSize = 20)
    {
        $users = $this->repository->getPaginatedByApp($appId, $pageSize);

        $userIdArr = $users->pluck('id')->toArray();
        $referrerIds = $users->pluck('referrer_id')->filter()->unique()->toArray();

        // 批量查推荐人
        $referrers = [];
        if (!empty($referrerIds)) {
            $referrerModels = $this->repository->query()
                ->whereIn('id', $referrerIds)
                ->get(['id', 'nickname', 'avatar_url', 'phone']);
            foreach ($referrerModels as $r) {
                $referrers[$r->id] = [
                    'id' => (string) $r->id,
                    'nickname' => $r->nickname,
                    'avatar_url' => $r->avatar_url,
                    'phone' => $r->phone,
                ];
            }
        }

        // 批量统计每个用户的推荐人数
        $referralCounts = $this->repository->query()
            ->selectRaw('referrer_id, COUNT(*) as cnt')
            ->whereIn('referrer_id', $userIdArr)
            ->groupBy('referrer_id')
            ->pluck('cnt', 'referrer_id')
            ->toArray();

        $items = $users->getCollection()->map(function ($user) use ($referrers, $referralCounts) {
            $arr = $user->toArray();
            $arr['referrer_info'] = $referrers[$user->referrer_id] ?? null;
            $arr['referral_count'] = $referralCounts[$user->id] ?? 0;
            return $arr;
        });

        $users->setCollection($items);

        return $users;
    }
}
