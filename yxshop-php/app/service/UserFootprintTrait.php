<?php

namespace app\service;
use app\exception\BusinessException;
use Exception;

/**
 * UserFootprintTrait：从 UserService.php 抽离的getUserProfile、getFootprint、clearFootprint、removeFootprint、getSecurityInfo等方法。
 *
 * 拆分目的：控制单文件体积、聚焦单一职责；方法实现保持原样，行为完全等价。
 *
 * @package app\service
 */
trait UserFootprintTrait
{
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
            $level = $user->userLevel;
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
}
