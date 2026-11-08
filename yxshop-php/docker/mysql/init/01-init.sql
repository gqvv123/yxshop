-- YXShop 数据库初始化脚本

-- 创建数据库
CREATE DATABASE IF NOT EXISTS `yxshop` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 使用数据库
USE `yxshop`;

-- 创建用户表
CREATE TABLE IF NOT EXISTS `yxshop_users` (
  `user_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '用户ID',
  `username` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '用户名',
  `password` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '密码',
  `nickname` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '昵称',
  `avatar` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '头像',
  `phone` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '手机号',
  `email` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '邮箱',
  `gender` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '性别 (0未知 1男 2女)',
  `birthday` date NULL DEFAULT NULL COMMENT '生日',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态 (0禁用 1正常)',
  `level_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '会员等级ID',
  `points` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '积分',
  `balance` decimal(10,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '余额',
  `version` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'basic' COMMENT '用户版本',
  `app_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '小程序ID',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`user_id`) USING BTREE,
  UNIQUE INDEX `username`(`username`) USING BTREE,
  UNIQUE INDEX `phone`(`phone`) USING BTREE,
  UNIQUE INDEX `email`(`email`) USING BTREE,
  INDEX `level_id`(`level_id`) USING BTREE,
  INDEX `status`(`status`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '用户表' ROW_FORMAT = Dynamic;

-- 创建管理员表
CREATE TABLE IF NOT EXISTS `yxshop_admins` (
  `admin_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '管理员ID',
  `username` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '用户名',
  `password` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '密码',
  `nickname` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '昵称',
  `avatar` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '头像',
  `phone` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '手机号',
  `email` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '邮箱',
  `role_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '角色ID',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态 (0禁用 1正常)',
  `last_login_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '最后登录时间',
  `last_login_ip` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '最后登录IP',
  `app_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '小程序ID',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`admin_id`) USING BTREE,
  UNIQUE INDEX `username`(`username`) USING BTREE,
  UNIQUE INDEX `phone`(`phone`) USING BTREE,
  UNIQUE INDEX `email`(`email`) USING BTREE,
  INDEX `role_id`(`role_id`) USING BTREE,
  INDEX `status`(`status`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '管理员表' ROW_FORMAT = Dynamic;

-- 插入默认管理员
INSERT INTO `yxshop_admins` (`username`, `password`, `nickname`, `email`, `role_id`, `status`, `app_id`, `created_at`, `updated_at`) VALUES
('admin', '$2y$10$K3JmtaHpviRgK9NyNfbn2.h/hKdf.7mg5kgh5aKY62Vv3EUYSM/Bm', '超级管理员', 'admin@yxshop.com', 1, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 创建角色表
CREATE TABLE IF NOT EXISTS `yxshop_roles` (
  `role_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '角色ID',
  `role_name` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '角色名称',
  `role_desc` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '角色描述',
  `permissions` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '权限列表 (JSON格式)',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态 (0禁用 1正常)',
  `app_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '小程序ID',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`role_id`) USING BTREE,
  UNIQUE INDEX `role_name`(`role_name`) USING BTREE,
  INDEX `status`(`status`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '角色表' ROW_FORMAT = Dynamic;

-- 插入默认角色
INSERT INTO `yxshop_roles` (`role_name`, `role_desc`, `permissions`, `status`, `app_id`, `created_at`, `updated_at`) VALUES
('超级管理员', '拥有所有权限', '["*"]', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('普通管理员', '拥有基础管理权限', '["user.view", "product.view", "order.view"]', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 创建应用表
CREATE TABLE IF NOT EXISTS `yxshop_apps` (
  `app_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '应用ID',
  `app_name` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '应用名称',
  `app_type` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '应用类型 (1小程序 2H5 3APP)',
  `app_key` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '应用密钥',
  `app_secret` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '应用秘钥',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态 (0禁用 1正常)',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`app_id`) USING BTREE,
  UNIQUE INDEX `app_key`(`app_key`) USING BTREE,
  INDEX `app_type`(`app_type`) USING BTREE,
  INDEX `status`(`status`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '应用表' ROW_FORMAT = Dynamic;

-- 插入默认应用
INSERT INTO `yxshop_apps` (`app_name`, `app_type`, `app_key`, `app_secret`, `status`, `created_at`, `updated_at`) VALUES
('YXShop小程序', 1, 'miniprogram_key', 'miniprogram_secret', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('YXShop H5', 2, 'h5_key', 'h5_secret', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('YXShop APP', 3, 'app_key', 'app_secret', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 创建管理员操作日志表
CREATE TABLE IF NOT EXISTS `admin_operation_logs` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '日志ID',
  `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '管理员ID',
  `username` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '管理员用户名',
  `app_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '小程序ID',
  `module` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '操作模块',
  `action` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '操作动作',
  `description` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '操作描述',
  `ip_address` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT 'IP地址',
  `user_agent` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '浏览器标识',
  `request_data` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '请求数据(JSON)',
  `response_data` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '响应数据(JSON)',
  `status` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'success' COMMENT '状态(success/failed)',
  `error_message` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '错误信息',
  `execution_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '执行时间(毫秒)',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE,
  INDEX `module`(`module`) USING BTREE,
  INDEX `status`(`status`) USING BTREE,
  INDEX `created_at`(`created_at`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '管理员操作日志表' ROW_FORMAT = Dynamic;

-- 创建积分日志表
CREATE TABLE IF NOT EXISTS `yxshop_integral_log` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '日志ID',
  `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
  `type` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '类型 (1获得 2消费)',
  `amount` int(11) NOT NULL DEFAULT 0 COMMENT '积分变动数量',
  `before_balance` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '变动前积分',
  `after_balance` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '变动后积分',
  `note` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '备注',
  `order_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联订单ID',
  `app_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '小程序ID',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE,
  INDEX `type`(`type`) USING BTREE,
  INDEX `order_id`(`order_id`) USING BTREE,
  INDEX `created_at`(`created_at`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '积分日志表' ROW_FORMAT = Dynamic;

-- 创建用户余额变动日志表
CREATE TABLE IF NOT EXISTS `yxshop_user_money_log` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '日志ID',
  `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
  `app_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '小程序ID',
  `money` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '变动金额(正数收入负数支出)',
  `before_money` decimal(10,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '变动前余额',
  `after_money` decimal(10,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '变动后余额',
  `note` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '备注',
  `type` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '类型 (1收入 2支出)',
  `order_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联订单ID',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE,
  INDEX `type`(`type`) USING BTREE,
  INDEX `order_id`(`order_id`) USING BTREE,
  INDEX `created_at`(`created_at`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '用户余额变动日志表' ROW_FORMAT = Dynamic;

-- 创建优惠活动表
CREATE TABLE IF NOT EXISTS `yxshop_discounts` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '优惠ID',
  `name` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '优惠名称',
  `type` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '类型 (1百分比 2固定金额)',
  `value` decimal(10,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '优惠值(百分比或固定金额)',
  `min_amount` decimal(10,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '最低消费金额',
  `max_discount` decimal(10,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '最大优惠金额(0无限制)',
  `start_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '开始时间',
  `end_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '结束时间',
  `is_active` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否启用 (0否 1是)',
  `app_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '小程序ID',
  `sort` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序',
  `is_delete` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否删除 (0否 1是)',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `type`(`type`) USING BTREE,
  INDEX `is_active`(`is_active`) USING BTREE,
  INDEX `start_time`(`start_time`) USING BTREE,
  INDEX `end_time`(`end_time`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '优惠活动表' ROW_FORMAT = Dynamic;
