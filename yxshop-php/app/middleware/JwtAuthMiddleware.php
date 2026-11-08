<?php

namespace app\middleware;

use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;
use app\service\JwtService;

class JwtAuthMiddleware implements MiddlewareInterface
{
    protected string $modelClass;
    protected string $requestAttr;
    protected array $allowedRoles;
    protected bool $checkRoles;

    public function process(Request $request, callable $handler): Response
    {
        $authorization = $request->header('Authorization');

        if (!$authorization) {
            return json(['code' => 401, 'msg' => '未提供认证令牌', 'data' => null]);
        }

        if (!preg_match('/^Bearer\s+(.*)$/i', $authorization, $matches)) {
            return json(['code' => 401, 'msg' => '认证令牌格式错误', 'data' => null]);
        }

        $token = $matches[1];

        try {
            $jwtService = new JwtService();
            $payload = $jwtService->validateToken($token);

            if (!$payload || !$payload['success']) {
                return json(['code' => 401, 'msg' => '认证令牌无效', 'data' => null]);
            }

            $cacheKey = "jwt_blacklist:" . md5($token);
            try {
                if (\support\Redis::get($cacheKey)) {
                    return json(['code' => 401, 'msg' => '登录已过期', 'data' => null]);
                }
            } catch (\Throwable $e) {
                \support\Log::warning('JWT黑名单Redis不可用，已跳过检查', [
                    'error' => $e->getMessage(),
                ]);
            }

            $scope = $payload['data']['scope'] ?? null;
            if ($scope === 'password_change') {
                $path = ltrim($request->path(), '/');
                $allowed = ['admin/api/user/change-password', 'admin/api/user/force-change-password'];
                if (!in_array($path, $allowed, true)) {
                    return json(['code' => 403, 'msg' => '此令牌仅允许修改密码', 'data' => null]);
                }
            }

            $modelClass = $this->modelClass;
            $user = $modelClass::find($payload['data']['user_id']);

            if (!$user) {
                return json(['code' => 401, 'msg' => '用户不存在', 'data' => null]);
            }

            // 状态字段在数据库中可能是 tinyint，PDO 返回 string；
            // 用 (int) 转换后再做严格比较，避免 1 !== '1' 的类型陷阱
            if ((int) ($user->status ?? 1) !== 1) {
                return json(['code' => 401, 'msg' => '用户已被禁用', 'data' => null]);
            }

            // 是否被软删除（兼容 is_delete 字段）
            if (isset($user->deleted_at) && (int) $user->deleted_at > 0) {
                return json(['code' => 401, 'msg' => '账户已被删除', 'data' => null]);
            }

            if ($this->checkRoles && !empty($this->allowedRoles)) {
                $isSuperAdmin = (int)($user->is_super_admin ?? 0) === 1;
                if (!$isSuperAdmin) {
                    $roleName = null;
                    if (isset($user->role) && is_object($user->role)) {
                        $roleName = $user->role->name ?? $user->role->role_name ?? null;
                    } elseif (isset($user->role) && is_string($user->role)) {
                        $roleName = $user->role;
                    }
                    $type = $user->type ?? null;
                    $roleMatched = ($roleName !== null && in_array($roleName, $this->allowedRoles, true))
                        || ($type !== null && in_array($type, $this->allowedRoles, true));
                    if (!$roleMatched) {
                        return json(['code' => 403, 'msg' => '无权限访问', 'data' => null]);
                    }
                }
            }

            $request->{$this->requestAttr} = $user;
            // 同时挂上常用别名，便于控制器统一通过 $request->user / userId 取
            $request->userId = $user->id ?? null;

            return $handler($request);
        } catch (\Exception $e) {
            return json(['code' => 401, 'msg' => '认证失败', 'data' => null]);
        }
    }
}
