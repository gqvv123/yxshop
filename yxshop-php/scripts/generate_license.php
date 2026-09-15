<?php

/**
 * YXShop 授权码生成工具
 *
 * 使用方法：
 *   php scripts/generate_license.php <edition> <domain> [date]
 *
 * 参数：
 *   edition  - 版本类型：commercial | enterprise | saas
 *   domain   - 绑定域名（如 shop.example.com）
 *   date     - 授权日期 YYYYMMDD（可选，默认今天）
 *
 * 示例：
 *   php scripts/generate_license.php commercial shop.example.com
 *   php scripts/generate_license.php enterprise shop.example.com 20260301
 *
 * 输出：
 *   授权码 + 到期时间 + 配置指引
 *
 * @package scripts
 */

require_once __DIR__ . '/../vendor/autoload.php';

// 支持 .env 文件读取（如果 vlucas/phpdotenv 已安装）
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) {
            continue;
        }
        if (str_contains($line, '=')) {
            [$key, $value] = explode('=', $line, 2);
            putenv(trim($key) . '=' . trim($value));
        }
    }
}

use app\common\LicenseManager;

// 解析命令行参数
$argv = $_SERVER['argv'];
array_shift($argv); // 移除脚本名

if (count($argv) < 2) {
    echo <<<HELP
YXShop 授权码生成工具
========================

使用方法：
  php scripts/generate_license.php <edition> <domain> [date]

参数：
  edition  - 版本类型：commercial | enterprise | saas
  domain   - 绑定域名（如 shop.example.com）
  date     - 授权日期 YYYYMMDD（可选，默认今天）

示例：
  php scripts/generate_license.php commercial shop.example.com
  php scripts/generate_license.php enterprise shop.example.com 20260301

HELP;
    exit(1);
}

$edition = $argv[0];
$domain = $argv[1];
$date = $argv[2] ?? date('Ymd');

// 验证版本类型
$validEditions = ['commercial', 'enterprise', 'saas'];
if (!in_array($edition, $validEditions)) {
    echo "错误：版本类型必须是 " . implode(' / ', $validEditions) . "\n";
    exit(1);
}

// 验证日期格式
if (!preg_match('/^\d{8}$/', $date)) {
    echo "错误：日期格式必须为 YYYYMMDD（如 20260301）\n";
    exit(1);
}

// 验证日期有效性
$dateObj = DateTime::createFromFormat('Ymd', $date);
if (!$dateObj || $dateObj->format('Ymd') !== $date) {
    echo "错误：无效的日期\n";
    exit(1);
}

// 生成授权码
$licenseKey = LicenseManager::generateLicenseKey($edition, $domain, $date);

// 计算到期时间
$expiryDays = match ($edition) {
    'commercial' => 365,
    'enterprise' => 365,
    'saas'       => 30,
    default      => 365,
};
$expiryDate = (clone $dateObj)->modify("+{$expiryDays} days")->format('Y-m-d');

// 输出结果
echo <<<OUTPUT
✅ 授权码生成成功！
==================

授权码：  {$licenseKey}
版本：    {$edition}
绑定域名：{$domain}
授权日期：{$dateObj->format('Y-m-d')}
到期时间：{$expiryDate}
有效期：  {$expiryDays} 天

配置方法：
  在 .env 文件中添加以下配置：

  APP_EDITION={$edition}
  LICENSE_KEY={$licenseKey}

  然后重启服务即可生效。

验证方法：
  访问后台 → 系统设置 → 授权管理
  或调用 API：GET /admin/api/license/status

OUTPUT;
