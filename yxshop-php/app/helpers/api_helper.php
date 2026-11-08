<?php

/**
 * 全局辅助函数：api 相关。
 *
 * 从 app/functions.php 按主题拆分而来，函数实现保持原样。
 *
 * @package app\helpers
 */

if (!function_exists('api')) {
    /**
     * 创建 API JSON 响应构建器
     */
    function api($data = []): \app\utility\ApiJsonResponse
    {
        return new \app\utility\ApiJsonResponse($data);
    }
}
