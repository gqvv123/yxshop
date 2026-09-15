<?php

namespace app\api;

use support\Request;
use app\traits\ApiResponseTrait;

class BaseController
{
    use ApiResponseTrait;

    protected function getCurrentUser(Request $request)
    {
        // JwtAuthMiddleware 写入 $request->user（requestAttr='user'）；兼容历史 apiUser/merchantUser
        return $request->user ?? $request->apiUser ?? $request->merchantUser ?? null;
    }

    protected function getCurrentUserId(Request $request): ?int
    {
        // 中间件已挂载 $request->userId 别名，优先使用
        if (!empty($request->userId)) {
            return (int) $request->userId;
        }
        $user = $this->getCurrentUser($request);
        return $user ? (int) $user->id : null;
    }

    protected function getCurrentUserType(Request $request): ?string
    {
        $user = $this->getCurrentUser($request);
        return $user ? ($user->type ?? null) : null;
    }

    protected function getCurrentMerchantId(Request $request): ?int
    {
        $user = $request->merchantUser ?? null;
        return $user ? ($user->merchant_id ?? null) : null;
    }

    protected function isMerchantOwner(Request $request): bool
    {
        return $this->getCurrentUserType($request) === 'merchant_owner';
    }

    protected function isMerchantManager(Request $request): bool
    {
        $userType = $this->getCurrentUserType($request);
        return in_array($userType, ['merchant_owner', 'merchant_manager']);
    }

    protected function isVipUser(Request $request): bool
    {
        return $this->getCurrentUserType($request) === 'vip_customer';
    }

    protected function validateRequired(Request $request, array $fields): void
    {
        foreach ($fields as $field => $label) {
            if (is_int($field)) {
                $field = $label;
                $label = $field;
            }
            $value = $request->input($field);
            if (empty($value) && $value !== '0' && $value !== 0) {
                throw new \app\exception\ValidationException("{$label}不能为空");
            }
        }
    }

    protected function validatePagination(Request $request): array
    {
        $page = max(1, (int)($request->get('page', 1)));
        $pageSize = min(100, max(1, (int)($request->get('page_size', 20))));

        return [
            'page' => $page,
            'page_size' => $pageSize,
        ];
    }

    protected function validateLocation(Request $request): array
    {
        $latitude = (float)$request->get('latitude');
        $longitude = (float)$request->get('longitude');

        if ($latitude < -90 || $latitude > 90) {
            throw new \app\exception\ValidationException('纬度必须在-90到90之间');
        }

        if ($longitude < -180 || $longitude > 180) {
            throw new \app\exception\ValidationException('经度必须在-180到180之间');
        }

        return [
            'latitude' => $latitude,
            'longitude' => $longitude,
        ];
    }

    protected function validateRestaurantPermission(Request $request, int $restaurantId): bool
    {
        $currentMerchantId = $this->getCurrentMerchantId($request);
        return $currentMerchantId === $restaurantId;
    }
}
