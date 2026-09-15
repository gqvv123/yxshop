<?php

namespace app\utility;

class HttpHelper
{
    /**
     * GET 请求
     */
    public static function curl(string $url, array $data = [])
    {
        if (!empty($data)) {
            $url = $url . '?' . http_build_query($data);
        }
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        self::applySslOptions($curl);

        $result = curl_exec($curl);
        if ($result === false) {
            \support\Log::error('HttpHelper curl 请求失败', [
                'url' => $url,
                'error' => curl_error($curl),
                'errno' => curl_errno($curl),
            ]);
        }
        curl_close($curl);
        return $result;
    }

    /**
     * POST 请求
     */
    public static function curlPost(string $url, $data = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        if (is_string($data)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        self::applySslOptions($ch);

        $result = curl_exec($ch);
        if ($result === false) {
            \support\Log::error('HttpHelper curlPost 请求失败', [
                'url' => $url,
                'error' => curl_error($ch),
                'errno' => curl_errno($ch),
            ]);
        }
        curl_close($ch);
        return $result;
    }

    /**
     * SSL 配置：
     * - 生产：CURL_SSL_VERIFY=true，并配置 CURL_CA_BUNDLE 指向 cacert.pem
     * - 本地开发：可临时设 CURL_SSL_VERIFY=false 跳过校验
     */
    private static function applySslOptions($curl): void
    {
        $verify = env('CURL_SSL_VERIFY', true);
        $verify = !($verify === false || $verify === 'false' || $verify === '0' || $verify === 0);

        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, $verify);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, $verify ? 2 : 0);

        $caBundle = env('CURL_CA_BUNDLE', '');
        if ($verify && !empty($caBundle) && is_file($caBundle)) {
            curl_setopt($curl, CURLOPT_CAINFO, $caBundle);
        }
    }

    public static function buildPostFields($data, string $existingKeys = '', array &$returnArray = []): array
    {
        if (($data instanceof \CURLFile) or !(is_array($data) or is_object($data))) {
            $returnArray[$existingKeys] = $data;
            return $returnArray;
        }
        foreach ($data as $key => $item) {
            static::buildPostFields($item, $existingKeys ? $existingKeys . "[$key]" : $key, $returnArray);
        }
        return $returnArray;
    }

    public static function arrayToXml(array $data, string $root = 'xml'): string
    {
        $xml = '<' . $root . '>';
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $xml .= static::arrayToXml($value, $key);
            } else {
                $xml .= '<' . $key . '><![CDATA[' . $value . ']]></' . $key . '>';
            }
        }
        $xml .= '</' . $root . '>';
        return $xml;
    }

    public static function xmlToArray(string $xml): array
    {
        return json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    }

    public static function getClientIp(): string
    {
        $request = request();
        $ip = $request->header('x-forwarded-for');
        if ($ip) {
            $ip = explode(',', $ip);
            $ip = trim($ip[0]);
        } else {
            $ip = $request->header('x-real-ip')
                ?? $request->header('client-ip')
                ?? $request->header('x-client-ip')
                ?? $request->header('via');
        }
        if (!$ip) {
            $ip = $request->ip() ?: '0.0.0.0';
        }
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            $ip = '0.0.0.0';
        }
        return $ip;
    }

    public static function getRealIp(): string
    {
        return static::getClientIp();
    }

    public static function getIpInfo(string $ip = ''): array
    {
        if (empty($ip)) {
            $ip = static::getClientIp();
        }
        if ($ip === '0.0.0.0' || $ip === '127.0.0.1') {
            return ['ip' => $ip, 'country' => 'Local', 'city' => 'Local'];
        }
        try {
            $response = static::curl("http://ip-api.com/json/{$ip}");
            $data = json_decode($response, true);
            if ($data && $data['status'] === 'success') {
                return [
                    'ip' => $ip,
                    'country' => $data['country'] ?? '',
                    'city' => $data['city'] ?? '',
                    'region' => $data['regionName'] ?? '',
                    'isp' => $data['isp'] ?? '',
                ];
            }
        } catch (\Throwable $e) {
        }
        return ['ip' => $ip, 'country' => '', 'city' => ''];
    }
}
