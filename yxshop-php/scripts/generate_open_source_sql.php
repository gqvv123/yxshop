<?php
/**
 * 开源版默认 SQL 生成脚本
 *
 * 从现有 yxshop_stuc.sql 基线 + 所有迁移文件合并生成完整的开源版初始化 SQL
 * 包含：表结构 + 默认数据（管理员、菜单、设置、等级、分类）
 *
 * 用法: php generate_open_source_sql.php > yxshop_open_source_default.sql
 */

$basePath = 'd:/wwwroot/yxshop/yxshop-php';
$output = [];

// 文件头
$output[] = "-- ============================================================";
$output[] = "-- YXShop 开源版完整数据库初始化脚本";
$output[] = "-- 版本: 1.0.0 (" . date('Y-m-d') . ")";
$output[] = "-- ";
$output[] = "-- 使用方法:";
$output[] = "--   mysql -u root -p yxshop < database/yxshop_open_source_default.sql";
$output[] = "--";
$output[] = "-- 包含内容:";
$output[] = "--   1. 全部开源版核心表结构（90+ 张表）";
$output[] = "--   2. 默认管理员账号、角色、菜单";
$output[] = "--   3. 默认系统设置、会员等级、分类";
$output[] = "--   4. 迁移记录表";
$output[] = "--";
$output[] = "-- 注意:";
$output[] = "--   - 企业版/商业版表由对应迁移脚本按需添加";
$output[] = "--   - 所有表使用 utf8mb4 字符集";
$output[] = "--   - 时间字段统一使用 int(11) UNIX 时间戳";
$output[] = "--   - ID 使用 Snowflake 雪花算法（bigint UNSIGNED）";
$output[] = "-- ============================================================";
$output[] = "";
$output[] = "SET NAMES utf8mb4;";
$output[] = "SET FOREIGN_KEY_CHECKS = 0;";
$output[] = "";

// 1. 读取基线 SQL（表结构）
$baseline = file_get_contents($basePath . '/database/yxshop_stuc.sql');
if ($baseline !== false) {
    // 移除文件头注释和 SET 语句（避免重复）
    $baseline = preg_replace('/^\/\*.*?\*\//s', '', $baseline);
    $baseline = preg_replace('/^SET NAMES.*$/m', '', $baseline);
    $baseline = preg_replace('/^SET FOREIGN_KEY_CHECKS.*$/m', '', $baseline);
    $output[] = "-- ============================================================";
    $output[] = "-- 一、基线表结构（来自 yxshop_stuc.sql）";
    $output[] = "-- ============================================================";
    $output[] = "";
    $output[] = trim($baseline);
    $output[] = "";
}

// 2. 追加后续迁移中新增的表
$migrationDir = $basePath . '/database/migrations';
$migrations = glob($migrationDir . '/*.sql');
sort($migrations);

$output[] = "-- ============================================================";
$output[] = "-- 二、增量迁移（新增表和字段）";
$output[] = "-- ============================================================";
$output[] = "";

$skipMigrations = ['2026_43_phase44_45_46_enterprise.sql', '2026_44_phase47_agent_profit.sql']; // 企业版表不在开源版中

foreach ($migrations as $migFile) {
    $filename = basename($migFile);
    if (in_array($filename, $skipMigrations, true)) {
        continue;
    }
    $content = file_get_contents($migFile);
    if ($content === false) {
        continue;
    }
    $output[] = "-- ---- 迁移: $filename ----";
    $output[] = trim($content);
    $output[] = "";
}

// 3. 默认种子数据
$output[] = "-- ============================================================";
$output[] = "-- 三、默认种子数据";
$output[] = "-- ============================================================";
$output[] = "";

$output[] = "SET @NOW := UNIX_TIMESTAMP();";
$output[] = "SET @APP_ID := 0;";
$output[] = "";

// 默认管理员
$output[] = "-- 默认超级管理员（密码: admin123，请在首次登录后立即修改）";
$output[] = "INSERT INTO `yxshop_admins` (`id`, `username`, `password`, `app_id`, `nickname`, `is_super_admin`, `role_id`, `status`, `password_changed_at`, `force_password_change`, `created_at`, `updated_at`) VALUES";
$output[] = "(7100000000001, 'admin', '\$2y\$10\$N9zK9xQ5vV5mZ2vD6sJ3vOZQ8jG5pHr5WZcY5e1m5bN5cX9m2vW5K', 0, '超级管理员', 1, 0, 1, 0, 1, @NOW, @NOW)";
$output[] = "ON DUPLICATE KEY UPDATE `updated_at` = @NOW;";
$output[] = "";

