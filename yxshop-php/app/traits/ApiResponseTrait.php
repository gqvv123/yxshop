<?php

namespace app\traits;

use support\Response;

trait ApiResponseTrait
{
    protected function success($data = null, string $message = 'success', int $code = 0): Response
    {
        $data = $this->normalizeData($data);

        return new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'code' => $code,
            'message' => $message,
            'data' => $data,
        ], JSON_UNESCAPED_UNICODE));
    }

    private function normalizeData($data)
    {
        if ($data instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            return [
                'list' => $data->items(),
                'total' => $data->total(),
                'page' => $data->currentPage(),
                'page_size' => $data->perPage(),
                'last_page' => $data->lastPage(),
            ];
        }

        if (is_array($data) && isset($data['data']) && is_array($data['data']) && isset($data['total'])) {
            $normalized = [
                'list' => $data['data'],
                'total' => (int) $data['total'],
                'page' => (int) ($data['page'] ?? $data['current_page'] ?? 1),
                'page_size' => (int) ($data['page_size'] ?? $data['per_page'] ?? $data['limit'] ?? 20),
            ];
            unset($data['data'], $data['total'], $data['page'], $data['current_page'], $data['page_size'], $data['per_page'], $data['limit'], $data['last_page']);
            return array_merge($normalized, $data);
        }

        return $data;
    }

    protected function error(string $message = 'error', int $code = 1, $data = null, int $httpStatus = 200): Response
    {
        return new Response($httpStatus, ['Content-Type' => 'application/json'], json_encode([
            'code' => $code,
            'message' => $message,
            'data' => $data,
        ], JSON_UNESCAPED_UNICODE));
    }

    protected function errorUnauthorized(string $message = '未授权访问'): Response
    {
        return new Response(401, ['Content-Type' => 'application/json'], json_encode([
            'code' => 401,
            'message' => $message,
            'data' => null,
        ], JSON_UNESCAPED_UNICODE));
    }

    protected function errorForbidden(string $message = '权限不足'): Response
    {
        return new Response(403, ['Content-Type' => 'application/json'], json_encode([
            'code' => 403,
            'message' => $message,
            'data' => null,
        ], JSON_UNESCAPED_UNICODE));
    }

    protected function errorNotFound(string $message = '资源不存在'): Response
    {
        return new Response(404, ['Content-Type' => 'application/json'], json_encode([
            'code' => 404,
            'message' => $message,
            'data' => null,
        ], JSON_UNESCAPED_UNICODE));
    }

    protected function errorValidation(string $message = '参数验证失败', $errors = null): Response
    {
        return new Response(422, ['Content-Type' => 'application/json'], json_encode([
            'code' => 422,
            'message' => $message,
            'data' => $errors,
        ], JSON_UNESCAPED_UNICODE));
    }

    protected function paginate($paginator, string $message = 'success'): Response
    {
        return $this->success([
            'list' => $paginator->items(),
            'total' => $paginator->total(),
            'page' => $paginator->currentPage(),
            'page_size' => $paginator->perPage(),
            'last_page' => $paginator->lastPage(),
        ], $message);
    }
}
