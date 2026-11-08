<?php

/**
 * 全局辅助函数：file 相关。
 *
 * 从 app/functions.php 按主题拆分而来，函数实现保持原样。
 *
 * @package app\helpers
 */

if (!function_exists('store_file')) {
    /**
     * @deprecated 使用 \app\utility\FileHelper::storeFile() 替代
     */
    function store_file($file, $location, $size = null, $removefile = null): string
    {
        return \app\utility\FileHelper::storeFile($file, $location, $size, $removefile);
    }
}

if (!function_exists('remove_file')) {
    /**
     * @deprecated 使用 \app\utility\FileHelper::removeFile() 替代
     */
    function remove_file($location, $removefile): void
    {
        \app\utility\FileHelper::removeFile($location, $removefile);
    }
}

if (!function_exists('show_image')) {
    /**
     * @deprecated 使用 \app\utility\FileHelper::showImage() 替代
     */
    function show_image($image, $size = null): string
    {
        return \app\utility\FileHelper::showImage($image, $size);
    }
}

if (!function_exists('upload_new_file')) {
    /**
     * @deprecated 使用 \app\utility\FileHelper::uploadNewFile() 替代
     */
    function upload_new_file($file, $location, $old = null): string
    {
        return \app\utility\FileHelper::uploadNewFile($file, $location, $old);
    }
}

if (!function_exists('export_excel')) {
    /**
     * @deprecated 使用 \app\utility\FileHelper::exportExcel() 替代
     */
    function export_excel($fileName, $tileArray = [], $dataArray = []): void
    {
        \app\utility\FileHelper::exportExcel($fileName, $tileArray, $dataArray);
    }
}

if (!function_exists('file_format')) {
    /**
     * @deprecated 使用 \app\utility\FileHelper::fileFormat() 替代
     */
    function file_format($type = 'image'): array
    {
        return \app\utility\FileHelper::fileFormat();
    }
}

if (!function_exists('file_path')) {
    /**
     * @deprecated 使用 \app\utility\FileHelper::filePath() 替代
     */
    function file_path(): array
    {
        return \app\utility\FileHelper::filePath();
    }
}