// 默认角色
$output[] = "-- 默认角色";
$output[] = "INSERT INTO `yxshop_admin_roles` (`id`, `role_name`, `role_desc`, `app_id`, `created_at`, `updated_at`) VALUES";
$output[] = "(1, '超级管理员', '拥有所有权限', 0, @NOW, @NOW),";
$output[] = "(2, '运营', '商品和订单管理', 0, @NOW, @NOW),";
$output[] = "(3, '客服', '仅查看订单和用户', 0, @NOW, @NOW)";
$output[] = "ON DUPLICATE KEY UPDATE `updated_at` = @NOW;";
$output[] = "";

// 默认会员等级
$output[] = "-- 默认会员等级";
$output[] = "INSERT INTO `yxshop_user_levels` (`id`, `key`, `name`, `agio`, `integral`, `shop_money`, `sort`, `desc`, `app_id`, `created_at`, `updated_at`) VALUES";
$output[] = "(7100000000001, 'one', '普通会员', 10.00, 0, 0, 100, '注册即成为普通会员', @APP_ID, @NOW, @NOW),";
$output[] = "(7100000000002, 'two', '白银会员', 9.50, 1000, 99, 80, '累计消费满1000元', @APP_ID, @NOW, @NOW),";
$output[] = "(7100000000003, 'three', '黄金会员', 9.00, 5000, 299, 60, '累计消费满5000元', @APP_ID, @NOW, @NOW),";
$output[] = "(7100000000004, 'four', '铂金会员', 8.50, 20000, 999, 40, '累计消费满20000元', @APP_ID, @NOW, @NOW),";
$output[] = "(7100000000005, 'five', '钻石会员', 8.00, 50000, 2999, 20, '累计消费满50000元', @APP_ID, @NOW, @NOW)";
$output[] = "ON DUPLICATE KEY UPDATE `updated_at` = @NOW;";
$output[] = "";

// 默认商品分类
$output[] = "-- 默认商品分类";
$output[] = "INSERT INTO `yxshop_categories` (`id`, `name`, `parent_id`, `sort`, `is_visible`, `level`, `status`, `app_id`, `created_at`, `updated_at`) VALUES";
$output[] = "(7200000000001, '手机数码', 0, 100, 1, '1', 1, @APP_ID, @NOW, @NOW),";
$output[] = "(7200000000002, '家用电器', 0, 90, 1, '1', 1, @APP_ID, @NOW, @NOW),";
$output[] = "(7200000000003, '服饰鞋包', 0, 80, 1, '1', 1, @APP_ID, @NOW, @NOW),";
$output[] = "(7200000000004, '食品生鲜', 0, 70, 1, '1', 1, @APP_ID, @NOW, @NOW),";
$output[] = "(7200000000005, '美妆护肤', 0, 60, 1, '1', 1, @APP_ID, @NOW, @NOW)";
$output[] = "ON DUPLICATE KEY UPDATE `updated_at` = @NOW;";
$output[] = "";

// 默认系统设置
$output[] = "-- 默认系统设置";
$output[] = "INSERT INTO `yxshop_settings` (`key`, `value`, `type`, `group`, `description`, `app_id`, `created_at`, `updated_at`) VALUES";
$settings = [
    ['site_name', 'YXShop商城', 'string', 'general', '站点名称'],
    ['site_logo', '', 'string', 'general', '站点Logo'],
    ['site_description', 'YXShop开源商城系统', 'string', 'general', '站点描述'],
    ['site_keywords', '商城,电商,开店', 'string', 'general', '站点关键词'],
    ['site_url', 'http://localhost:8777', 'string', 'general', '站点URL'],
    ['icp_number', '', 'string', 'general', 'ICP备案号'],
    ['contact_phone', '', 'string', 'general', '联系电话'],
    ['contact_email', '', 'string', 'general', '联系邮箱'],
    ['contact_address', '', 'string', 'general', '联系地址'],
    ['currency', 'CNY', 'string', 'general', '默认货币'],
    ['language', 'zh_CN', 'string', 'general', '默认语言'],
    ['timezone', 'Asia/Shanghai', 'string', 'general', '时区'],
    ['admin_login_attempts', '5', 'int', 'security', '登录失败锁定次数'],
    ['admin_lock_duration', '30', 'int', 'security', '锁定时长(分钟)'],
    ['admin_session_timeout', '7200', 'int', 'security', '会话超时(秒)'],
    ['admin_password_expiry', '90', 'int', 'security', '密码有效期(天)'],
    ['admin_password_history', '5', 'int', 'security', '密码历史记录数'],
    ['dos_prevent', '1', 'bool', 'security', '防CC攻击'],
    ['dos_attempts', '60', 'int', 'security', 'CC攻击阈值'],
    ['order_auto_cancel', '30', 'int', 'trade', '订单自动取消(分钟)'],
    ['order_auto_receive', '7', 'int', 'trade', '订单自动收货(天)'],
    ['order_refund_days', '7', 'int', 'trade', '退款期限(天)'],
    ['free_shipping_threshold', '0', 'float', 'trade', '免邮门槛'],
    ['default_shipping_fee', '0', 'float', 'trade', '默认运费'],
];
foreach ($settings as $i => $s) {
    $comma = $i < count($settings) - 1 ? ',' : '';
    $value = is_numeric($s[1]) ? $s[1] : "'" . $s[1] . "'";
    $output[] = "('" . $s[0] . "', " . $value . ", '" . $s[2] . "', '" . $s[3] . "', '" . $s[4] . "', 0, @NOW, @NOW)" . $comma;
}
$output[] = "ON DUPLICATE KEY UPDATE `updated_at` = @NOW;";
$output[] = "";

