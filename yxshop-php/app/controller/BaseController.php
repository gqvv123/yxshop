<?php

namespace app\controller;

use support\Request;
use app\traits\ApiResponseTrait;
use app\traits\BatchOperationTrait;

class BaseController
{
    use ApiResponseTrait;
    use BatchOperationTrait;

    protected function getCurrentUserId(Request $request): ?int
    {
        return $request->userId ?? null;
    }

    protected function getAppId(Request $request): int
    {
        return $request->appId ?? 0;
    }

    protected function validateRequired(Request $request, array $fields): void
    {
        foreach ($fields as $field => $label) {
            if (empty($request->input($field)) && $request->input($field) !== '0' && $request->input($field) !== 0) {
                throw new \app\exception\ValidationException("{$label}不能为空");
            }
        }
    }
}
