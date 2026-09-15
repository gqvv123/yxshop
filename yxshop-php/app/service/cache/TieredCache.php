<?php

namespace app\service\cache;

use support\Redis;

class TieredCache
{
	/**
	 * 进程内缓存
	 * @var array<string, array{expire:int, value:mixed}>
	 */
	protected static $memoryCache = [];

	/**
	 * 二级文件缓存目录
	 */
	protected static function fileCacheDir(): string
	{
		$dir = runtime_path() . '/tiered_cache';
		if (!is_dir($dir)) {
			@mkdir($dir, 0775, true);
		}
		return $dir;
	}

	protected static function now(): int
	{
		return time();
	}

	public static function get(string $key)
	{
		$now = self::now();

		// L1: 内存
		if (isset(self::$memoryCache[$key])) {
			$entry = self::$memoryCache[$key];
			if ($entry['expire'] === 0 || $entry['expire'] > $now) {
				return $entry['value'];
			}
			unset(self::$memoryCache[$key]);
		}

		// L2: 文件
		$path = self::fileCacheDir() . '/' . self::sanitizeKey($key) . '.cache';
		if (is_file($path)) {
			$raw = @file_get_contents($path);
			if ($raw !== false) {
				$data = @json_decode($raw, true);
				if (is_array($data) && isset($data['expire'])) {
					if ($data['expire'] === 0 || $data['expire'] > $now) {
						// 回填到 L1
						self::$memoryCache[$key] = [
							'expire' => (int)$data['expire'],
							'value' => array_key_exists('value', $data) ? $data['value'] : null,
						];
						return array_key_exists('value', $data) ? $data['value'] : null;
					}
				}
			}
			// 过期清理
			@unlink($path);
		}

		// L3: Redis
		$value = Redis::get($key);
		if ($value !== false && $value !== null) {
			$decoded = @json_decode($value, true);
			if (is_array($decoded) && isset($decoded['expire'])) {
				if ($decoded['expire'] === 0 || $decoded['expire'] > $now) {
					// 回填到 L2 与 L1
					self::setLocal($key, array_key_exists('value', $decoded) ? $decoded['value'] : null, $decoded['expire'] ? ($decoded['expire'] - $now) : 0);
					return array_key_exists('value', $decoded) ? $decoded['value'] : null;
				}
			}
		}

		return null;
	}

	public static function set(string $key, $value, int $ttl = 0): void
	{
		self::setLocal($key, $value, $ttl);

		$expireAt = $ttl > 0 ? self::now() + $ttl : 0;
		$payload = json_encode(['expire' => $expireAt, 'value' => $value, 'key' => $key], JSON_UNESCAPED_UNICODE);
		if ($ttl > 0) {
			Redis::setex($key, $ttl, $payload);
		} else {
			Redis::set($key, $payload);
		}
	}

	protected static function setLocal(string $key, $value, int $ttl = 0): void
	{
		$expireAt = $ttl > 0 ? self::now() + $ttl : 0;

		// L1
		self::$memoryCache[$key] = [
			'expire' => $expireAt,
			'value' => $value,
		];

		// L2
		$path = self::fileCacheDir() . '/' . self::sanitizeKey($key) . '.cache';
		@file_put_contents($path, json_encode(['expire' => $expireAt, 'value' => $value, 'key' => $key], JSON_UNESCAPED_UNICODE));
	}

	public static function remember(string $key, int $ttl, callable $callback)
	{
		$value = self::get($key);
		if ($value !== null || self::isPlaceholderPresent($key)) {
			return $value;
		}
		
		// 击穿保护：短期互斥锁
		$lockKey = 'lock:' . $key;
		// 兼容 PhpRedis：使用 setnx + expire 实现互斥锁
		$gotLock = Redis::setnx($lockKey, '1');
		if ($gotLock) {
			Redis::expire($lockKey, 5);
		} else {
			// 未获取锁，短暂等待后重试一次
			usleep(100 * 1000);
			$value = self::get($key);
			if ($value !== null || self::isPlaceholderPresent($key)) {
				return $value;
			}
			$gotLock = Redis::setnx($lockKey, '1');
			if ($gotLock) {
				Redis::expire($lockKey, 5);
			}
		}
		
		try {
			$result = $callback();
			if ($result === null) {
				// 空值缓存占位，防止穿透
				self::setPlaceholder($key, $ttl > 60 ? 60 : $ttl);
				return null;
			}
			self::set($key, $result, $ttl);
			return $result;
		} finally {
			// 释放锁
			Redis::del($lockKey);
		}
	}

