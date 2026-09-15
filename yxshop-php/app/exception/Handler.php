<?php

namespace app\exception;

use Webman\Exception\ExceptionHandler;
use Webman\Http\Request;
use Webman\Http\Response;
use Throwable;
use app\model\SystemLog;

class Handler extends ExceptionHandler
{
    public $dontReport = [
        BusinessException::class,
        AuthenticationException::class,
        AuthorizationException::class,
        ValidationException::class,
        NotFoundException::class,
    ];

    public function report(Throwable $exception): void
    {
        if (!$this->shouldntReport($exception)) {
            try {
                $request = request();
                SystemLog::record(
                    SystemLog::LEVEL_ERROR,
                    get_class($exception) . ': ' . $exception->getMessage(),
                    null,
                    $exception->getFile(),
                    $exception->getLine(),
                    collect($exception->getTrace())->map(function ($item) {
                        return \Illuminate\Support\Arr::only($item, ['file', 'line', 'function', 'class']);
                    })->take(10)->toArray(),
                    'exception',
                    '',
                    '',
                    $request ? $request->getRealIp() : '',
                    $request ? $request->header('User-Agent', '') : '',
                    0
                );
            } catch (\Throwable $logException) {
                error_log('[ExceptionHandler] Failed to log exception: ' . $logException->getMessage());
                error_log('[ExceptionHandler] Original exception: ' . $exception->getMessage() . ' in ' . $exception->getFile() . ':' . $exception->getLine());
            }
        }

        parent::report($exception);
    }

    public function render(Request $request, Throwable $exception): Response
    {
        if ($exception instanceof BusinessException) {
            return new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'code' => $exception->getCode() ?: 1,
                'message' => $exception->getMessage(),
                'data' => null,
            ], JSON_UNESCAPED_UNICODE));
        }

        if ($exception instanceof AuthenticationException) {
            return new Response(401, ['Content-Type' => 'application/json'], json_encode([
                'code' => 401,
                'message' => $exception->getMessage() ?: '未授权访问',
                'data' => null,
            ], JSON_UNESCAPED_UNICODE));
        }

        if ($exception instanceof AuthorizationException) {
            return new Response(403, ['Content-Type' => 'application/json'], json_encode([
                'code' => 403,
                'message' => $exception->getMessage() ?: '权限不足',
                'data' => null,
            ], JSON_UNESCAPED_UNICODE));
        }

        if ($exception instanceof ValidationException) {
            return new Response(422, ['Content-Type' => 'application/json'], json_encode([
                'code' => 422,
                'message' => $exception->getMessage() ?: '参数验证失败',
                'data' => $exception->getErrors(),
            ], JSON_UNESCAPED_UNICODE));
        }

        if ($exception instanceof \think\exception\ValidateException) {
            return new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'code' => 1,
                'message' => $exception->getMessage() ?: '参数验证失败',
                'data' => null,
            ], JSON_UNESCAPED_UNICODE));
        }

        if ($exception instanceof NotFoundException) {
            return new Response(404, ['Content-Type' => 'application/json'], json_encode([
                'code' => 404,
                'message' => $exception->getMessage() ?: '资源不存在',
                'data' => null,
            ], JSON_UNESCAPED_UNICODE));
        }

        if ($request->expectsJson() || $request->header('accept') === 'application/json' || str_starts_with($request->path(), 'api/')) {
            $code = $this->isDebug() ? $exception->getCode() : 500;
            $message = $this->isDebug() ? $exception->getMessage() : '服务器内部错误';
            return new Response(500, ['Content-Type' => 'application/json'], json_encode([
                'code' => is_int($code) && $code > 0 ? $code : 500,
                'message' => $message,
                'data' => $this->isDebug() ? [
                    'exception' => get_class($exception),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'trace' => collect($exception->getTrace())->map(function ($item) {
                        return \Illuminate\Support\Arr::only($item, ['file', 'line', 'function', 'class']);
                    })->toArray(),
                ] : null,
            ], JSON_UNESCAPED_UNICODE));
        }

        return parent::render($request, $exception);
    }

    private function isDebug(): bool
    {
        return (bool) config('app.debug', false);
    }
}