// 默认菜单
$output[] = "-- 默认后台菜单";
$output[] = "INSERT INTO `yxshop_admin_menus` (`id`, `parent_id`, `name`, `model`, `url`, `icon`, `sort`, `is_show`, `permission`, `method`, `app_id`, `created_at`, `updated_at`) VALUES";
$menus = [
    [7300000000001, 0, '仪表盘', 'dashboard', '/admin/dashboard', 'fas fa-tachometer-alt', 10, 1, 'admin/dashboard', 'GET'],
    [7300000000002, 0, '商品管理', 'item', NULL, 'fas fa-cubes text-primary', 20, 1, NULL, 'GET'],
    [7300000000003, 7300000000002, '商品列表', 'item', '/admin/item', 'fas fa-boxes', 10, 1, 'admin/item', 'GET'],
    [7300000000004, 7300000000002, '添加商品', 'item', '/admin/item/create', 'fas fa-plus-square', 20, 1, 'admin/item/create', 'GET'],
    [7300000000005, 7300000000002, '商品分类', 'item', '/admin/category', 'fas fa-bullseye', 30, 1, 'admin/category', 'GET'],
    [7300000000006, 7300000000002, '品牌管理', 'item', '/admin/brand', 'fas fa-tag', 40, 1, 'admin/brand', 'GET'],
    [7300000000007, 0, '订单管理', 'order', NULL, 'fas fa-shopping-cart text-warning', 30, 1, NULL, 'GET'],
    [7300000000008, 7300000000007, '订单列表', 'order', '/admin/order', 'fas fa-boxes', 10, 1, 'admin/order', 'GET'],
    [7300000000009, 7300000000007, '售后管理', 'order', '/admin/after-sales', 'fas fa-undo', 20, 1, 'admin/after-sales', 'GET'],
    [7300000000010, 0, '用户管理', 'user', NULL, 'fa fa-user text-success', 40, 1, NULL, 'GET'],
    [7300000000011, 7300000000010, '用户列表', 'user', '/admin/user', 'fas fa-users', 10, 1, 'admin/user', 'GET'],
    [7300000000012, 7300000000010, '会员等级', 'user', '/admin/user/levels', 'fas fa-medal', 20, 1, 'admin/user/levels', 'GET'],
    [7300000000013, 0, '营销管理', 'marketing', NULL, 'fas fa-chart-line text-info', 50, 1, NULL, 'GET'],
    [7300000000014, 7300000000013, '优惠券', 'marketing', '/admin/coupon', 'fa fa-puzzle-piece', 10, 1, 'admin/coupon', 'GET'],
    [7300000000015, 7300000000013, '促销活动', 'marketing', '/admin/promotion', 'fas fa-gift', 20, 1, 'admin/promotion', 'GET'],
    [7300000000016, 7300000000013, '广告管理', 'marketing', '/admin/advertisement', 'fas fa-image', 30, 1, 'admin/advertisement', 'GET'],
    [7300000000017, 0, '内容管理', 'content', NULL, 'fas fa-newspaper text-secondary', 60, 1, NULL, 'GET'],
    [7300000000018, 7300000000017, '文章管理', 'content', '/admin/article', 'fas fa-file-alt', 10, 1, 'admin/article', 'GET'],
    [7300000000019, 7300000000017, '文章分类', 'content', '/admin/article-category', 'fas fa-folder', 20, 1, 'admin/article-category', 'GET'],
    [7300000000020, 0, '系统设置', 'setting', NULL, 'fa fa-wrench text-primary', 70, 1, NULL, 'GET'],
    [7300000000021, 7300000000020, '基本设置', 'setting', '/admin/setting', 'fas fa-store', 10, 1, 'admin/setting', 'GET'],
    [7300000000022, 7300000000020, '支付设置', 'setting', '/admin/setting/payment', 'fas fa-credit-card', 20, 1, 'admin/setting/payment', 'GET'],
    [7300000000023, 7300000000020, '配送设置', 'setting', '/admin/setting/shipping', 'fas fa-shipping-fast', 30, 1, 'admin/setting/shipping', 'GET'],
    [7300000000024, 7300000000020, '管理员管理', 'setting', '/admin/admin', 'fas fa-user-shield', 40, 1, 'admin/admin', 'GET'],
    [7300000000025, 7300000000020, '角色权限', 'setting', '/admin/role', 'fas fa-key', 50, 1, 'admin/role', 'GET'],
    [7300000000026, 7300000000020, '安全设置', 'setting', '/admin/setting/security', 'fas fa-shield-alt', 60, 1, 'admin/setting/security', 'GET'],
    [7300000000027, 0, '小程序装修', 'miniprogram', NULL, 'fas fa-mobile-alt text-info', 80, 1, NULL, 'GET'],
    [7300000000028, 7300000000027, '页面管理', 'miniprogram', '/admin/mini-page', 'fas fa-layout', 10, 1, 'admin/mini-page', 'GET'],
    [7300000000029, 7300000000027, '底部导航', 'miniprogram', '/admin/mini-tab-bar', 'fas fa-bars', 20, 1, 'admin/mini-tab-bar', 'GET'],
    [7300000000030, 7300000000027, '主题设置', 'miniprogram', '/admin/mini-theme', 'fas fa-palette', 30, 1, 'admin/mini-theme', 'GET'],
];
foreach ($menus as $i => $m) {
    $comma = $i < count($menus) - 1 ? ',' : '';
    $url = $m[4] === NULL ? 'NULL' : "'" . $m[4] . "'";
    $output[] = "(" . $m[0] . ", " . $m[1] . ", '" . $m[2] . "', '" . $m[3] . "', " . $url . ", '" . $m[5] . "', " . $m[6] . ", " . $m[7] . ", '" . $m[8] . "', '" . $m[9] . "', 0, @NOW, @NOW)" . $comma;
}
$output[] = "ON DUPLICATE KEY UPDATE `updated_at` = @NOW;";
$output[] = "";