	protected static function setPlaceholder(string $key, int $ttl): void
	{
		$expireAt = $ttl > 0 ? self::now() + $ttl : 0;
		// L1
		self::$memoryCache[$key] = [
			'expire' => $expireAt,
			// 不设置 value 字段，以便 get 时能识别存在但值为 null 的占位
		];
		// L2
		$path = self::fileCacheDir() . '/' . self::sanitizeKey($key) . '.cache';
		@file_put_contents($path, json_encode(['expire' => $expireAt, 'key' => $key], JSON_UNESCAPED_UNICODE));
		// L3
		Redis::setex($key, max(1, $ttl), json_encode(['expire' => $expireAt, 'key' => $key], JSON_UNESCAPED_UNICODE));
	}

	protected static function isPlaceholderPresent(string $key): bool
	{
		// L1
		if (isset(self::$memoryCache[$key])) {
			$entry = self::$memoryCache[$key];
			return isset($entry['expire']) && !array_key_exists('value', $entry) && ($entry['expire'] === 0 || $entry['expire'] > self::now());
		}
		// L2
		$path = self::fileCacheDir() . '/' . self::sanitizeKey($key) . '.cache';
		if (is_file($path)) {
			$raw = @file_get_contents($path);
			if ($raw !== false) {
				$data = @json_decode($raw, true);
				if (is_array($data)) {
					return isset($data['expire']) && !array_key_exists('value', $data) && ($data['expire'] === 0 || $data['expire'] > self::now());
				}
			}
		}
		// L3
		$value = Redis::get($key);
		if ($value !== false && $value !== null) {
			$decoded = @json_decode($value, true);
			if (is_array($decoded)) {
				return isset($decoded['expire']) && !array_key_exists('value', $decoded) && ($decoded['expire'] === 0 || $decoded['expire'] > self::now());
			}
		}
		return false;
	}

	public static function delete(string $key): void
	{
		unset(self::$memoryCache[$key]);
		$path = self::fileCacheDir() . '/' . self::sanitizeKey($key) . '.cache';
		if (is_file($path)) {
			@unlink($path);
		}
		Redis::del($key);
	}

	public static function deleteByPrefix(string $prefix): void
	{
		// L1
		foreach (array_keys(self::$memoryCache) as $k) {
			if (substr($k, 0, strlen($prefix)) === $prefix) {
				unset(self::$memoryCache[$k]);
			}
		}
		// L2 - 扫描文件读取原始 key 做匹配
		$dir = self::fileCacheDir();
		$files = @scandir($dir) ?: [];
		foreach ($files as $file) {
			if ($file === '.' || $file === '..') continue;
			$full = $dir . '/' . $file;
			if (!is_file($full)) continue;
			$raw = @file_get_contents($full);
			if ($raw === false) continue;
			$data = @json_decode($raw, true);
			if (!is_array($data)) continue;
			$orig = isset($data['key']) ? $data['key'] : null;
			if (is_string($orig) && substr($orig, 0, strlen($prefix)) === $prefix) {
				@unlink($full);
			}
		}
		// L3 - Redis：使用 SCAN 更安全
		$iterator = null;
		do {
			$keys = Redis::scan($iterator, $prefix . '*', 1000);
			if (is_array($keys) && !empty($keys)) {
				foreach ($keys as $k) {
					Redis::del($k);
				}
			}
		} while ($iterator);
	}

	public static function flush(): void
	{
		// L1: 内存
		self::$memoryCache = [];

		// L2: 文件
		$dir = self::fileCacheDir();
		$files = @scandir($dir) ?: [];
		foreach ($files as $file) {
			if ($file === '.' || $file === '..') continue;
			$full = $dir . '/' . $file;
			if (is_file($full)) {
				@unlink($full);
			}
		}

		// L3: Redis
		try {
			Redis::flushDB();
		} catch (\Throwable $e) {
			// ignore
		}
	}

	public static function buildKey(string $namespace, array $parts = []): string
	{
		if (empty($parts)) {
			return $namespace;
		}
		$flat = [];
		foreach ($parts as $k => $v) {
			if (is_scalar($v)) {
				$flat[] = $k . '=' . (string)$v;
			} else {
				$flat[] = $k . '=' . md5(json_encode($v));
			}
		}
		return $namespace . ':' . implode('|', $flat);
	}

	protected static function sanitizeKey(string $key): string
	{
		// 保留可读性的一部分前缀 + 散列
		$prefix = substr(preg_replace('/[^a-zA-Z0-9:_\-]/', '_', $key), 0, 80);
		return $prefix . '_' . md5($key);
	}
 
} 