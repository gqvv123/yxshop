<?php

namespace app\common;

/**
 * 公共组件：日志载荷编解码。
 *
 * 系统日志的 message 字段是 mediumtext（utf8mb4），存不了 gzcompress 的二进制；
 * 日志清理任务压缩后统一改存 base64(gzcompress(json))，
 * 读取时由这里统一还原，避免各处重复实现。
 *
 * @package app\common
 */
class LogPayload
{
    /**
     * 编码待压缩的日志内容（base64 + gzip，纯 ASCII，可安全写入 utf8mb4 字段）。
     */
    public static function encode(string $raw): string
    {
        $compressed = gzcompress($raw);
        if ($compressed === false) {
            return base64_encode($raw);
        }
        return base64_encode($compressed);
    }

    /**
     * 解码日志内容；非压缩记录或解码失败时原样返回。
     */
    public static function decode(?string $payload, bool $compressed): string
    {
        $payload = (string) $payload;
        if (!$compressed || $payload === '') {
            return $payload;
        }
        $binary = base64_decode($payload, true);
        if ($binary === false) {
            return $payload;
        }
        $raw = @gzuncompress($binary);
        return $raw === false ? $payload : $raw;
    }

    /**
     * 批量解码记录集合里的 message 字段。
     *
     * @param iterable<mixed> $records
     * @return iterable<mixed>
     */
    public static function decodeRecords(iterable $records): iterable
    {
        foreach ($records as $record) {
            if (is_object($record) && isset($record->message)) {
                $compressed = (int) ($record->is_compressed ?? 0) === 1;
                $record->message = self::decode($record->message, $compressed);
            } elseif (is_array($record) && array_key_exists('message', $record)) {
                $compressed = (int) ($record['is_compressed'] ?? 0) === 1;
                $record['message'] = self::decode($record['message'], $compressed);
            }
            yield $record;
        }
    }
}
