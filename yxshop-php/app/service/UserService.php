<?php

namespace app\service;

use app\repository\UserRepository;
use app\repository\UserLevelRepository;
use app\repository\OrderRepository;
use app\model\User;
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
    protected UserLevelRepository $levelRepository;
    protected UserMoneyService $moneyService;
    protected IntegralService $integralService;

    public function __construct(?UserRepository $repository = null)
    {
        $repository = $repository ?? new UserRepository();
        parent::__construct($repository);
        $this->levelRepository = new UserLevelRepository();
        $this->moneyService = new UserMoneyService();
        $this->integralService = new IntegralService();
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
     * 用户综合统计（消费/推荐/收藏/浏览/搜索/评价/优惠券）
     *
     * 多源聚合，无法纯下沉到单一 Repository，仍在 Service 层协调；
     * 关联 count 走 User 模型已定义的 hasMany 关系（Eloquent 默认走索引）。
     */
    public function getUserStats($userId)
    {
        try {
            $user = $this->findOrFail($userId);

            return [
                'user_info'       => $user->toArray(),
                'consumption'     => $this->repository->getUserConsumptionStats($userId, $user->app_id),
                'referral'        => $this->repository->getUserReferralStats($userId, $user->app_id),
                'favorites_count' => $user->favorites()->count(),
                'views_count'     => $user->views()->count(),
                'searches_count'  => $user->searches()->count(),
                'comments_count'  => $user->comments()->count(),
                'coupons_count'   => $user->coupons()->count(),
            ];
        } catch (Exception $e) {
            $this->logError('获取用户统计信息失败', ['user_id' => $userId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 推荐人下级列表（指定层级）
     */
    public function getReferralList($userId, $level = 1, $page = 1, $limit = 15)
    {
        try {
            $this->findOrFail($userId);  // 校验推荐人存在
            return $this->repository->getReferralsPaginated((int) $userId, (int) $level, (int) $page, (int) $limit);
        } catch (Exception $e) {
            $this->logError('获取用户推荐列表失败',
                compact('userId', 'level', 'page', 'limit') + ['error' => $e->getMessage()]);
            throw $e;
        }
    }

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
            return ['success' => true, 'data' => $user->toArray(), 'message' => 'ok'];
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

    private function sendRegisterNotification(User $user): void
    {
        try {
            // TODO: 接入 NotificationSendService 后调用真实通知
            $this->logInfo('发送注册通知', ['user_id' => $user->id]);
        } catch (Exception $e) {
            $this->logError('发送注册通知失败', ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }
    }

    private function sendLoginNotification(User $user): void
    {
        try {
            $this->logInfo('发送登录通知', ['user_id' => $user->id]);
        } catch (Exception $e) {
            $this->logError('发送登录通知失败', ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }
    }

    private function sendLevelChangeNotification(User $user, $level): void
    {
        try {
            $this->logInfo('发送等级变更通知', ['user_id' => $user->id, 'level_id' => $level->id]);
        } catch (Exception $e) {
            $this->logError('发送等级变更通知失败', ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }
    }

    private function sendDealerChangeNotification(User $user, $isDealer): void
    {
        try {
            $this->logInfo('发送分销商变更通知', ['user_id' => $user->id, 'is_dealer' => $isDealer]);
        } catch (Exception $e) {
            $this->logError('发送分销商变更通知失败', ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }
    }

    // ============================================================
    // C 端 — 个人中心聚合
    // ============================================================

    /**
     * 用户综合信息（个人中心首页聚合）
     *
     * 聚合：基础信息 + 等级 + 余额 + 积分 + 优惠券数 + 订单各状态数 + 收藏数 + 足迹数
     *
     * @param int $userId
     * @return array
     */
    public function getUserProfile(int $userId): array
    {
        try {
            $user = $this->findOrFail($userId);

            // 等级信息
            $level = $user->level;
            $levelInfo = $level ? [
                'id'       => $level->id,
                'name'     => $level->name,
                'level'    => $level->level,
                'discount' => (float) $level->discount,
                'icon'     => '',  // icon 字段不存在，预留
            ] : ['id' => 0, 'name' => '普通会员', 'level' => 0, 'discount' => 10.00, 'icon' => ''];

            // 下一等级信息
            $nextLevel = $this->levelRepository->getNextLevel($user->app_id, $level->level ?? 0);
            $nextLevelInfo = $nextLevel ? [
                'id'          => $nextLevel->id,
                'name'        => $nextLevel->name,
                'level'       => $nextLevel->level,
                'experience'  => $nextLevel->experience,
            ] : null;

            // 订单各状态数量
            $orderService = new OrderService();
            $orderCounts = $orderService->getOrderStatusCounts($userId);

            // 优惠券未使用数量
            $userCouponRepo = new \app\repository\UserCouponRepository();
            $unusedCouponCount = $userCouponRepo->countUnusedByUser($userId);

            // 收藏数
            $favoriteCount = $user->favorites()->count();

            // 足迹数
            $footprintCount = $user->views()->count();

            return [
                'id'              => $user->id,
                'nickname'        => $user->nickname,
                'avatar_url'      => $user->avatar_url,
                'phone'           => $user->phone ? substr_replace($user->phone, '****', 3, 4) : '',
                'gender'          => $user->gender,
                'gender_text'     => [0 => '未知', 1 => '男', 2 => '女'][$user->gender] ?? '未知',
                'birthday'        => $user->birthday,
                'money'           => (float) $user->money,
                'freeze_money'    => (float) $user->freeze_money,
                'integral'        => (float) $user->integral,
                'freeze_integral' => (float) $user->freeze_integral,
                'total_spent'     => (float) $user->total_spent,
                'level'           => $levelInfo,
                'next_level'      => $nextLevelInfo,
                'order_counts'    => $orderCounts,
                'coupon_count'    => $unusedCouponCount,
                'favorite_count'  => $favoriteCount,
                'footprint_count' => $footprintCount,
                'created_at'      => $user->created_at,
            ];
        } catch (Exception $e) {
            $this->logError('获取用户综合信息失败', ['user_id' => $userId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 用户浏览足迹（分页）
     *
     * @param int $userId
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function getFootprint(int $userId, int $page = 1, int $pageSize = 20): array
    {
        try {
            $page = max(1, $page);
            $pageSize = min(50, max(1, $pageSize));

            $viewRepo = new \app\repository\ItemViewRepository();
            $paginator = $viewRepo->getPaginatedByUserWithItem($userId, $page, $pageSize);
            $list = $paginator->getCollection()->map(function ($view) {
                $item = $view->item;
                if (!$item) {
                    return null;
                }
                return [
                    'view_id'      => $view->id,
                    'view_count'   => (int) ($view->view_count ?? 1),
                    'viewed_at'    => (int) $view->getRawOriginal('created_at'),
                    'item'         => [
                        'id'          => $item->id,
                        'name'        => $item->name,
                        'subtitle'    => $item->subtitle ?? '',
                        'sale_price'  => $item->sale_price,
                        'image'       => \app\model\BaseModel::resolveAssetUrl($item->getMainImageAttribute() ?: ''),
                        'is_on_sale'  => (int) $item->is_on_sale,
                    ],
                ];
            })->filter()->values();

            return [
                'list'      => $list,
                'total'     => $paginator->total(),
                'page'      => $page,
                'page_size' => $pageSize,
            ];
        } catch (Exception $e) {
            $this->logError('获取用户浏览足迹失败', ['user_id' => $userId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 清空浏览足迹
     *
     * @param int $userId
     * @return bool
     */
    public function clearFootprint(int $userId): bool
    {
        try {
            $viewRepo = new \app\repository\ItemViewRepository();
            $viewRepo->deleteByUser($userId);
            $this->logInfo('清空浏览足迹', ['user_id' => $userId]);
            return true;
        } catch (Exception $e) {
            $this->logError('清空浏览足迹失败', ['user_id' => $userId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 删除单条浏览足迹
     *
     * @param int $viewId
     * @param int $userId
     * @return bool
     */
    public function removeFootprint(int $viewId, int $userId): bool
    {
        try {
            $viewRepo = new \app\repository\ItemViewRepository();
            $view = $viewRepo->findByIdAndUser($viewId, $userId);
            if (!$view) {
                throw new BusinessException('足迹记录不存在', 404);
            }
            $view->delete();
            return true;
        } catch (Exception $e) {
            $this->logError('删除浏览足迹失败', ['view_id' => $viewId, 'user_id' => $userId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 用户报表（按日统计）
     */
    public function getUserReport($appId, $startTs = null, $endTs = null): array
    {
        $days = 30;
        if ($startTs && $endTs) {
            $days = min(365, max(1, intval(($endTs - $startTs) / 86400) + 1));
        }

        $base = $this->repository->query();
        if ($appId > 0) {
            $base->where('app_id', $appId);
        }

        $data = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $dayStart = strtotime($date . ' 00:00:00');
            $dayEnd = strtotime($date . ' 23:59:59');

            $data[] = [
                'date' => $date,
                'new_users' => (clone $base)->whereBetween('created_at', [$dayStart, $dayEnd])->count(),
                'active_users' => (clone $base)->whereBetween('last_login_at', [$dayStart, $dayEnd])->count(),
                'order_users' => 0,
            ];
        }

        return $data;
    }

    /**
     * 最新注册用户
     */
    public function getLatestUsers($appId, $limit = 10)
    {
        $query = $this->repository->query();
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->orderBy('id', 'desc')->limit($limit)->get();
    }

    /**
     * 用户仪表板（真实数据）
     */
    public function getUserDashboard($appId): array
    {
        $base = $this->repository->query();
        if ($appId > 0) {
            $base->where('app_id', $appId);
        }

        $now = time();
        $thisMonthStart = strtotime(date('Y-m-01') . ' 00:00:00');
        $lastMonthStart = strtotime('first day of last month 00:00:00');
        $lastMonthEnd = strtotime('last day of last month 23:59:59');
        $thirtyDaysAgo = $now - 86400 * 30;
        $ninetyDaysAgo = $now - 86400 * 90;

        // --- Stats ---
        $totalUsers = (clone $base)->count();
        $totalUsersLastMonth = (clone $base)->where('created_at', '<', $thisMonthStart)->count();
        $totalUsersTrend = $totalUsersLastMonth > 0 ? round(($totalUsers - $totalUsersLastMonth) / $totalUsersLastMonth * 100, 1) : 0;

        $newUsers = (clone $base)->where('created_at', '>=', $thisMonthStart)->count();
        $newUsersLastMonth = (clone $base)->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])->count();
        $newUsersTrend = $newUsersLastMonth > 0 ? round(($newUsers - $newUsersLastMonth) / $newUsersLastMonth * 100, 1) : 0;

        $activeUsers = (clone $base)->where('last_login_at', '>=', $thirtyDaysAgo)->count();
        $activeRate = $totalUsers > 0 ? round($activeUsers / $totalUsers, 4) : 0;
        $activeUsersLastMonth = (clone $base)->where('last_login_at', '>=', $thirtyDaysAgo - 86400 * 30)->where('last_login_at', '<', $thirtyDaysAgo)->count();
        $activeRateLastMonth = $totalUsers > 0 ? round($activeUsersLastMonth / $totalUsers, 4) : 0;
        $activeRateTrend = $activeRateLastMonth > 0 ? round(($activeRate - $activeRateLastMonth) / $activeRateLastMonth * 100, 1) : 0;

        // Avg order value
        $orderBase = $this->orderRepo->query();
        if ($appId > 0) {
            $orderBase->where('app_id', $appId);
        }
        $avgOrderValue = (clone $orderBase)->where('pay_status', 1)->where('created_at', '>=', $thisMonthStart)->avg('pay_price') ?? 0;
        $avgOrderValueLastMonth = (clone $orderBase)->where('pay_status', 1)->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])->avg('pay_price') ?? 0;
        $avgOrderValueTrend = $avgOrderValueLastMonth > 0 ? round(($avgOrderValue - $avgOrderValueLastMonth) / $avgOrderValueLastMonth * 100, 1) : 0;

        // --- Chart (last 12 months new users) ---
        $chart = [];
        for ($i = 11; $i >= 0; $i--) {
            $monthStart = strtotime(date('Y-m-01', strtotime("-{$i} months")) . ' 00:00:00');
            $monthEnd = strtotime('last day of ' . date('Y-m', $monthStart) . ' 23:59:59');
            $count = (clone $base)->whereBetween('created_at', [$monthStart, $monthEnd])->count();
            $chart[] = [
                'label' => date('n', $monthStart) . '',
                'value' => $count,
            ];
        }

        // --- Segments ---
        $newCount = (clone $base)->where('created_at', '>=', $thirtyDaysAgo)->count();
        $vipCount = (clone $base)->where('level_id', '>', 1)->count();
        $regularCount = (clone $base)->where('level_id', 1)->where('created_at', '<', $thirtyDaysAgo)->count();
        $inactiveCount = (clone $base)->where(function ($q) use ($ninetyDaysAgo) {
            $q->whereNull('last_login_at')->orWhere('last_login_at', '<', $ninetyDaysAgo);
        })->count();

        $segments = [];
        foreach ([
            ['key' => 'new', 'count' => $newCount],
            ['key' => 'vip', 'count' => $vipCount],
            ['key' => 'regular', 'count' => $regularCount],
            ['key' => 'inactive', 'count' => $inactiveCount],
        ] as $seg) {
            $seg['percent'] = $totalUsers > 0 ? round($seg['count'] / $totalUsers * 100, 1) : 0;
            $segments[] = $seg;
        }

        // --- Latest users ---
        $latestUsers = (clone $base)->orderBy('id', 'desc')->limit(10)->get();
        $orderRepo = new OrderRepository();
        $users = [];
        foreach ($latestUsers as $u) {
            $orderCount = $orderRepo->query()->where('user_id', $u->id)->count();
            $totalSpent = $orderRepo->query()->where('user_id', $u->id)->where('pay_status', 1)->sum('pay_price');
            $users[] = [
                'id' => $u->id,
                'nickname' => $u->nickname,
                'username' => $u->phone ?: '',
                'registered_at' => $u->created_at ? date('Y-m-d', is_numeric($u->created_at) ? $u->created_at : strtotime($u->created_at)) : '-',
                'created_at' => $u->created_at ? date('Y-m-d', is_numeric($u->created_at) ? $u->created_at : strtotime($u->created_at)) : '-',
                'order_count' => $orderCount,
                'total_spent' => round((float) $totalSpent, 2),
                'status' => $u->deleted_at > 0 ? 'inactive' : 'active',
            ];
        }

        return [
            'stats' => [
                'total_users' => $totalUsers,
                'total_users_trend' => $totalUsersTrend,
                'new_users' => $newUsers,
                'new_users_trend' => $newUsersTrend,
                'active_rate' => $activeRate,
                'active_rate_trend' => $activeRateTrend,
                'avg_order_value' => round((float) $avgOrderValue, 2),
                'avg_order_value_trend' => $avgOrderValueTrend,
            ],
            'chart' => $chart,
            'segments' => $segments,
            'users' => $users,
        ];
    }

    /**
     * 数据变更审计日志
     * @enterprise-only 接入 DataChangeLogService 后入库（现仅打 info 日志）
     */
    private function logDataChange($tableName, $recordId, $changeType, $oldValue, $newValue): void
    {
        $this->logInfo('记录数据变更日志', [
            'table_name'  => $tableName,
            'record_id'   => $recordId,
            'change_type' => $changeType,
        ]);
    }

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
     * 获取账号安全信息
     */
    public function getSecurityInfo(int $userId): array
    {
        $user = $this->repository->find($userId);
        if (!$user) {
            throw new BusinessException('用户不存在');
        }

        $hasPassword  = !empty($user->password);
        $hasPhone     = !empty($user->phone);
        $hasWxBind    = !empty($user->openid);
        $hasEmail     = !empty($user->email);

        $score = 0;
        if ($hasPassword) $score += 30;
        if ($hasPhone)    $score += 30;
        if ($hasWxBind)   $score += 20;
        if ($hasEmail)    $score += 10;
        if (($user->login_security_status ?? 0) > 0) $score += 10;
        $score = min(100, $score);

        $level = '低';
        $levelColor = '#dc2626';
        if ($score >= 80) {
            $level = '高';
            $levelColor = '#10b981';
        } elseif ($score >= 60) {
            $level = '中';
            $levelColor = '#f59e0b';
        }

        $lastLoginAt = (int) ($user->last_login_at ?? 0);
        $lastLoginText = '';
        if ($lastLoginAt > 0) {
            $lastLoginText = date('Y-m-d H:i:s', $lastLoginAt);
        }

        $loginCount = (int) ($user->login_count ?? 0);

        $createdAt = $user->created_at;
        $createdText = '';
        if ($createdAt) {
            if (is_numeric($createdAt)) {
                $ts = (int) $createdAt;
                if ($ts > 0) $createdText = date('Y-m-d', $ts);
            } else {
                $createdText = (string) $createdAt;
                if (strlen($createdText) >= 10) {
                    $createdText = substr($createdText, 0, 10);
                }
            }
        }

        $maskPhone = function (?string $phone): string {
            if (!$phone) return '未绑定';
            if (strlen($phone) >= 11) {
                return substr($phone, 0, 3) . '****' . substr($phone, -4);
            }
            return $phone;
        };

        $maskEmail = function (?string $email): string {
            if (!$email) return '未绑定';
            $atPos = strpos($email, '@');
            if ($atPos === false) return $email;
            $name = substr($email, 0, $atPos);
            $domain = substr($email, $atPos);
            if (strlen($name) <= 2) {
                return $name[0] . '*' . $domain;
            }
            return substr($name, 0, 2) . str_repeat('*', max(1, strlen($name) - 2)) . $domain;
        };

        $items = [
            [
                'key'    => 'password',
                'title'  => '登录密码',
                'desc'   => $hasPassword ? '已设置' : '未设置',
                'bound'  => $hasPassword,
                'action' => $hasPassword ? '修改' : '设置',
            ],
            [
                'key'    => 'phone',
                'title'  => '手机号码',
                'desc'   => $hasPhone ? $maskPhone($user->phone) : '未绑定',
                'bound'  => $hasPhone,
                'action' => $hasPhone ? '更换' : '绑定',
            ],
            [
                'key'    => 'wechat',
                'title'  => '微信账号',
                'desc'   => $hasWxBind ? '已绑定' : '未绑定',
                'bound'  => $hasWxBind,
                'action' => $hasWxBind ? '解绑' : '绑定',
            ],
            [
                'key'    => 'email',
                'title'  => '电子邮箱',
                'desc'   => $hasEmail ? $maskEmail($user->email) : '未绑定',
                'bound'  => $hasEmail,
                'action' => $hasEmail ? '更换' : '绑定',
            ],
        ];

        return [
            'score'         => $score,
            'level'         => $level,
            'level_color'   => $levelColor,
            'items'         => $items,
            'last_login_at' => $lastLoginText,
            'login_count'   => $loginCount,
            'last_login_ip' => $user->last_login_ip ?: '',
            'created_at'    => $createdText,
        ];
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
