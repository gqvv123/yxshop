<?php

/**
 * 全局辅助函数：http 相关。
 *
 * 从 app/functions.php 按主题拆分而来，函数实现保持原样。
 *
 * @package app\helpers
 */

if (!function_exists('curl')) {
    /**
     * @deprecated 使用 \app\utility\HttpHelper::curl() 替代
     */
    function curl($url, $data = [])
    {
        return \app\utility\HttpHelper::curl($url, $data);
    }
}

if (!function_exists('curlPost')) {
    /**
     * @deprecated 使用 \app\utility\HttpHelper::curlPost() 替代
     */
    function curlPost($url, $data = [])
    {
        return \app\utility\HttpHelper::curlPost($url, $data);
    }
}

if (!function_exists('build_post_fields')) {
    /**
     * @deprecated 使用 \app\utility\HttpHelper::buildPostFields() 替代
     */
    function build_post_fields($data, $existingKeys = '', &$returnArray = []): array
    {
        return \app\utility\HttpHelper::buildPostFields($data, $existingKeys, $returnArray);
    }
}

if (!function_exists('array_to_xml')) {
    /**
     * @deprecated 使用 \app\utility\HttpHelper::arrayToXml() 替代
     */
    function array_to_xml($data, $root = 'xml'): string
    {
        return \app\utility\HttpHelper::arrayToXml($data, $root);
    }
}

if (!function_exists('xml_to_array')) {
    /**
     * @deprecated 使用 \app\utility\HttpHelper::xmlToArray() 替代
     */
    function xml_to_array($xml): array
    {
        return \app\utility\HttpHelper::xmlToArray($xml);
    }
}

if (!function_exists('get_client_ip')) {
    /**
     * @deprecated 使用 \app\utility\HttpHelper::getClientIp() 替代
     */
    function get_client_ip(): string
    {
        return \app\utility\HttpHelper::getClientIp();
    }
}

if (!function_exists('get_real_ip')) {
    /**
     * @deprecated 使用 \app\utility\HttpHelper::getRealIp() 替代
     */
    function get_real_ip(): string
    {
        return \app\utility\HttpHelper::getRealIp();
    }
}

if (!function_exists('get_ip_info')) {
    /**
     * @deprecated 使用 \app\utility\HttpHelper::getIpInfo() 替代
     */
    function get_ip_info(): array
    {
        return \app\utility\HttpHelper::getIpInfo();
    }
}
