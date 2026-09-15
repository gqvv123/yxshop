<?php

namespace app\utility;

class StringHelper
{
    public static function randomString(int $length = 16): string
    {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $str;
    }

    public static function generateOrderNo(string $prefix = 'ORD'): string
    {
        return $prefix . date('YmdHis') . str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
    }

    public static function generateOrderCode(string $prefix = ''): string
    {
        return strtoupper($prefix . \Illuminate\Support\Str::random(12));
    }

    public static function randToken(int $length = 40): string
    {
        return \Illuminate\Support\Str::random($length);
    }

    public static function strUnique(int $length = 16): string
    {
        return md5(uniqid((string)mt_rand(), true) . microtime(true));
    }

    public static function trxNumber(int $length = 14): string
    {
        return strtoupper(\Illuminate\Support\Str::random($length));
    }

    public static function generatePrefixedHash(string $prefix = ''): string
    {
        return $prefix . md5(uniqid((string)mt_rand(), true));
    }

    public static function toUnderScore(string $str): string
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $str));
    }

    public static function unslug(string $slug, string $separator = '-'): string
    {
        return ucwords(str_replace($separator, ' ', $slug));
    }

    public static function limitWords(string $text, int $limit = 50, string $end = '...'): string
    {
        return \Illuminate\Support\Str::limit($text, $limit, $end);
    }

    public static function limitLines(string $text, int $lines = 3, string $end = '...'): string
    {
        $textLines = explode("\n", $text);
        if (count($textLines) > $lines) {
            return implode("\n", array_slice($textLines, 0, $lines)) . $end;
        }
        return $text;
    }

    public static function textSorted(string $text): string
    {
        return ucfirst(preg_replace("/[^A-Za-z0-9 ]/", ' ', $text));
    }

    public static function hexaToRgba(string $code): string
    {
        $result = sscanf($code, "#%02x%02x%02x");
        return "{$result[0]},{$result[1]},{$result[2]}";
    }

    public static function k2t(string $text): string
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $text)));
    }

    public static function t2k(string $text): string
    {
        return strtolower(preg_replace('/(?<!^)([A-Z])/', '_$1', $text));
    }

    public static function replaceSortCode(string $sortCode): string
    {
        return str_replace(['__', '_'], [' ', ' '], $sortCode);
    }
}
