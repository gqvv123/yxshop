<?php

namespace app\common;

/**
 * 公共组件：Safe Helper。
 *
 * 提供「失败即返回安全默认值」的封装，替代散落在各处的 @ 错误抑制符。
 * 错误抑制符会把「文件不存在、目录不可写、JSON 非法」等问题完全静默，
 * 排查线上问题时无从下手；这里统一收敛到一处，语义清晰、可复用。
 *
 * @package app\common
 */
class SafeHelper
{
    /**
     * 删除文件；文件不存在或删除失败返回 false，不抛异常。
     */
    public static function unlink(string $path): bool
    {
        if ($path === '' || !is_file($path)) {
            return false;
        }
        return @unlink($path);
    }

    /**
     * 读取文件内容；文件不存在或不可读返回 null。
     */
    public static function read(string $path): ?string
    {
        if ($path === '' || !is_file($path) || !is_readable($path)) {
            return null;
        }
        $content = @file_get_contents($path);
        return $content === false ? null : $content;
    }

    /**
     * 写入文件；失败返回 false。
     */
    public static function write(string $path, string $content): bool
    {
        if ($path === '') {
            return false;
        }
        return @file_put_contents($path, $content) !== false;
    }

    /**
     * 创建目录（含递归）；已存在或创建成功返回 true。
     */
    public static function mkdir(string $dir, int $mode = 0775): bool
    {
        if ($dir === '') {
            return false;
        }
        if (is_dir($dir)) {
            return true;
        }
        return @mkdir($dir, $mode, true) || is_dir($dir);
    }

    /**
     * 列出目录内容；目录不存在返回空数组。
     */
    public static function scandir(string $dir): array
    {
        if ($dir === '' || !is_dir($dir)) {
            return [];
        }
        $entries = @scandir($dir);
        return $entries === false ? [] : $entries;
    }

    /**
     * 解析 JSON；非法 JSON 返回默认值（默认空数组）。
     */
    public static function decodeJson(string $json, array $default = []): array
    {
        if (trim($json) === '') {
            return $default;
        }
        $decoded = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            return $default;
        }
        return $decoded;
    }

    /**
     * 解析 JSON 为任意类型；非法 JSON 返回默认值。
     */
    public static function decodeJsonMixed(string $json, mixed $default = null): mixed
    {
        if (trim($json) === '') {
            return $default;
        }
        $decoded = json_decode($json, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : $default;
    }
}