// 迁移记录
$output[] = "-- 迁移记录表";
$output[] = "CREATE TABLE IF NOT EXISTS `yxshop_schema_migrations` (";
$output[] = "  `version` VARCHAR(30) NOT NULL DEFAULT '',";
$output[] = "  `name` VARCHAR(100) NOT NULL DEFAULT '',";
$output[] = "  `executed_at` INT(11) UNSIGNED NOT NULL DEFAULT 0,";
$output[] = "  PRIMARY KEY (`version`)";
$output[] = ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='迁移记录表';";
$output[] = "";

$output[] = "-- 标记所有迁移为已执行";
foreach ($migrations as $migFile) {
    $filename = basename($migFile, '.sql');
    // 直接用文件名作为版本号，保证唯一且可追溯
    $output[] = "INSERT INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('" . $filename . "', '" . $filename . "', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;";
}
$output[] = "";

$output[] = "SET FOREIGN_KEY_CHECKS = 1;";
$output[] = "";
$output[] = "-- ============================================================";
$output[] = "-- 初始化完成！";
$output[] = "-- 默认管理员: admin / admin123";
$output[] = "-- 请在首次登录后立即修改密码！";
$output[] = "-- ============================================================";

// 输出
$result = implode("\n", $output);
echo $result;

// 统计
$tableCount = substr_count($result, 'CREATE TABLE');
$seedCount = substr_count($result, 'INSERT INTO');
fwrite(STDERR, "\n--- 生成完成 ---\n");
fwrite(STDERR, "表结构: $tableCount 张表\n");
fwrite(STDERR, "种子数据: $seedCount 条 INSERT\n");
fwrite(STDERR, "文件大小: " . round(strlen($result) / 1024, 1) . " KB\n");
