-- ============================================================
-- YXShop 开源版完整数据库初始化脚本
-- 版本: 1.0.0 (2026-08-23)
-- 
-- 使用方法:
--   mysql -u root -p yxshop < database/yxshop_open_source_default.sql
--
-- 包含内容:
--   1. 全部开源版核心表结构（90+ 张表）
--   2. 默认管理员账号、角色、菜单
--   3. 默认系统设置、会员等级、分类
--   4. 迁移记录表
--
-- 注意:
--   - 企业版/商业版表由对应迁移脚本按需添加
--   - 所有表使用 utf8mb4 字符集
--   - 时间字段统一使用 int(11) UNIX 时间戳
--   - ID 使用 Snowflake 雪花算法（bigint UNSIGNED）
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- 一、基线表结构（来自 yxshop_stuc.sql）
-- ============================================================

-- ----------------------------
-- Table structure for yxshop_admin_auths
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_admin_auths`;
CREATE TABLE `yxshop_admin_auths`  (
  `id` bigint(20) UNSIGNED NOT NULL,
  `admin_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `auth_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `idx_admin_auth`(`admin_id`, `auth_id`) USING BTREE,
  INDEX `idx_admin_id`(`admin_id`) USING BTREE,
  INDEX `idx_auth_id`(`auth_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '管理员权限关联表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_admin_logs
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_admin_logs`;
CREATE TABLE `yxshop_admin_logs`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `admin_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `admin_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '管理员名称',
  `module` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '操作模块',
  `action` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '操作动作',
  `operation_type` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '操作类型(10查看 20新增 30修改 40删除 50导出 60导入 70登录 80登出)',
  `target_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '操作对象类型',
  `target_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `target_name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '操作对象名称',
  `request_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '请求URL',
  `request_method` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '请求方法',
  `request_params` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '请求参数',
  `response_data` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '响应数据',
  `operation_result` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '操作结果(10成功 20失败)',
  `error_message` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '错误信息',
  `ip` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'IP地址',
  `user_agent` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '用户代理',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_admin_id`(`admin_id`) USING BTREE,
  INDEX `idx_module_action`(`module`, `action`) USING BTREE,
  INDEX `idx_operation_type`(`operation_type`) USING BTREE,
  INDEX `idx_target`(`target_type`, `target_id`) USING BTREE,
  INDEX `idx_created_at`(`created_at`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '管理员操作日志表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_admin_menus
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_admin_menus`;
CREATE TABLE `yxshop_admin_menus`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `parent_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '父级ID(0为顶级)',
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '菜单名称',
  `model` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '模块标识/分组',
  `url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '菜单路由URL',
  `icon` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '图标类名',
  `sort` int(11) NOT NULL DEFAULT 0 COMMENT '排序(小在前)',
  `is_show` tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否显示 1显示 0隐藏',
  `permission` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '所需权限标识',
  `method` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'GET' COMMENT 'HTTP方法',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_parent_id`(`parent_id`) USING BTREE,
  INDEX `idx_app_id`(`app_id`) USING BTREE,
  INDEX `idx_url`(`url`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 74 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '后台菜单表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for yxshop_admin_role_auths
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_admin_role_auths`;
CREATE TABLE `yxshop_admin_role_auths`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '角色ID',
  `auth_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '权限(菜单)ID',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `idx_role_auth`(`role_id`, `auth_id`) USING BTREE,
  INDEX `idx_role_id`(`role_id`) USING BTREE,
  INDEX `idx_auth_id`(`auth_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 78 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '角色权限关联表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_admin_roles
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_admin_roles`;
CREATE TABLE `yxshop_admin_roles`  (
  `id` bigint(20) UNSIGNED NOT NULL,
  `role_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '角色名称',
  `role_desc` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '角色描述',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除时间(0未删)',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_app_id`(`app_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '管理员角色表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_admins
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_admins`;
CREATE TABLE `yxshop_admins`  (
  `id` bigint(20) UNSIGNED NOT NULL,
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '用户名',
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '登录密码',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除时间(0未删)',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：0=禁用，1=启用',
  `nickname` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `is_super_admin` int(2) NOT NULL DEFAULT 0 COMMENT '超级管理员',
  `pid` int(7) NULL DEFAULT 0 COMMENT '主帐号id',
  `role_id` bigint(20) UNSIGNED NULL DEFAULT NULL,
  `phone` varchar(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '手机号',
  `note` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '备注',
  `vip` int(2) NOT NULL DEFAULT 0 COMMENT 'vip等级',
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'email',
  `avatar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '头像',
  `last_login_at` int(11) NULL DEFAULT 0 COMMENT '最后登录时间',
  `two_factor_secret` varchar(255) NULL DEFAULT NULL COMMENT '2FA密钥',
  `two_factor_enabled` tinyint(1) NOT NULL DEFAULT 0 COMMENT '2FA开关',
  `login_fail_count` int(11) NOT NULL DEFAULT 0 COMMENT '连续登录失败次数',
  `locked_until` int(11) NULL DEFAULT NULL COMMENT '锁定截止时间',
  `password_changed_at` int(11) NULL DEFAULT NULL COMMENT '密码修改时间',
  `force_password_change` tinyint(1) NOT NULL DEFAULT 0 COMMENT '强制改密',
  `wechat_openid` varchar(128) NULL DEFAULT NULL COMMENT '微信openid',
  `wechat_bound_at` int(11) NOT NULL DEFAULT 0 COMMENT '微信绑定时间',
  `wechat_nickname` varchar(100) NULL DEFAULT NULL COMMENT '微信昵称',
  `wechat_avatar` varchar(500) NULL DEFAULT NULL COMMENT '微信头像',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `user_name`(`username`) USING BTREE,
  INDEX `id`(`id`) USING BTREE,
  INDEX `idx_app_username`(`app_id`, `username`) USING BTREE,
  INDEX `idx_is_super`(`is_super_admin`) USING BTREE,
  INDEX `idx_created_at`(`created_at`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '商家用户记录表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_advertisements
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_advertisements`;
CREATE TABLE `yxshop_advertisements`  (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `count` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` int(11) NOT NULL,
  `updated_at` int(11) NOT NULL,
  `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除时间(0未删)',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '1启用 0禁用',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '应用ID(0=全局)',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_app_id`(`app_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_after_sales
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_after_sales`;
CREATE TABLE `yxshop_after_sales`  (
  `id` bigint(20) UNSIGNED NOT NULL,
  `score` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '评分 (10好评 20中评 30差评)',
  `content` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '后售申请内容',
  `is_picture` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否为图片评价',
  `sort` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态(0隐藏 1显示)',
  `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `order_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `item_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `order_goods_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `sub_status` int(7) NOT NULL DEFAULT 0 COMMENT '售后状态10为申请20为已消取30为已处理',
  `sub_type` int(7) NOT NULL DEFAULT 0 COMMENT '售后方式10为退款20为换货',
  `seller_remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '卖家留言',
  `buyer_name` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '收货人姓名',
  `buyer_phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '联系电话',
  `num` int(6) UNSIGNED NOT NULL DEFAULT 0 COMMENT '数量',
  `delivery_status` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '发货状态(10未发货 20已发货)',
  `delivery_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '发货时间',
  `express_no` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `express_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `buyer_address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '联系地址',
  `reasons` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '申请原因',
  `goods_status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '货物状态',
  `reasonsText` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `refund_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '0' COMMENT '退款ID',
  `refund_status` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '付款状态(10未付款 20已付款)',
  `refund_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '付款时间',
  `receipt_status` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '收货状态(10未收货 20已收货)',
  `receipt_time` int(11) NULL DEFAULT 0 COMMENT '收货时间',
  `end_time` int(11) NOT NULL DEFAULT 0 COMMENT '结束时间',
  `buyer_delivery_time` int(11) NOT NULL DEFAULT 0 COMMENT '发货时间',
  `buyer_delivery_status` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '发货状态(10未发货 20已发货)',
  `buyer_receipt_status` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '收货状态(10未收货 20已收货)',
  `buyer_receipt_time` int(11) NULL DEFAULT NULL COMMENT '收货时间',
  `buyer_express_no` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `buyer_express_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `buyer_express_name` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `express_name` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `user_id`(`user_id`, `order_id`) USING BTREE,
  INDEX `idx_user_status`(`user_id`, `sub_status`) USING BTREE,
  INDEX `idx_order_status`(`order_id`, `sub_status`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '订单评价记录表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_after_sales_details
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_after_sales_details`;
CREATE TABLE `yxshop_after_sales_details`  (
  `id` bigint(20) UNSIGNED NOT NULL,
  `content` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '后售申请内容',
  `is_picture` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否为图片评价',
  `sort` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态(0隐藏 1显示)',
  `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `order_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `order_goods_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `type` tinyint(3) NOT NULL DEFAULT 0 COMMENT '0 用户 1商家',
  `after_sales_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联售后主表ID',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `user_id`(`user_id`, `order_id`) USING BTREE,
  INDEX `idx_after_sales_id`(`after_sales_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '订单评价记录表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_after_sales_images
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_after_sales_images`;
CREATE TABLE `yxshop_after_sales_images`  (
  `id` bigint(20) UNSIGNED NOT NULL,
  `image_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '图片地址',
  `app_id` bigint(20) NOT NULL,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `after_sales_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联售后主表ID',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_after_sales_id`(`after_sales_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '评价图片表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_agent_capitals
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_agent_capitals`;
CREATE TABLE `yxshop_agent_capitals`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键id',
  `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `flow_type` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '资金流动类型 (10佣金收入 20提现支出)',
  `money` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '金额',
  `describe` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '描述',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE,
  INDEX `idx_user_type_time`(`user_id`, `flow_type`, `created_at`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '分销商资金明细表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_agent_withdraws
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_agent_withdraws`;
CREATE TABLE `yxshop_agent_withdraws`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键id',
  `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `money` decimal(10, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '提现金额',
  `pay_type` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '打款方式 (10微信 20支付宝 30银行卡)',
  `alipay_name` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '支付宝姓名',
  `alipay_account` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '支付宝账号',
  `bank_name` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '开户行名称',
  `bank_account` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '银行开户名',
  `bank_card` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '银行卡号',
  `apply_status` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '申请状态 (10待审核 20审核通过 30驳回 40已打款)',
  `audit_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '审核时间',
  `reject_reason` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '驳回原因',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `type` tinyint(3) NOT NULL DEFAULT 0 COMMENT '0 为现金提现  1 为积分提现',
  `weixin_name` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `weixin_account` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE,
  INDEX `idx_user_status`(`user_id`, `apply_status`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '分销商提现明细表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_agents
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_agents`;
CREATE TABLE `yxshop_agents`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `parent_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `level` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '代理等级',
  `agent_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '代理名称',
  `agent_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '代理编码',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态 1启用 0禁用',
  `commission_rate` decimal(5, 2) NOT NULL DEFAULT 0.00 COMMENT '佣金比例',
  `total_commission` decimal(12, 2) NOT NULL DEFAULT 0.00 COMMENT '累计佣金',
  `available_commission` decimal(12, 2) NOT NULL DEFAULT 0.00 COMMENT '可用佣金',
  `frozen_commission` decimal(12, 2) NOT NULL DEFAULT 0.00 COMMENT '冻结佣金',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `updated_at` int(10) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_agent_code`(`agent_code`) USING BTREE,
  INDEX `idx_user_id`(`user_id`) USING BTREE,
  INDEX `idx_parent_id`(`parent_id`) USING BTREE,
  INDEX `idx_app_id`(`app_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '代理商' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for yxshop_app_helps
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_app_helps`;
CREATE TABLE `yxshop_app_helps`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键id',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '帮助标题',
  `content` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '帮助内容',
  `sort` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序(数字越小越靠前)',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '微信小程序帮助' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_app_navbars
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_app_navbars`;
CREATE TABLE `yxshop_app_navbars`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键id',
  `title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '小程序标题',
  `text_color` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '顶部导航文字颜色(10黑色 20白色)',
  `background_color` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '顶部导航背景色',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '应用ID(0=全局)',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_app_id`(`app_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '微信小程序导航栏设置' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_app_pages
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_app_pages`;
CREATE TABLE `yxshop_app_pages`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '页面id',
  `type` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '页面类型(10首页 20自定义页)',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '页面名称',
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '页面数据',
  `sort` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '??????',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `app_id`(`app_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '微信小程序diy页面表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_app_prepay_ids
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_app_prepay_ids`;
CREATE TABLE `yxshop_app_prepay_ids`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键id',
  `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `order_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `prepay_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '微信支付prepay_id',
  `can_use_times` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '可使用次数',
  `used_times` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '已使用次数',
  `pay_status` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '支付状态(1已支付)',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `expiry_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '过期时间',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `order_id`(`order_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '小程序prepay_id记录' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_applies
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_applies`;
CREATE TABLE `yxshop_applies`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键id',
  `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '通过类型 (10申请中 20已通过)',
  `describe` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '描述',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `phone` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `referee_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '微信昵称',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '微信昵称',
  `apply_type` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '申请类型',
  `apply_status` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '申请状态',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '分销商资金明细表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_apps
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_apps`;
CREATE TABLE `yxshop_apps`  (
  `id` bigint(20) UNSIGNED NOT NULL,
  `app_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '应用名称',
  `app_type` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '应用类型 (1小程序 2H5 3APP)',
  `app_key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '应用密钥',
  `app_secret` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '应用秘钥',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态 (0禁用 1正常)',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除时间(0未删)',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `app_key`(`app_key`) USING BTREE,
  INDEX `app_type`(`app_type`) USING BTREE,
  INDEX `status`(`status`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '应用表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_article_categories
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_article_categories`;
CREATE TABLE `yxshop_article_categories`  (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '分类名称',
  `parent_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `sort` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态 1启用 0禁用',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `updated_at` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除时间(0未删)',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_parent_id`(`parent_id`) USING BTREE,
  INDEX `idx_app_id`(`app_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '文章分类' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for yxshop_articles
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_articles`;
CREATE TABLE `yxshop_articles`  (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '标题',
  `category_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `author` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '作者',
  `cover` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '封面图',
  `description` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '摘要',
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '内容',
  `sort` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态 1启用 0禁用',
  `is_top` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否置顶',
  `view_count` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '浏览量',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `updated_at` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除时间(0未删)',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_category_id`(`category_id`) USING BTREE,
  INDEX `idx_app_id`(`app_id`) USING BTREE,
  INDEX `idx_status`(`status`) USING BTREE,
  INDEX `idx_deleted_at`(`deleted_at`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '文章' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for yxshop_brands
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_brands`;
CREATE TABLE `yxshop_brands`  (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '品牌名称',
  `logo` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '品牌logo',
  `phone` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '电话',
  `address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '商家地址',
  `desc` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '品牌描述',
  `url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '品牌地址',
  `seo_title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'SEO标题',
  `seo_keywords` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'SEO关键词',
  `seo_description` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'SEO描述',
  `sort` tinyint(3) UNSIGNED NOT NULL DEFAULT 50 COMMENT '排序',
  `cat_name` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '品牌分类',
  `parent_cat_id` bigint(20) NULL DEFAULT 0,
  `cat_id` bigint(20) NULL DEFAULT 0,
  `is_hot` tinyint(1) NULL DEFAULT 0 COMMENT '是否推荐',
  `app_id` bigint(20) NOT NULL,
  `created_at` int(11) NOT NULL,
  `updated_at` int(11) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：0=禁用，1=启用',
  `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除时间(0未删)',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_app_cat_hot`(`app_id`, `cat_id`, `is_hot`) USING BTREE,
  INDEX `idx_deleted_at`(`deleted_at`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '商品品牌表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_card
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_card`;
CREATE TABLE `yxshop_card`  (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '卡密名称',
  `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '所属用户(0未发放)',
  `card` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '卡号',
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '卡密',
  `nickName` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '持有人昵称',
  `card_type` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '卡密类型(10卡密 20链接)',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态(0未用 1已用 2已禁用)',
  `balance` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '余额',
  `discount` decimal(5, 2) NOT NULL DEFAULT 10.00 COMMENT '折扣',
  `item_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联商品',
  `used_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '使用时间',
  `expire_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '过期时间',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_user_id`(`user_id`) USING BTREE,
  INDEX `idx_item_id`(`item_id`) USING BTREE,
  INDEX `idx_status`(`status`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '虚拟商品卡密表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for yxshop_carts
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_carts`;
CREATE TABLE `yxshop_carts`  (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `item_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '商品ID',
  `item_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '商品名称快照',
  `item_image` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '商品主图快照',
  `spec_key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '规格组合key',
  `spec_key_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '规格名称快照',
  `price` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '加入时单价',
  `quantity` int(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT '数量',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_user_item_spec`(`user_id`, `item_id`, `spec_key`) USING BTREE,
  INDEX `idx_user_id`(`user_id`) USING BTREE,
  INDEX `idx_app_user`(`app_id`, `user_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '购物车表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for yxshop_categories
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_categories`;
CREATE TABLE `yxshop_categories`  (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(90) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '商品分类名称',
  `parent_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `sort` tinyint(1) UNSIGNED NOT NULL DEFAULT 50 COMMENT '顺序排序',
  `is_visible` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否显示',
  `image` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '分类图片',
  `seo_title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'SEO标题',
  `seo_keywords` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'SEO关键词',
  `seo_description` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'SEO描述',
  `icon` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '分类图标',
  `app_id` bigint(20) NOT NULL,
  `created_at` int(11) NOT NULL,
  `updated_at` int(11) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：0=禁用，1=启用',
  `level` varchar(90) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '等级',
  `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除时间(0未删)',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_app_pid_show`(`app_id`, `parent_id`, `is_visible`) USING BTREE,
  INDEX `idx_parent_id`(`parent_id`) USING BTREE,
  INDEX `idx_deleted_at`(`deleted_at`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '商品分类表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_comments
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_comments`;
CREATE TABLE `yxshop_comments`  (
  `id` bigint(20) UNSIGNED NOT NULL,
  `score` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '评分 (10好评 20中评 30差评)',
  `content` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '评价内容',
  `images` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '评价图片(JSON数组)',
  `is_anonymous` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否匿名评价(0否 1是)',
  `reply_content` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '商家回复内容',
  `reply_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '商家回复时间',
  `is_recommend` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否推荐(0否 1是)',
  `sort` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '评价排序',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态(0隐藏 1显示)',
  `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `order_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `item_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `order_goods_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '??????',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `user_id`(`user_id`, `order_id`) USING BTREE,
  INDEX `idx_item_status`(`item_id`, `status`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '订单评价记录表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_coupon_goods
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_coupon_goods`;
CREATE TABLE `yxshop_coupon_goods`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键id',
  `coupon_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `goods_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '??????',
  `status` tinyint(1) NULL DEFAULT 1,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `coupon`(`coupon_id`) USING BTREE,
  INDEX `coupon_id`(`goods_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '优惠券指定商品记录表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_coupon_receive_logs
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_coupon_receive_logs`;
CREATE TABLE `yxshop_coupon_receive_logs`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `coupon_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `user_coupon_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '生成的user_coupon记录',
  `source` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '领取来源(10主动 20后台发放 30活动)',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_coupon_user`(`coupon_id`, `user_id`) USING BTREE,
  INDEX `idx_user_id`(`user_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '优惠券领取流水' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for yxshop_coupons
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_coupons`;
CREATE TABLE `yxshop_coupons`  (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '优惠券名称',
  `color` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '优惠券颜色(10蓝 20红 30紫 40黄)',
  `type` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '优惠券类型(10满减券 20折扣券)',
  `discount_amount` decimal(10, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '满减券-减免金额',
  `discount_rate` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '折扣券-折扣率(0-100)',
  `min_amount` decimal(10, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '最低消费金额',
  `expiry_type` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '到期类型(10领取后生效 20固定时间)',
  `expiry_days` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '领取后生效-有效天数',
  `start_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '固定时间-开始时间',
  `end_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '固定时间-结束时间',
  `scope` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '适用范围(10全部商品 20指定商品)',
  `total_quantity` int(11) NOT NULL DEFAULT 0 COMMENT '发放总数量(-1为不限制)',
  `used_quantity` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '已领取数量',
  `sort` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序方式(数字越小越靠前)',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：0=禁用，1=启用',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除时间(0未删)',
  `min_quantity` int(11) NOT NULL DEFAULT 0,
  `member_level_id` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '可购买的会员等级',
  `user_limit_type` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '限制用户类型(10全部 20新用户 30老用户)',
  `user_level_limit` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '限制用户等级',
  `goods_limit_type` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '限制商品类型(10全部 20指定分类 30指定品牌)',
  `goods_ids` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_app_status_time`(`app_id`, `start_at`, `end_at`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '优惠券记录表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_currencies
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_currencies`;
CREATE TABLE `yxshop_currencies`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '货币名称',
  `code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '货币代码（如：CNY, USD）',
  `symbol` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '货币符号（如：¥, $）',
  `exchange_rate` decimal(10, 4) NOT NULL DEFAULT 1.0000 COMMENT '兑换率（相对于基础货币）',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：1-启用，0-禁用',
  `is_default` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否默认货币：1-是，0-否',
  `sort` int(11) NOT NULL DEFAULT 0 COMMENT '排序',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `code`(`code`) USING BTREE,
  INDEX `status`(`status`) USING BTREE,
  INDEX `is_default`(`is_default`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 6 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '货币表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for yxshop_data_change_logs
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_data_change_logs`;
CREATE TABLE `yxshop_data_change_logs`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `table_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '表名',
  `record_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `change_type` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '变更类型(10新增 20修改 30删除)',
  `change_field` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '变更字段',
  `old_value` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '原值',
  `new_value` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '新值',
  `change_reason` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '变更原因',
  `operator_type` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '操作者类型(10用户 20管理员 30系统)',
  `operator_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `operator_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '操作者名称',
  `ip` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'IP地址',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_table_record`(`table_name`, `record_id`) USING BTREE,
  INDEX `idx_change_type`(`change_type`) USING BTREE,
  INDEX `idx_operator`(`operator_type`, `operator_id`) USING BTREE,
  INDEX `idx_created_at`(`created_at`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '数据变更日志表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_dealer_referees
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_dealer_referees`;
CREATE TABLE `yxshop_dealer_referees`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键id',
  `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `level` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '资金流动类型 (10佣金收入 20提现支出)',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `dealer_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '分销商资金明细表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_deliveries
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_deliveries`;
CREATE TABLE `yxshop_deliveries`  (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '模板名称',
  `method` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '计费方式(10按件数 20按重量)',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '1启用 0禁用',
  `sort` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序方式(数字越小越靠前)',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '配送模板主表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_delivery_rules
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_delivery_rules`;
CREATE TABLE `yxshop_delivery_rules`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '规则id',
  `delivery_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `region` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '可配送区域(城市id集)',
  `first` double UNSIGNED NOT NULL DEFAULT 0 COMMENT '首件(个)/首重(Kg)',
  `first_fee` decimal(10, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '运费(元)',
  `additional` double UNSIGNED NOT NULL DEFAULT 0 COMMENT '续件/续重',
  `additional_fee` decimal(10, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '续费(元)',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) UNSIGNED NOT NULL COMMENT '创建时间',
  `min_price` decimal(10, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '最低消费金额',
  `min_num` int(11) NOT NULL DEFAULT 0,
  `delivery_type` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '包邮条件类型(10件数 20金额 30件数+金额)',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `delivery_id`(`delivery_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '配送模板区域及运费表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_delivery_tracks
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_delivery_tracks`;
CREATE TABLE `yxshop_delivery_tracks`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `order_delivery_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `express_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '快递公司编码',
  `express_no` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '快递单号',
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '轨迹内容JSON',
  `location` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '所在位置',
  `event_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '轨迹事件时间',
  `is_last` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否最新轨迹',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_order_id`(`order_id`) USING BTREE,
  INDEX `idx_express_no`(`express_no`) USING BTREE,
  INDEX `idx_event_time`(`event_time`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '物流轨迹表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for yxshop_discounts
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_discounts`;
CREATE TABLE `yxshop_discounts`  (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '优惠名称',
  `type` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '类型 (1百分比 2固定金额)',
  `value` decimal(10, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '优惠值(百分比或固定金额)',
  `min_amount` decimal(10, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '最低消费金额',
  `max_discount` decimal(10, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '最大优惠金额(0无限制)',
  `start_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '开始时间',
  `end_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '结束时间',
  `is_active` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否启用 (0否 1是)',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `sort` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '??????',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `type`(`type`) USING BTREE,
  INDEX `is_active`(`is_active`) USING BTREE,
  INDEX `start_time`(`start_time`) USING BTREE,
  INDEX `end_time`(`end_time`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '优惠活动表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_email_logs
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_email_logs`;
CREATE TABLE `yxshop_email_logs`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '邮箱地址',
  `email_type` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '邮件类型(10注册 20登录 30找回密码 40通知 50营销)',
  `email_subject` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '邮件主题',
  `email_content` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '邮件内容',
  `email_status` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '发送状态(10待发送 20发送中 30发送成功 40发送失败)',
  `email_result` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '发送结果',
  `error_message` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '错误信息',
  `ip` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'IP地址',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_user_id`(`user_id`) USING BTREE,
  INDEX `idx_email`(`email`) USING BTREE,
  INDEX `idx_email_type`(`email_type`) USING BTREE,
  INDEX `idx_email_status`(`email_status`) USING BTREE,
  INDEX `idx_created_at`(`created_at`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '邮件日志表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_expresses
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_expresses`;
CREATE TABLE `yxshop_expresses`  (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '快递公司名称',
  `code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '快递公司编码',
  `sort` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序 (数字越小越靠前)',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '1启用 0禁用',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `express_code`(`code`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '物流公司记录表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for yxshop_file_logs
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_file_logs`;
CREATE TABLE `yxshop_file_logs`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `file_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `file_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '文件名',
  `file_path` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '文件路径',
  `file_size` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '文件大小',
  `file_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '文件类型',
  `operation_type` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '操作类型(10上传 20下载 30删除 40修改)',
  `operation_result` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '操作结果(10成功 20失败)',
  `error_message` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '错误信息',
  `ip` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'IP地址',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_user_id`(`user_id`) USING BTREE,
  INDEX `idx_file_id`(`file_id`) USING BTREE,
  INDEX `idx_operation_type`(`operation_type`) USING BTREE,
  INDEX `idx_operation_result`(`operation_result`) USING BTREE,
  INDEX `idx_created_at`(`created_at`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '文件操作日志表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_games
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_games`;
CREATE TABLE `yxshop_games`  (
  `id` bigint(20) UNSIGNED NOT NULL,
  `app_id` bigint(20) NOT NULL,
  `title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '活动标题',
  `created_at` int(11) NOT NULL COMMENT '创建时间',
  `updated_at` int(11) NOT NULL COMMENT '修改时间',
  `type` int(7) NOT NULL COMMENT '1为大转盘,',
  `start_time` int(11) NOT NULL COMMENT '开始时间',
  `end_time` int(11) NOT NULL,
  `details` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '活动详情',
  `setnum` int(5) NOT NULL COMMENT '中奖次数',
  `setup` int(1) NOT NULL COMMENT '1为每日中奖次数,0为本轮活动中奖次数',
  `status` int(1) NOT NULL DEFAULT 0 COMMENT '默认活动',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_idempotency_records
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_idempotency_records`;
CREATE TABLE `yxshop_idempotency_records`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `key` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '幂等key(如支付回调out_trade_no)',
  `scope` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '作用域(payment/order/...)',
  `response` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '首次响应缓存',
  `expires_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '过期时间',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_key`(`key`) USING BTREE,
  INDEX `idx_expires_at`(`expires_at`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '幂等记录表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for yxshop_integral_log
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_integral_log`;
CREATE TABLE `yxshop_integral_log`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '日志ID',
  `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `type` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '类型 (1获得 2消费)',
  `amount` int(11) NOT NULL DEFAULT 0 COMMENT '积分变动数量(正获得负消费)',
  `before_balance` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '变动前积分',
  `after_balance` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '变动后积分',
  `note` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '备注',
  `order_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE,
  INDEX `type`(`type`) USING BTREE,
  INDEX `order_id`(`order_id`) USING BTREE,
  INDEX `created_at`(`created_at`) USING BTREE,
  INDEX `idx_user_type`(`user_id`, `type`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '积分日志表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_item_attributes
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_item_attributes`;
CREATE TABLE `yxshop_item_attributes`  (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '属性名称',
  `type_id` smallint(5) UNSIGNED NOT NULL DEFAULT 0 COMMENT '属性分类id',
  `values` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '可选值列表',
  `sort` tinyint(3) UNSIGNED NOT NULL DEFAULT 50 COMMENT '属性排序',
  `app_id` bigint(20) NOT NULL,
  `created_at` int(11) NOT NULL DEFAULT 0,
  `updated_at` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `cat_id`(`type_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '属性定义表（定义属性名称和类型）' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_item_attrs
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_item_attrs`;
CREATE TABLE `yxshop_item_attrs`  (
  `id` bigint(20) UNSIGNED NOT NULL,
  `goods_id` mediumint(8) UNSIGNED NOT NULL DEFAULT 0 COMMENT '商品id',
  `attr_id` smallint(5) UNSIGNED NOT NULL DEFAULT 0 COMMENT '属性id',
  `attr_value` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '属性值',
  `attr_price` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '属性价格',
  `app_id` bigint(20) NOT NULL,
  `created_at` int(11) NOT NULL DEFAULT 0,
  `updated_at` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `goods_id`(`goods_id`) USING BTREE,
  INDEX `attr_id`(`attr_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '商品-属性值关联表（记录商品的属性值）' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_item_favorites
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_item_favorites`;
CREATE TABLE `yxshop_item_favorites`  (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `item_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除时间(0未删)',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `idx_user_item`(`user_id`, `item_id`) USING BTREE,
  INDEX `idx_item_user`(`item_id`, `user_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '商品收藏表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_item_images
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_item_images`;
CREATE TABLE `yxshop_item_images`  (
  `id` bigint(20) UNSIGNED NOT NULL,
  `image_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '图片地址',
  `sort` int(11) NOT NULL DEFAULT 0 COMMENT '排序',
  `is_main` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否主图',
  `url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '图片URL',
  `app_id` bigint(20) NOT NULL,
  `created_at` int(11) NOT NULL DEFAULT 0,
  `updated_at` int(11) NOT NULL DEFAULT 0,
  `item_id` bigint(20) NULL DEFAULT NULL,
  `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除时间(0未删)',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '商品图片表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_item_searches
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_item_searches`;
CREATE TABLE `yxshop_item_searches`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '搜索记录ID',
  `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `keyword` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '搜索关键词',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `ip` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'IP地址',
  `result_count` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '搜索结果数量',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_user_time`(`user_id`, `created_at`) USING BTREE,
  INDEX `idx_keyword_time`(`keyword`, `created_at`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '商品搜索记录表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_item_spec_price_histories
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_item_spec_price_histories`;
CREATE TABLE `yxshop_item_spec_price_histories`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `item_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `spec_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `old_price` decimal(10, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '原价格',
  `new_price` decimal(10, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '新价格',
  `change_reason` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '变更原因',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_item_spec`(`item_id`, `spec_id`) USING BTREE,
  INDEX `idx_time`(`created_at`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '商品规格价格历史表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_item_spec_prices
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_item_spec_prices`;
CREATE TABLE `yxshop_item_spec_prices`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `item_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `spec_key` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '规格组合MD5',
  `spec_key_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '规格组合文本',
  `spec_values` json NULL COMMENT '规格值JSON',
  `price` decimal(10, 2) NOT NULL DEFAULT 0.00,
  `cost_price` decimal(10, 2) NULL DEFAULT NULL,
  `market_price` decimal(10, 2) NULL DEFAULT NULL,
  `store_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `weight` decimal(10, 2) NULL DEFAULT NULL,
  `sku` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT 'SKU编码',
  `image` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT 'SKU图片',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `updated_at` int(10) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_item_id`(`item_id`) USING BTREE,
  INDEX `idx_spec_key`(`spec_key`) USING BTREE,
  INDEX `idx_app_id`(`app_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 327098037123940353 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '商品规格价格表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for yxshop_item_spec_sales
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_item_spec_sales`;
CREATE TABLE `yxshop_item_spec_sales`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `item_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `spec_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `sales_count` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '销量',
  `sales_amount` decimal(10, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '销售金额',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `stat_date` date NOT NULL COMMENT '统计日期',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `idx_item_spec_date`(`item_id`, `spec_id`, `stat_date`) USING BTREE,
  INDEX `idx_date`(`stat_date`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '商品规格销量统计表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_item_spec_stock_logs
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_item_spec_stock_logs`;
CREATE TABLE `yxshop_item_spec_stock_logs`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `item_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `spec_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `change_type` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '变动类型(10入库 20出库 30调整 40退货)',
  `change_quantity` int(11) NOT NULL DEFAULT 0 COMMENT '变动数量(正数为增加，负数为减少)',
  `before_stock` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '变动前库存',
  `after_stock` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '变动后库存',
  `change_reason` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '变动原因',
  `operator_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_item_spec`(`item_id`, `spec_id`) USING BTREE,
  INDEX `idx_type_time`(`change_type`, `created_at`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 6 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '商品规格库存变动记录表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_item_stock_warnings
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_item_stock_warnings`;
CREATE TABLE `yxshop_item_stock_warnings`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '预警ID',
  `item_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `spec_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `current_stock` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '当前库存',
  `warning_stock` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '预警库存',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `is_notified` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否已通知(0否 1是)',
  `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_item_spec`(`item_id`, `spec_id`) USING BTREE,
  INDEX `idx_notified`(`is_notified`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '商品规格库存预警表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_item_tag_relations
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_item_tag_relations`;
CREATE TABLE `yxshop_item_tag_relations`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `item_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `tag_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_item_tag`(`item_id`, `tag_id`) USING BTREE,
  INDEX `idx_tag_item`(`tag_id`, `item_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '商品标签关联表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_item_tags
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_item_tags`;
CREATE TABLE `yxshop_item_tags`  (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tag_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '标签名称',
  `tag_color` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '#ff6b6b' COMMENT '标签颜色',
  `sort` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序(数字越小越靠前)',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '1启用 0禁用',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_app_sort`(`app_id`, `sort`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '商品标签表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_item_types
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_item_types`;
CREATE TABLE `yxshop_item_types`  (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '类型名称',
  `app_id` bigint(20) NOT NULL,
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '1启用 0禁用',
  `created_at` int(11) NOT NULL,
  `updated_at` int(11) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '商品类型表（实物/虚拟/服务）' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_item_views
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_item_views`;
CREATE TABLE `yxshop_item_views`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '浏览记录ID',
  `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `item_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `ip` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'IP地址',
  `user_agent` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '用户代理',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_user_time`(`user_id`, `created_at`) USING BTREE,
  INDEX `idx_item_time`(`item_id`, `created_at`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '商品浏览记录表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_items
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_items`;
CREATE TABLE `yxshop_items`  (
  `id` bigint(20) UNSIGNED NOT NULL,
  `category_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `initial_sales` int(6) NULL DEFAULT 0 COMMENT '初始销量',
  `name` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '商品名称',
  `click` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '点击数',
  `brand_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `stock` smallint(5) UNSIGNED NOT NULL DEFAULT 10 COMMENT '库存数量',
  `stock_warning` int(11) NULL DEFAULT NULL COMMENT '库存预警值',
  `comment_count` smallint(5) NULL DEFAULT 0 COMMENT '商品评论数',
  `weight` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '商品重量克为单位',
  `length` decimal(10, 2) NULL DEFAULT NULL COMMENT '长度(cm)',
  `width` decimal(10, 2) NULL DEFAULT NULL COMMENT '宽度(cm)',
  `height` decimal(10, 2) NULL DEFAULT NULL COMMENT '高度(cm)',
  `summary` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '商品简单描述',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '商品详细描述',
  `is_physical` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否为实物',
  `is_virtual` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否虚拟商品(0否 1是)',
  `virtual_type` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '虚拟商品类型(0无 1卡密 2链接)',
  `valid_days` int(11) NULL DEFAULT NULL COMMENT '虚拟商品有效天数',
  `usage_note` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '使用说明',
  `is_on_sale` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否上架',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态(0下架 1上架)',
  `is_free_shipping` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否包邮0否1是',
  `sort` smallint(4) UNSIGNED NOT NULL DEFAULT 50 COMMENT '商品排序',
  `is_recommended` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否推荐',
  `is_new` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否新品',
  `is_hot` tinyint(1) NULL DEFAULT 0 COMMENT '是否热卖',
  `supports_coupon` int(1) NOT NULL DEFAULT 1 COMMENT '0为不支持1为支持',
  `supports_vip` int(1) NOT NULL DEFAULT 1 COMMENT '0为不支持1为支持',
  `type_id` smallint(5) UNSIGNED NOT NULL DEFAULT 0 COMMENT '商品所属类型id，取值表goods_type的cat_id',
  `spec_type` smallint(5) NULL DEFAULT 0 COMMENT '商品规格类型，取值表goods_type的cat_id',
  `points_reward` decimal(10, 2) NULL DEFAULT 0.00 COMMENT '购买商品赠送积分',
  `points_exchange` int(10) NOT NULL DEFAULT 0 COMMENT '积分兑换：0不参与积分兑换，积分和现金的兑换比例100：1',
  `total_sales` int(11) NULL DEFAULT 0 COMMENT '商品销量',
  `promotion_type` tinyint(1) NULL DEFAULT 0 COMMENT '0 普通订单,1 限时抢购, 2 团购 , 3 促销优惠',
  `promotion_id` bigint(20) NULL DEFAULT 0,
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) NOT NULL DEFAULT 0,
  `updated_at` int(11) NOT NULL DEFAULT 0,
  `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除时间(0未删)',
  `shipping_template_id` bigint(20) NULL DEFAULT 0,
  `discount` decimal(5, 2) NOT NULL DEFAULT 0.00 COMMENT '折扣',
  `sale_price` decimal(10, 2) NULL DEFAULT NULL COMMENT '促销价',
  `commission_type` int(2) NOT NULL DEFAULT 1 COMMENT '(1百分比2固定)',
  `commission_amount` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0' COMMENT '分销金额',
  `supports_discount` tinyint(1) NULL DEFAULT 1 COMMENT '是否参加活动',
  `member_level_id` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '可购买的会员等级',
  `services` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '商品服务',
  `seo_title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'SEO标题',
  `seo_keywords` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'SEO关键词',
  `seo_description` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'SEO描述',
  `video_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '商品视频',
  `unit` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '件' COMMENT '商品单位',
  `show_in_category` smallint(3) NOT NULL DEFAULT 1 COMMENT '显示在分类',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_app_cat_status`(`app_id`, `category_id`, `is_on_sale`) USING BTREE,
  INDEX `idx_sales_sort`(`total_sales`, `sort`) USING BTREE,
  INDEX `idx_prom_type`(`promotion_type`, `promotion_id`) USING BTREE,
  INDEX `idx_status_delete`(`status`) USING BTREE,
  INDEX `idx_is_on_sale`(`is_on_sale`) USING BTREE,
  INDEX `idx_created_at`(`created_at`) USING BTREE,
  INDEX `idx_category_id`(`category_id`) USING BTREE,
  INDEX `idx_brand_id`(`brand_id`) USING BTREE,
  INDEX `idx_stock`(`stock`) USING BTREE,
  INDEX `idx_weight`(`weight`) USING BTREE,
  INDEX `idx_sort`(`sort`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '商品表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_languages
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_languages`;
CREATE TABLE `yxshop_languages`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '语言名称',
  `code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '语言代码（如：zh-CN, en-US）',
  `flag` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '国旗图标',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：1-启用，0-禁用',
  `is_default` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否默认语言：1-是，0-否',
  `sort` int(11) NOT NULL DEFAULT 0 COMMENT '排序',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `code`(`code`) USING BTREE,
  INDEX `status`(`status`) USING BTREE,
  INDEX `is_default`(`is_default`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 6 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '语言表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for yxshop_login_logs
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_login_logs`;
CREATE TABLE `yxshop_login_logs`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_type` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '10' COMMENT '用户类型',
  `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `user_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '用户名',
  `login_type` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '登录类型(10密码 20微信 30手机验证码)',
  `login_result` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '登录结果(10成功 20失败)',
  `fail_reason` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '失败原因',
  `login_ip` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'IP地址',
  `user_agent` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '用户代理',
  `location` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '登录地点',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `login_message` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '登陆信息',
  `login_status` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '登陆状态',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_user_type_id`(`user_type`, `user_id`) USING BTREE,
  INDEX `idx_login_result`(`login_result`) USING BTREE,
  INDEX `idx_created_at`(`created_at`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 328112200860303361 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '登录日志表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_notification_blacklists
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_notification_blacklists`;
CREATE TABLE `yxshop_notification_blacklists`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '黑名单ID',
  `receiver` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '接收者(手机号/邮箱/用户ID)',
  `receiver_type` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '接收者类型(10手机号 20邮箱 30用户ID)',
  `template_type` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '模板类型(0全部 10邮件 20短信 30小程序通知 40站内信)',
  `blacklist_reason` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '拉黑原因',
  `is_active` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否启用(0否 1是)',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `idx_receiver_type`(`receiver`, `receiver_type`, `template_type`) USING BTREE,
  INDEX `idx_receiver`(`receiver`) USING BTREE,
  INDEX `idx_template_type`(`template_type`) USING BTREE,
  INDEX `idx_is_active`(`is_active`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '通知黑名单表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_notification_configs
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_notification_configs`;
CREATE TABLE `yxshop_notification_configs`  (
  `id` bigint(20) UNSIGNED NOT NULL,
  `config_key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '配置键',
  `config_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '配置名称',
  `config_type` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '配置类型(10邮件 20短信 30小程序通知 40站内信)',
  `config_value` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '配置值(JSON格式)',
  `config_desc` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '配置描述',
  `is_active` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否启用(0否 1是)',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `idx_config_key`(`config_key`) USING BTREE,
  INDEX `idx_config_type`(`config_type`) USING BTREE,
  INDEX `idx_is_active`(`is_active`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '通知配置表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_notification_queues
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_notification_queues`;
CREATE TABLE `yxshop_notification_queues`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '队列ID',
  `scene_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `template_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `template_type` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '模板类型(10邮件 20短信 30小程序通知 40站内信)',
  `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `receiver` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '接收者',
  `receiver_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '接收者姓名',
  `template_data` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '模板数据(JSON格式)',
  `send_priority` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '发送优先级(10低 20中 30高)',
  `send_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '计划发送时间',
  `retry_count` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '重试次数',
  `max_retry` tinyint(3) UNSIGNED NOT NULL DEFAULT 3 COMMENT '最大重试次数',
  `queue_status` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '队列状态(10待处理 20处理中 30处理成功 40处理失败)',
  `error_message` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '错误信息',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_scene_id`(`scene_id`) USING BTREE,
  INDEX `idx_template_id`(`template_id`) USING BTREE,
  INDEX `idx_user_id`(`user_id`) USING BTREE,
  INDEX `idx_send_priority`(`send_priority`) USING BTREE,
  INDEX `idx_send_time`(`send_time`) USING BTREE,
  INDEX `idx_queue_status`(`queue_status`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '通知队列表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_notification_scene_templates
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_notification_scene_templates`;
CREATE TABLE `yxshop_notification_scene_templates`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `scene_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `template_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `template_type` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '模板类型(10邮件 20短信 30小程序通知 40站内信)',
  `is_required` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否必选(0否 1是)',
  `is_active` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否启用(0否 1是)',
  `send_condition` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '发送条件(JSON格式)',
  `send_delay` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '发送延迟(秒)',
  `send_priority` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '发送优先级(10低 20中 30高)',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `idx_scene_template`(`scene_id`, `template_id`, `template_type`) USING BTREE,
  INDEX `idx_scene_id`(`scene_id`) USING BTREE,
  INDEX `idx_template_id`(`template_id`) USING BTREE,
  INDEX `idx_template_type`(`template_type`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 17 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '通知场景模板关联表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_notification_scenes
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_notification_scenes`;
CREATE TABLE `yxshop_notification_scenes`  (
  `id` bigint(20) UNSIGNED NOT NULL,
  `scene_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '场景名称',
  `scene_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '场景编码',
  `scene_type` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '场景类型(10用户 20管理员 30系统)',
  `scene_category` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '场景分类',
  `scene_desc` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '场景描述',
  `is_active` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否启用(0否 1是)',
  `sort` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序(数字越小越靠前)',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `idx_scene_code`(`scene_code`) USING BTREE,
  INDEX `idx_scene_type`(`scene_type`) USING BTREE,
  INDEX `idx_scene_category`(`scene_category`) USING BTREE,
  INDEX `idx_is_active`(`is_active`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '通知场景配置表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_notification_sends
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_notification_sends`;
CREATE TABLE `yxshop_notification_sends`  (
  `id` bigint(20) UNSIGNED NOT NULL,
  `scene_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `template_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `template_type` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '模板类型(10邮件 20短信 30小程序通知 40站内信)',
  `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `user_type` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '用户类型(10普通用户 20管理员)',
  `receiver` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '接收者(手机号/邮箱/用户ID)',
  `receiver_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '接收者姓名',
  `send_title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '发送标题',
  `send_content` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '发送内容',
  `send_status` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '发送状态(10待发送 20发送中 30发送成功 40发送失败)',
  `send_result` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '发送结果',
  `error_message` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '错误信息',
  `send_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '发送时间',
  `read_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '阅读时间',
  `ip` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'IP地址',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_scene_id`(`scene_id`) USING BTREE,
  INDEX `idx_template_id`(`template_id`) USING BTREE,
  INDEX `idx_user_id`(`user_id`) USING BTREE,
  INDEX `idx_receiver`(`receiver`) USING BTREE,
  INDEX `idx_send_status`(`send_status`) USING BTREE,
  INDEX `idx_send_time`(`send_time`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '通知发送记录表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_notification_statistics
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_notification_statistics`;
CREATE TABLE `yxshop_notification_statistics`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '统计ID',
  `template_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `template_type` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '模板类型(10邮件 20短信 30小程序通知 40站内信)',
  `stat_date` date NOT NULL COMMENT '统计日期',
  `send_count` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '发送数量',
  `success_count` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '成功数量',
  `fail_count` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '失败数量',
  `read_count` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '阅读数量',
  `read_rate` decimal(5, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '阅读率(%)',
  `success_rate` decimal(5, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '成功率(%)',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `idx_template_date`(`template_id`, `template_type`, `stat_date`) USING BTREE,
  INDEX `idx_template_id`(`template_id`) USING BTREE,
  INDEX `idx_template_type`(`template_type`) USING BTREE,
  INDEX `idx_stat_date`(`stat_date`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '通知统计表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_notification_template_variables
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_notification_template_variables`;
CREATE TABLE `yxshop_notification_template_variables`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `template_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `variable_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `is_required` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否必填(0否 1是)',
  `default_value` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '默认值',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `idx_template_variable`(`template_id`, `variable_id`) USING BTREE,
  INDEX `idx_template_id`(`template_id`) USING BTREE,
  INDEX `idx_variable_id`(`variable_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '模板-变量关联表（记录模板使用了哪些变量）' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_notification_templates
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_notification_templates`;
CREATE TABLE `yxshop_notification_templates`  (
  `id` bigint(20) UNSIGNED NOT NULL,
  `template_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '模板名称',
  `template_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '模板编码',
  `template_type` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '模板类型(10邮件 20短信 30小程序通知 40站内信)',
  `template_category` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '模板分类',
  `template_title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '模板标题',
  `template_content` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '模板内容',
  `template_variables` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '模板变量说明(JSON格式)',
  `template_example` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '模板示例',
  `is_active` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否启用(0否 1是)',
  `is_system` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否系统模板(0否 1是)',
  `sort` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序(数字越小越靠前)',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `idx_template_code`(`template_code`) USING BTREE,
  INDEX `idx_template_type`(`template_type`) USING BTREE,
  INDEX `idx_template_category`(`template_category`) USING BTREE,
  INDEX `idx_is_active`(`is_active`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '通知模板表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_notification_variables
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_notification_variables`;
CREATE TABLE `yxshop_notification_variables`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '变量ID',
  `variable_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '变量名称',
  `variable_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '变量编码',
  `variable_type` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '变量类型(10字符串 20数字 30日期 40布尔)',
  `variable_desc` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '变量描述',
  `variable_example` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '变量示例',
  `is_system` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否系统变量(0否 1是)',
  `is_active` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否启用(0否 1是)',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `idx_variable_code`(`variable_code`) USING BTREE,
  INDEX `idx_variable_type`(`variable_type`) USING BTREE,
  INDEX `idx_is_system`(`is_system`) USING BTREE,
  INDEX `idx_is_active`(`is_active`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 19 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '系统通知变量定义（如{{order_no}}、{{user_name}}）' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_notifications
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_notifications`;
CREATE TABLE `yxshop_notifications`  (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '标题',
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '内容',
  `channel` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'site' COMMENT '渠道：site/sms/email',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：0草稿 1已发送',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(10) UNSIGNED NULL DEFAULT NULL,
  `updated_at` int(10) UNSIGNED NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_app_id`(`app_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '消息推送' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for yxshop_order_addresses
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_order_addresses`;
CREATE TABLE `yxshop_order_addresses`  (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '收货人姓名',
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '联系电话',
  `province_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `city_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `region_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `detail` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '详细地址',
  `postal_code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '邮政编码',
  `is_default` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否默认地址(0否 1是)',
  `order_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `order_id`(`order_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '订单收货地址记录表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_order_agents
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_order_agents`;
CREATE TABLE `yxshop_order_agents`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键id',
  `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `order_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `order_price` decimal(10, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '订单总金额(不含运费)',
  `proportion` float(5, 2) NOT NULL COMMENT '分成比例',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `profit_money` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '分成收益',
  `graded` int(1) NOT NULL COMMENT '分成级别',
  `confirm` int(1) NULL DEFAULT 0,
  `order_user_id` bigint(20) NOT NULL DEFAULT 0,
  `command` tinyint(2) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `order_id`(`order_id`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '分销商订单记录表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_order_deliveries
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_order_deliveries`;
CREATE TABLE `yxshop_order_deliveries`  (
  `id` int(7) NOT NULL AUTO_INCREMENT,
  `order_no` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '订单号',
  `express_id` bigint(20) NOT NULL,
  `express_no` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '物流单号',
  `company` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '物流公司',
  `type` int(2) NOT NULL COMMENT '1卖家发货2买家退货',
  `app_id` bigint(20) NOT NULL,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `receipt_time` int(11) NULL DEFAULT 0 COMMENT '收货时间',
  `order_id` bigint(20) NULL DEFAULT NULL,
  `express_code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_order_id`(`order_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_order_goods
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_order_goods`;
CREATE TABLE `yxshop_order_goods`  (
  `id` bigint(20) UNSIGNED NOT NULL,
  `item_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '商品名称',
  `image` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0' COMMENT '商品封面图id',
  `type` tinyint(3) UNSIGNED NOT NULL DEFAULT 20 COMMENT '库存计算方式(10下单减库存 20付款减库存)',
  `spec_type` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '规格类型(10单规格 20多规格)',
  `spec_sku_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '商品sku标识',
  `item_sku_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `goods_attr` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '商品规格信息',
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '商品详情',
  `goods_no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '商品编码',
  `goods_price` decimal(10, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '商品价格',
  `line_price` decimal(10, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '商品划线价',
  `goods_weight` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '商品重量(g)',
  `total_num` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '购买数量',
  `total_price` decimal(10, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '商品总价(数量×单价)',
  `total_pay_price` decimal(10, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '实际付款价(折扣和优惠后)',
  `is_comment` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否已评价(0否 1是)',
  `order_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `is_service` tinyint(3) NULL DEFAULT 0,
  `give_integral` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '积分',
  `refund_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0' COMMENT '退款ID',
  `sv_num` int(11) NOT NULL DEFAULT 0,
  `service_status` int(7) NOT NULL DEFAULT 0,
  `service_type` int(7) NOT NULL DEFAULT 0,
  `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除时间(0未删)',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `item_id`(`item_id`) USING BTREE,
  INDEX `idx_app_user`(`app_id`, `user_id`) USING BTREE,
  INDEX `idx_item_id`(`item_id`) USING BTREE,
  INDEX `idx_deleted_at`(`deleted_at`) USING BTREE,
  INDEX `idx_order_id`(`order_id`) USING BTREE,
  INDEX `idx_user_id`(`user_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '订单商品记录表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_order_logs
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_order_logs`;
CREATE TABLE `yxshop_order_logs`  (
  `log_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `order_no` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '订单号',
  `change_type` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '变更类型(10创建 20支付 30发货 40收货 50取消 60退款 70修改 80状态变更)',
  `change_field` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '变更字段',
  `old_value` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '原值',
  `new_value` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '新值',
  `change_reason` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '变更原因',
  `operator_type` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '操作者类型(10用户 20管理员 30系统)',
  `operator_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `operator_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '操作者名称',
  `ip` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'IP地址',
  `user_agent` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '用户代理',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  PRIMARY KEY (`log_id`) USING BTREE,
  INDEX `idx_order_id`(`order_id`) USING BTREE,
  INDEX `idx_order_no`(`order_no`) USING BTREE,
  INDEX `idx_change_type`(`change_type`) USING BTREE,
  INDEX `idx_operator`(`operator_type`, `operator_id`) USING BTREE,
  INDEX `idx_created_at`(`created_at`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 4 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '订单变化日志表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_orders
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_orders`;
CREATE TABLE `yxshop_orders`  (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_no` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '订单号',
  `total_price` decimal(10, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '订单金额(不含运费)',
  `coupon_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `coupon_price` decimal(10, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '优惠券抵扣金额',
  `pay_price` decimal(10, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '实际付款金额(包含运费)',
  `update_price` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '后台修改的订单金额（差价）',
  `buyer_note` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '买家留言',
  `pay_status` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '付款状态(10未付款 20已付款)',
  `pay_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '付款时间',
  `express_price` decimal(10, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '运费金额',
  `delivery_status` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '发货状态(10未发货 20已发货)',
  `receipt_status` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '收货状态(10未收货 20已收货)',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '订单状态(10进行中 20取消 30已完成)',
  `source` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '订单来源(10小程序 20H5 30APP)',
  `transaction_id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '微信支付交易号',
  `is_comment` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否已评价(0否 1是)',
  `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除时间(0未删)',
  `prom_type` int(2) NOT NULL DEFAULT 0 COMMENT '订单分类：0普通 1拼团 2秒杀 3抢购',
  `prom_num` int(2) NOT NULL DEFAULT 0 COMMENT '参团人数',
  `prom_statis` int(2) NOT NULL DEFAULT 0 COMMENT '拼团状态',
  `prom_time` int(3) NOT NULL DEFAULT 0 COMMENT '拼团限时',
  `rebate` decimal(5, 2) NOT NULL DEFAULT 0.00 COMMENT '折扣',
  `item_id` bigint(20) NOT NULL DEFAULT 0 COMMENT '兼容字段(多商品订单不用)',
  `end_time` int(11) NOT NULL DEFAULT 0 COMMENT '结束时间',
  `give_integral` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '积分',
  `sub_status` int(7) NOT NULL DEFAULT 0 COMMENT '售后状态10为申请20为已处理',
  `sub_type` int(7) NOT NULL DEFAULT 0 COMMENT '售后方式1为退款2为换货',
  `express_no` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `discount_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `discount_price` decimal(10, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '优惠活动抵扣金额',
  `receiver_name` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '收货人姓名',
  `receiver_phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '联系电话',
  `seller_note` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '卖家留言',
  `delivery_time` int(11) NOT NULL DEFAULT 0 COMMENT '发货时间',
  `is_integral` tinyint(3) NOT NULL DEFAULT 0 COMMENT '积分购买',
  `refund_no` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `refund_desc` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `refund_status` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '退款状态(10无退款 20退款中 30已退款)',
  `refund_amount` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '已退款金额(累计)',
  `refund_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '退款时间',
  `receipt_time` int(11) NOT NULL DEFAULT 0 COMMENT '收货时间',
  `auto_confirm_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '自动确认收货时间',
  `car_no` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `selected_date` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `brand_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `order_no`(`order_no`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE,
  INDEX `idx_app_user_status`(`app_id`, `user_id`, `status`) USING BTREE,
  INDEX `idx_pay_status_time`(`pay_status`, `created_at`) USING BTREE,
  INDEX `idx_delivery_status`(`delivery_status`, `created_at`) USING BTREE,
  INDEX `idx_status`(`status`) USING BTREE,
  INDEX `idx_pay_status`(`pay_status`) USING BTREE,
  INDEX `idx_user_id`(`user_id`) USING BTREE,
  INDEX `idx_created_at`(`created_at`) USING BTREE,
  INDEX `idx_refund_status`(`refund_status`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '订单记录表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_packages
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_packages`;
CREATE TABLE `yxshop_packages`  (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '套餐名称',
  `type` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '类型(1固定套餐 2自定义)',
  `items` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '包含商品JSON [{item_id, quantity}]',
  `price` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '套餐价',
  `original_price` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '原价',
  `start_time` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `end_time` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态(0下架 1上架)',
  `sort` int(11) NOT NULL DEFAULT 50,
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_app_status`(`app_id`, `status`) USING BTREE,
  INDEX `idx_sort`(`sort`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '商品套餐表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for yxshop_payment_logs
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_payment_logs`;
CREATE TABLE `yxshop_payment_logs`  (
  `log_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `order_no` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '订单号',
  `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `payment_type` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '支付类型(10微信支付 20支付宝 30余额支付)',
  `payment_method` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '支付方式',
  `payment_amount` decimal(10, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '支付金额',
  `payment_status` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '支付状态(10待支付 20支付中 30支付成功 40支付失败 50已退款)',
  `transaction_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '第三方交易号',
  `prepay_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '预支付ID',
  `payment_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '支付时间',
  `callback_data` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '回调数据',
  `error_message` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '错误信息',
  `ip` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'IP地址',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  PRIMARY KEY (`log_id`) USING BTREE,
  INDEX `idx_order_id`(`order_id`) USING BTREE,
  INDEX `idx_order_no`(`order_no`) USING BTREE,
  INDEX `idx_user_id`(`user_id`) USING BTREE,
  INDEX `idx_payment_status`(`payment_status`) USING BTREE,
  INDEX `idx_transaction_id`(`transaction_id`) USING BTREE,
  INDEX `idx_created_at`(`created_at`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '支付日志表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_payments
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_payments`;
CREATE TABLE `yxshop_payments`  (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `order_no` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '订单编号',
  `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `payment_method` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '支付方式 wechat/alipay/balance',
  `amount` decimal(12, 2) NOT NULL DEFAULT 0.00 COMMENT '支付金额（现用字段）',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '支付状态 10待支付 20成功 30失败 40取消 50已退款（现用字段）',
  `payment_amount` decimal(12, 2) NOT NULL DEFAULT 0.00 COMMENT '支付金额（兼容历史查询）',
  `payment_status` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '支付状态（兼容历史查询）',
  `transaction_id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '第三方交易号',
  `refund_reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '退款原因',
  `paid_at` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '支付时间(时间戳)',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建时间(时间戳)',
  `updated_at` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '更新时间(时间戳)',
  `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除时间(0未删)',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_transaction_id`(`transaction_id`) USING BTREE,
  INDEX `idx_app_id`(`app_id`) USING BTREE,
  INDEX `idx_order_id`(`order_id`) USING BTREE,
  INDEX `idx_user_id`(`user_id`) USING BTREE,
  INDEX `idx_status`(`status`) USING BTREE,
  INDEX `idx_payment_status`(`payment_status`) USING BTREE,
  INDEX `idx_method`(`payment_method`) USING BTREE,
  INDEX `idx_paid_at`(`paid_at`) USING BTREE,
  INDEX `idx_deleted_at`(`deleted_at`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '支付记录表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for yxshop_prize_lists
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_prize_lists`;
CREATE TABLE `yxshop_prize_lists`  (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `app_id` bigint(20) NOT NULL,
  `game_id` bigint(20) NOT NULL,
  `prize_id` bigint(20) NOT NULL,
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `state` int(1) NOT NULL COMMENT '0为参与',
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `prize_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_prizes
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_prizes`;
CREATE TABLE `yxshop_prizes`  (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '奖品名称',
  `app_id` bigint(20) NOT NULL,
  `game_id` bigint(20) NOT NULL,
  `number` int(7) NOT NULL COMMENT '奖品数量',
  `ratio` int(7) NOT NULL COMMENT '中奖率',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `original_img` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '图片路径',
  `is_state` int(1) NOT NULL COMMENT '是否中奖商品',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_profit_logs
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_profit_logs`;
CREATE TABLE `yxshop_profit_logs`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键id',
  `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `order_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `order_price` decimal(10, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '订单总金额(不含运费)',
  `proportion` float(5, 2) NOT NULL COMMENT '分成比例',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `profit_money` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '分成收益',
  `graded` int(1) NOT NULL COMMENT '分成级别',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `order_id`(`order_id`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '分销商订单记录表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_prom_goods
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_prom_goods`;
CREATE TABLE `yxshop_prom_goods`  (
  `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT '活动ID',
  `name` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '促销活动名称',
  `type` int(2) NOT NULL DEFAULT 0 COMMENT '促销类型',
  `expression` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '优惠体现',
  `description` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '活动描述',
  `start_time` int(11) NOT NULL COMMENT '活动开始时间',
  `end_time` int(11) NOT NULL COMMENT '活动结束时间',
  `is_close` tinyint(1) NULL DEFAULT 0,
  `group` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '适用范围',
  `prom_img` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '活动宣传图片',
  `app_id` bigint(20) NOT NULL,
  `item_id` bigint(20) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '商品级促销记录（特价/限时等）' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_prom_orders
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_prom_orders`;
CREATE TABLE `yxshop_prom_orders`  (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '活动名称',
  `type` int(2) NOT NULL DEFAULT 0 COMMENT '活动类型',
  `money` decimal(10, 2) NULL DEFAULT 0.00 COMMENT '最小金额',
  `expression` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '优惠体现',
  `description` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '活动描述',
  `start_time` int(11) NULL DEFAULT NULL COMMENT '活动开始时间',
  `end_time` int(11) NULL DEFAULT NULL COMMENT '活动结束时间',
  `is_close` tinyint(1) NULL DEFAULT 0,
  `group` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '适用范围',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '订单级促销记录（满减/折扣等）' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_promotions
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_promotions`;
CREATE TABLE `yxshop_promotions`  (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '活动标题',
  `type` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '类型：1满减 2折扣 3赠品',
  `rule` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '规则JSON',
  `start_time` int(10) UNSIGNED NULL DEFAULT NULL,
  `end_time` int(10) UNSIGNED NULL DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：0禁用 1启用',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(10) UNSIGNED NULL DEFAULT NULL,
  `updated_at` int(10) UNSIGNED NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_app_id`(`app_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '促销活动主表（定义活动规则）' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for yxshop_refund_records
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_refund_records`;
CREATE TABLE `yxshop_refund_records`  (
  `id` bigint(20) UNSIGNED NOT NULL,
  `refund_no` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '退款单号',
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `order_no` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `payment_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '原支付单ID',
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `amount` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '退款金额',
  `reason` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '退款原因',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '状态(10申请中 20已退款 30已拒绝 40已取消)',
  `refund_type` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '退款类型(10仅退款 20退货退款)',
  `transaction_id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '第三方退款单号',
  `operator_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `operator_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `handled_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '处理时间',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_refund_no`(`refund_no`) USING BTREE,
  INDEX `idx_order_id`(`order_id`) USING BTREE,
  INDEX `idx_payment_id`(`payment_id`) USING BTREE,
  INDEX `idx_user_id`(`user_id`) USING BTREE,
  INDEX `idx_status`(`status`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '退款记录表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for yxshop_regions
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_regions`;
CREATE TABLE `yxshop_regions`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `pid` int(11) NULL DEFAULT NULL COMMENT '父id',
  `shortname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '简称',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '名称',
  `merger_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '全称',
  `level` tinyint(4) UNSIGNED NULL DEFAULT 0 COMMENT '层级 1 2 3 省市区县',
  `pinyin` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '拼音',
  `code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '长途区号',
  `zip_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '邮编',
  `first` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '首字母',
  `lng` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '经度',
  `lat` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '纬度',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `name,level`(`name`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 3749 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '省市表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for yxshop_roles
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_roles`;
CREATE TABLE `yxshop_roles`  (
  `role_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '角色名称',
  `role_desc` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '角色描述',
  `permissions` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '权限列表 (JSON格式)',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态 (0禁用 1正常)',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`role_id`) USING BTREE,
  UNIQUE INDEX `role_name`(`role_name`) USING BTREE,
  INDEX `status`(`status`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '角色表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_schema_migrations
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_schema_migrations`;
CREATE TABLE `yxshop_schema_migrations`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `version` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `executed_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_version`(`version`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 6 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '迁移版本记录' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for yxshop_settings
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_settings`;
CREATE TABLE `yxshop_settings`  (
  `id` bigint(20) UNSIGNED NOT NULL,
  `key` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '设置项标示',
  `type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'string' COMMENT '值类型(string/json/int/float/bool)',
  `group` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'system' COMMENT '设置分组',
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '描述',
  `describe` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '设置项描述',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `value` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '设置值',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_app_key`(`app_id`, `key`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '商城设置记录表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_settlements
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_settlements`;
CREATE TABLE `yxshop_settlements`  (
  `id` bigint(20) UNSIGNED NOT NULL,
  `settlement_no` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '结算单号',
  `user_id` bigint(20) UNSIGNED NOT NULL COMMENT '被结算人(分销商/商户)',
  `order_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `order_agent_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联分销记录',
  `amount` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '结算金额',
  `type` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '结算类型(10分销佣金 20商户分账)',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '状态(10待结算 20已结算 30已撤销)',
  `settled_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_settlement_no`(`settlement_no`) USING BTREE,
  INDEX `idx_user_id`(`user_id`) USING BTREE,
  INDEX `idx_order_id`(`order_id`) USING BTREE,
  INDEX `idx_status`(`status`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '结算单表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for yxshop_signs
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_signs`;
CREATE TABLE `yxshop_signs`  (
  `id` int(7) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) NULL DEFAULT NULL,
  `day` int(10) NULL DEFAULT NULL,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `app_id` bigint(20) NULL DEFAULT NULL,
  `note` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_sms_logs
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_sms_logs`;
CREATE TABLE `yxshop_sms_logs`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '手机号',
  `sms_type` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '短信类型(10注册 20登录 30找回密码 40支付 50通知)',
  `sms_content` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '短信内容',
  `sms_status` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '发送状态(10待发送 20发送中 30发送成功 40发送失败)',
  `sms_result` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '发送结果',
  `error_message` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '错误信息',
  `ip` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'IP地址',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_user_id`(`user_id`) USING BTREE,
  INDEX `idx_phone`(`phone`) USING BTREE,
  INDEX `idx_sms_type`(`sms_type`) USING BTREE,
  INDEX `idx_sms_status`(`sms_status`) USING BTREE,
  INDEX `idx_created_at`(`created_at`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '短信日志表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_spec_item_prices
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_spec_item_prices`;
CREATE TABLE `yxshop_spec_item_prices`  (
  `id` bigint(20) UNSIGNED NOT NULL,
  `goods_id` bigint(20) NULL DEFAULT 0,
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '规格键名',
  `key_name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '规格键名中文',
  `shop_price` decimal(8, 2) NULL DEFAULT NULL COMMENT '规格键名中文',
  `price` decimal(10, 2) NULL DEFAULT NULL COMMENT '价格',
  `cost_price` decimal(10, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '成本价',
  `market_price` decimal(10, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '市场价',
  `is_default` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否默认规格(0否 1是)',
  `barcode` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '商品条码',
  `store_count` int(11) UNSIGNED NULL DEFAULT 10 COMMENT '库存数量',
  `sku` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT 'SKU',
  `app_id` bigint(20) NULL DEFAULT 0,
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `weight` decimal(8, 2) NULL DEFAULT NULL COMMENT '重量',
  `short_name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除时间(0未删)',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '规格价格表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_spec_items
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_spec_items`;
CREATE TABLE `yxshop_spec_items`  (
  `id` bigint(20) UNSIGNED NOT NULL,
  `spec_id` bigint(20) NULL DEFAULT NULL,
  `item` varchar(54) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '规格项',
  `app_id` bigint(20) NOT NULL,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除时间(0未删)',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '规格名称表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_specs
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_specs`;
CREATE TABLE `yxshop_specs`  (
  `id` bigint(20) UNSIGNED NOT NULL,
  `type_id` bigint(20) NULL DEFAULT 0,
  `name` varchar(55) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '规格名称',
  `order` int(11) NULL DEFAULT 50 COMMENT '排序',
  `search_index` tinyint(1) NULL DEFAULT 0 COMMENT '是否需要检索',
  `app_id` bigint(20) NOT NULL,
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '1启用 0禁用',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `sort_order` int(7) NULL DEFAULT NULL,
  `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除时间(0未删)',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '规格表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_system_logs
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_system_logs`;
CREATE TABLE `yxshop_system_logs`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `log_level` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '日志级别(10DEBUG 20INFO 30WARNING 40ERROR 50CRITICAL)',
  `log_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '日志类型',
  `module` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '模块名称',
  `action` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '操作动作',
  `message` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '日志消息',
  `context` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '上下文信息',
  `file` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '文件路径',
  `line` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '行号',
  `trace` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '堆栈跟踪',
  `ip` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'IP地址',
  `user_agent` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '用户代理',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `is_compressed` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否已压缩(0否 1是)',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_log_level`(`log_level`) USING BTREE,
  INDEX `idx_log_type`(`log_type`) USING BTREE,
  INDEX `idx_module_action`(`module`, `action`) USING BTREE,
  INDEX `idx_created_at`(`created_at`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '系统操作日志表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_system_logs_archive
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_system_logs_archive`;
CREATE TABLE `yxshop_system_logs_archive`  (
  `id` bigint(20) UNSIGNED NOT NULL,
  `log_level` tinyint(3) UNSIGNED NOT NULL DEFAULT 20 COMMENT '日志级别',
  `log_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '日志类型',
  `module` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '模块名称',
  `action` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '操作动作',
  `message` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '日志消息',
  `context` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '上下文信息',
  `file` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '文件路径',
  `line` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '行号',
  `trace` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '堆栈跟踪',
  `ip` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'IP地址',
  `user_agent` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '用户代理',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '原始创建时间',
  `archived_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '归档时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_created_at`(`created_at`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '系统操作日志归档表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_upload_files
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_upload_files`;
CREATE TABLE `yxshop_upload_files`  (
  `id` bigint(20) UNSIGNED NOT NULL,
  `storage` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '存储方式',
  `group_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `file_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '存储域名',
  `file_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '文件路径',
  `original_name` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '原始文件名',
  `file_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `file_size` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '文件大小(字节)',
  `file_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '文件类型',
  `file_ext` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `is_user` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否为c端用户上传',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `user_id` bigint(20) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `path_idx`(`file_name`) USING BTREE,
  INDEX `idx_app_user_type`(`app_id`, `user_id`, `file_type`) USING BTREE,
  INDEX `idx_group_type`(`group_id`, `file_type`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '文件库记录表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_upload_groups
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_upload_groups`;
CREATE TABLE `yxshop_upload_groups`  (
  `id` bigint(20) UNSIGNED NOT NULL,
  `group_type` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '文件类型',
  `group_name` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '分类名称',
  `sort` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '分类排序(数字越小越靠前)',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `type_index`(`group_type`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '文件库分组记录表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_upload_types
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_upload_types`;
CREATE TABLE `yxshop_upload_types`  (
  `id` bigint(20) UNSIGNED NOT NULL,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '类型如:商品item ',
  `app_id` bigint(20) NOT NULL,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `image_id` bigint(20) NULL DEFAULT NULL,
  `link_id` bigint(20) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_user_addresses
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_user_addresses`;
CREATE TABLE `yxshop_user_addresses`  (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '收货人姓名',
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '联系电话',
  `province_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `city_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `district_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `detail` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '详细地址',
  `zip_code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '邮政编码',
  `is_default` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否默认地址(0否 1是)',
  `label` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '地址标签',
  `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除时间(0未删)',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_deleted_at`(`deleted_at`) USING BTREE,
  INDEX `idx_user_id`(`user_id`) USING BTREE,
  INDEX `idx_app_user`(`app_id`, `user_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '用户收货地址表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_user_coupons
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_user_coupons`;
CREATE TABLE `yxshop_user_coupons`  (
  `id` bigint(20) UNSIGNED NOT NULL,
  `coupon_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '优惠券名称',
  `color` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '优惠券颜色(10蓝 20红 30紫 40黄)',
  `coupon_type` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '优惠券类型(10满减券 20折扣券)',
  `reduce_price` decimal(10, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '满减券-减免金额',
  `discount` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '折扣券-折扣率(0-100)',
  `min_price` decimal(10, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '最低消费金额',
  `expire_type` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '到期类型(10领取后生效 20固定时间)',
  `expire_day` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '领取后生效-有效天数',
  `start_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '有效期开始时间',
  `end_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '有效期结束时间',
  `apply_range` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '适用范围(10全部商品 20指定商品)',
  `is_expire` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否过期(0未过期 1已过期)',
  `is_use` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否已使用(0未使用 1已使用)',
  `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '1可用 0不可用',
  `order_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '使用的订单ID',
  `use_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '使用时间',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `min_num` int(11) NOT NULL DEFAULT 0,
  `level_id` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '可购买的会员等级',
  `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除时间(0未删)',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_user_status`(`user_id`, `is_use`, `is_expire`) USING BTREE,
  INDEX `idx_coupon_id`(`coupon_id`) USING BTREE,
  INDEX `idx_order_id`(`order_id`) USING BTREE,
  INDEX `idx_deleted_at`(`deleted_at`) USING BTREE,
  INDEX `idx_user_coupon`(`user_id`, `coupon_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '用户优惠券记录表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_user_integral_accounts
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_user_integral_accounts`;
CREATE TABLE `yxshop_user_integral_accounts`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `available` int(11) NOT NULL DEFAULT 0 COMMENT '可用积分',
  `frozen` int(11) NOT NULL DEFAULT 0 COMMENT '冻结积分',
  `total_earned` int(11) NOT NULL DEFAULT 0 COMMENT '累计获得',
  `total_consumed` int(11) NOT NULL DEFAULT 0 COMMENT '累计消耗',
  `version` int(11) NOT NULL DEFAULT 0 COMMENT '乐观锁版本号',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_user_app`(`user_id`, `app_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 5 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '用户积分账户表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for yxshop_user_levels
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_user_levels`;
CREATE TABLE `yxshop_user_levels`  (
  `id` bigint(20) UNSIGNED NOT NULL,
  `integral` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '订单id',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '等级 one two three',
  `values` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `agio` decimal(5, 2) NULL DEFAULT 10.00 COMMENT '扣折',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `sort` int(5) NULL DEFAULT NULL,
  `desc` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `shop_money` int(11) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `order_id`(`integral`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '等级表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_user_logs
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_user_logs`;
CREATE TABLE `yxshop_user_logs`  (
  `log_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `user_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '用户名称',
  `module` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '操作模块',
  `action` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '操作动作',
  `operation_type` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '操作类型(10查看 20新增 30修改 40删除 50支付 60登录 70注册)',
  `target_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '操作对象类型',
  `target_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `operation_desc` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '操作描述',
  `request_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '请求URL',
  `request_method` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '请求方法',
  `operation_result` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '操作结果(10成功 20失败)',
  `error_message` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '错误信息',
  `ip` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'IP地址',
  `user_agent` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '用户代理',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  PRIMARY KEY (`log_id`) USING BTREE,
  INDEX `idx_user_id`(`user_id`) USING BTREE,
  INDEX `idx_module_action`(`module`, `action`) USING BTREE,
  INDEX `idx_operation_type`(`operation_type`) USING BTREE,
  INDEX `idx_target`(`target_type`, `target_id`) USING BTREE,
  INDEX `idx_created_at`(`created_at`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '用户操作日志表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_user_money_log
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_user_money_log`;
CREATE TABLE `yxshop_user_money_log`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '日志ID',
  `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `money` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '变动金额(正数收入负数支出)',
  `before_money` decimal(10, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '变动前余额',
  `after_money` decimal(10, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '变动后余额',
  `note` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '备注',
  `type` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '类型 (1收入 2支出)',
  `order_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE,
  INDEX `type`(`type`) USING BTREE,
  INDEX `order_id`(`order_id`) USING BTREE,
  INDEX `created_at`(`created_at`) USING BTREE,
  INDEX `idx_app_user`(`app_id`, `user_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '用户余额变动日志表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for yxshop_users
-- ----------------------------
DROP TABLE IF EXISTS `yxshop_users`;
CREATE TABLE `yxshop_users`  (
  `id` bigint(20) UNSIGNED NOT NULL,
  `phone` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `email` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '邮箱',
  `openid` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '微信openid(账号密码注册用户为NULL)',
  `unionid` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '微信unionid(多应用唯一)',
  `nickname` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '微信昵称',
  `avatar_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '微信头像',
  `gender` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '性别',
  `country` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '国家',
  `province` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '省份',
  `city` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '城市',
  `default_address_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `money` decimal(10, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '用户余额',
  `total_spent` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '消费金额',
  `commission` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '推广收益',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：0=禁用，1=启用',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `last_login_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '最后登录时间',
  `last_login_ip` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '最后登录IP',
  `login_count` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '登录次数',
  `birthday` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '生日',
  `source` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '注册来源(10小程序 20H5 30APP)',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '密码哈希',
  `pid` int(10) NOT NULL DEFAULT 0 COMMENT '推荐',
  `level` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `sign` int(7) NULL DEFAULT 0 COMMENT '用户签到天数',
  `integral` int(11) NOT NULL DEFAULT 0 COMMENT '用户积分(余额见 user_integral_accounts)',
  `path` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '关系树',
  `agio` decimal(5, 2) NOT NULL DEFAULT 10.00 COMMENT '折扣',
  `freeze_money` decimal(10, 2) NULL DEFAULT 0.00,
  `level_id` tinyint(2) NULL DEFAULT 1,
  `is_dealer` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否经销商',
  `first_num` int(7) NULL DEFAULT 0,
  `second_num` int(7) NOT NULL DEFAULT 0,
  `third_num` int(7) NOT NULL DEFAULT 0,
  `referrer_id` bigint(20) NOT NULL DEFAULT 0,
  `referrer_spent` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '下线消费金额',
  `freeze_integral` int(11) NOT NULL DEFAULT 0 COMMENT '冻结积分',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_openid`(`openid`) USING BTREE,
  INDEX `idx_app_phone`(`app_id`, `phone`) USING BTREE,
  INDEX `idx_level_id`(`level_id`) USING BTREE,
  INDEX `idx_is_dealer`(`is_dealer`) USING BTREE,
  INDEX `idx_created_at`(`created_at`) USING BTREE,
  INDEX `idx_phone`(`phone`) USING BTREE,
  INDEX `idx_status`(`status`) USING BTREE,
  INDEX `idx_unionid`(`app_id`, `unionid`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '用户记录表' ROW_FORMAT = DYNAMIC;

-- ============================================================
-- 二、增量迁移（新增表和字段）
-- ============================================================

-- ---- 迁移: 2026_01_drop_non_prefixed_tables.sql ----
-- ============================================================
-- 废弃无前缀表（属于其他项目，不在yxshop系统中使用）
-- 执行顺序：Phase 1
-- 日期：2026-06-20
-- 说明：以下11张表无yxshop_前缀，属于其他项目(AI学习/成就/订阅等)，
--       与yxshop电商系统无关，直接废弃删除
-- ============================================================

SET NAMES utf8mb4;

-- 1. achievements - 成就系统表（AI学习项目）
DROP TABLE IF EXISTS `achievements`;

-- 2. admin_operation_logs - 管理员操作日志表（与yxshop_admin_logs功能重复，属于旧系统）
DROP TABLE IF EXISTS `admin_operation_logs`;

-- 3. ai_conversations - AI对话记录表（AI学习项目）
DROP TABLE IF EXISTS `ai_conversations`;

-- 4. ai_learning_profiles - AI学习配置表（AI学习项目）
DROP TABLE IF EXISTS `ai_learning_profiles`;

-- 5. ai_model_configs - AI模型配置表（AI学习项目）
DROP TABLE IF EXISTS `ai_model_configs`;

-- 6. point_activities - 积分活动类型表（AI学习项目）
DROP TABLE IF EXISTS `point_activities`;

-- 7. subscription_plans - 订阅套餐表（AI学习项目）
DROP TABLE IF EXISTS `subscription_plans`;

-- 8. user_achievements - 用户成就记录表（AI学习项目）
DROP TABLE IF EXISTS `user_achievements`;

-- 9. user_levels_config - 用户等级配置表（AI学习项目）
DROP TABLE IF EXISTS `user_levels_config`;

-- 10. user_points - 用户积分记录表（AI学习项目）
DROP TABLE IF EXISTS `user_points`;

-- 11. user_subscriptions - 用户订阅记录表（AI学习项目）
DROP TABLE IF EXISTS `user_subscriptions`;

-- ============================================================
-- 清理品牌表重复种子数据（不同app_id重复插入的同名品牌）
-- 保留最早一条（id最小的）
-- ============================================================
DELETE t1 FROM `yxshop_brands` t1
INNER JOIN `yxshop_brands` t2
ON t1.name = t2.name AND t1.id > t2.id;

-- ============================================================
-- 清理分类表重复种子数据（不同app_id重复插入的同名分类）
-- 保留最早一条（id最小的）
-- ============================================================
DELETE t1 FROM `yxshop_categories` t1
INNER JOIN `yxshop_categories` t2
ON t1.name = t2.name AND t1.parent_id = t2.parent_id AND t1.id > t2.id;

-- ---- 迁移: 2026_02_unify_time_fields.sql ----
-- ============================================================
-- 统一时间字段命名：create_time/update_time → created_at/updated_at
-- 执行顺序：Phase 2.1
-- 日期：2026-06-20
-- ============================================================

SET NAMES utf8mb4;

-- yxshop_expresses: create_time → created_at, update_time → updated_at
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_expresses` CHANGE `create_time` `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_expresses` CHANGE `update_time` `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间';

-- yxshop_item_favorites: create_time → created_at
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_item_favorites` CHANGE `create_time` `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间';

-- yxshop_item_views: create_time → created_at
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_item_views` CHANGE `create_time` `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间';

-- yxshop_item_searches: create_time → created_at
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_item_searches` CHANGE `create_time` `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间';

-- ---- 迁移: 2026_03_unify_primary_keys.sql ----
-- ============================================================
-- 统一主键命名：log_id → id
-- 执行顺序：Phase 2.2
-- 日期：2026-06-20
-- ============================================================

SET NAMES utf8mb4;

-- yxshop_admin_logs: log_id → id
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_admin_logs` CHANGE `log_id` `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键';

-- yxshop_data_change_logs: log_id → id
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_data_change_logs` CHANGE `log_id` `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键';

-- yxshop_email_logs: log_id → id
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_email_logs` CHANGE `log_id` `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键';

-- yxshop_file_logs: log_id → id
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_file_logs` CHANGE `log_id` `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键';

-- yxshop_item_spec_stock_logs: log_id → id
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_item_spec_stock_logs` CHANGE `log_id` `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键';

-- ---- 迁移: 2026_04_unify_foreign_keys.sql ----
-- ============================================================
-- 统一外键命名：goods_id → item_id
-- 执行顺序：Phase 2.3
-- 日期：2026-06-20
-- ============================================================

SET NAMES utf8mb4;

-- yxshop_item_attrs: goods_id → item_id
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_item_attrs` CHANGE `goods_id` `item_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '商品ID';

-- yxshop_spec_items: goods_id → item_id
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_spec_items` CHANGE `goods_id` `item_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '商品ID';

-- yxshop_spec_item_prices: goods_id → item_id
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_spec_item_prices` CHANGE `goods_id` `item_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '商品ID';

-- yxshop_coupon_goods: goods_id → item_id
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_coupon_goods` CHANGE `goods_id` `item_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '商品ID';

-- ---- 迁移: 2026_06_fix_brands_table.sql ----
-- ============================================================
-- 修复品牌表：删除重复排序字段 sort_order（保留sort）
-- 执行顺序：Phase 2.4
-- 日期：2026-06-20
-- ============================================================

SET NAMES utf8mb4;

-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_brands` DROP COLUMN `sort_order`;

-- ---- 迁移: 2026_07_03_000001_create_mini_page_tables.sql ----
-- 小程序首页动态装修 — 数据库迁移
-- 执行时间: 2026-07-03

CREATE TABLE `yxshop_mini_pages` (
  `id` varchar(64) NOT NULL COMMENT '雪花ID',
  `page_name` varchar(50) NOT NULL COMMENT '页面名称',
  `page_type` tinyint unsigned NOT NULL DEFAULT 10 COMMENT '页面类型: 10=首页 20=自定义页',
  `page_data` json DEFAULT NULL COMMENT '页面组件数据(JSON数组)',
  `status` tinyint unsigned NOT NULL DEFAULT 10 COMMENT '页面状态: 10=编辑中 20=已发布 30=已下线',
  `theme_id` varchar(64) DEFAULT NULL COMMENT '关联主题配色ID',
  `version` int unsigned NOT NULL DEFAULT 1 COMMENT '数据版本号(乐观锁)',
  `app_id` int unsigned NOT NULL DEFAULT 0 COMMENT '所属应用ID',
  `created_at` int unsigned NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int unsigned NOT NULL DEFAULT 0 COMMENT '更新时间',
  `deleted_at` int unsigned NOT NULL DEFAULT 0 COMMENT '软删除时间(0=未删除)',
  PRIMARY KEY (`id`),
  KEY `idx_app_id_status` (`app_id`, `status`),
  KEY `idx_app_id_type` (`app_id`, `page_type`),
  KEY `idx_theme_id` (`theme_id`),
  KEY `idx_updated_at` (`updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='小程序装修页面表';

CREATE TABLE `yxshop_mini_themes` (
  `id` varchar(64) NOT NULL COMMENT '雪花ID',
  `theme_name` varchar(30) NOT NULL COMMENT '配色方案名称',
  `primary_color` varchar(7) NOT NULL COMMENT '主色(HEX)',
  `secondary_color` varchar(7) NOT NULL COMMENT '辅色(HEX)',
  `nav_background_color` varchar(7) NOT NULL COMMENT '导航栏背景色(HEX)',
  `nav_text_color` varchar(7) NOT NULL DEFAULT '#333333' COMMENT '导航栏文字色(HEX)',
  `is_active` tinyint unsigned NOT NULL DEFAULT 0 COMMENT '是否当前激活: 0=否 1=是',
  `app_id` int unsigned NOT NULL DEFAULT 0 COMMENT '所属应用ID',
  `created_at` int unsigned NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int unsigned NOT NULL DEFAULT 0 COMMENT '更新时间',
  `deleted_at` int unsigned NOT NULL DEFAULT 0 COMMENT '软删除时间(0=未删除)',
  PRIMARY KEY (`id`),
  KEY `idx_app_id` (`app_id`),
  KEY `idx_app_id_active` (`app_id`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='小程序主题配色表';

CREATE TABLE `yxshop_mini_tab_bars` (
  `id` varchar(64) NOT NULL COMMENT '雪花ID',
  `color` varchar(7) NOT NULL DEFAULT '#999999' COMMENT '文字默认颜色(HEX)',
  `selected_color` varchar(7) NOT NULL DEFAULT '#2563eb' COMMENT '文字选中颜色(HEX)',
  `background_color` varchar(7) NOT NULL DEFAULT '#FFFFFF' COMMENT '背景颜色(HEX)',
  `border_style` varchar(5) NOT NULL DEFAULT 'black' COMMENT '边框样式: black/white',
  `items` json NOT NULL COMMENT '导航项列表(JSON数组)',
  `app_id` int unsigned NOT NULL DEFAULT 0 COMMENT '所属应用ID',
  `created_at` int unsigned NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int unsigned NOT NULL DEFAULT 0 COMMENT '更新时间',
  `deleted_at` int unsigned NOT NULL DEFAULT 0 COMMENT '软删除时间(0=未删除)',
  PRIMARY KEY (`id`),
  KEY `idx_app_id` (`app_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='小程序TabBar配置表';

CREATE TABLE `yxshop_mini_page_versions` (
  `id` varchar(64) NOT NULL COMMENT '雪花ID',
  `page_id` varchar(64) NOT NULL COMMENT '关联页面ID',
  `version_number` int unsigned NOT NULL COMMENT '版本号(页面内自增)',
  `page_data` json DEFAULT NULL COMMENT '版本快照数据(JSON)',
  `summary` varchar(200) DEFAULT NULL COMMENT '版本摘要',
  `publish_at` int unsigned NOT NULL DEFAULT 0 COMMENT '发布时间',
  `publisher_id` varchar(64) NOT NULL COMMENT '发布人ID',
  `app_id` int unsigned NOT NULL DEFAULT 0 COMMENT '所属应用ID',
  `created_at` int unsigned NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int unsigned NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_page_id_version` (`page_id`, `version_number`),
  KEY `idx_page_id_created` (`page_id`, `created_at`),
  KEY `idx_app_id` (`app_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='小程序页面版本表';

CREATE TABLE `yxshop_mini_page_templates` (
  `id` varchar(64) NOT NULL COMMENT '雪花ID',
  `template_name` varchar(50) NOT NULL COMMENT '模板名称',
  `template_data` json DEFAULT NULL COMMENT '模板页面数据(JSON)',
  `thumbnail` varchar(500) DEFAULT NULL COMMENT '模板缩略图URL',
  `app_id` int unsigned NOT NULL DEFAULT 0 COMMENT '所属应用ID',
  `created_at` int unsigned NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int unsigned NOT NULL DEFAULT 0 COMMENT '更新时间',
  `deleted_at` int unsigned NOT NULL DEFAULT 0 COMMENT '软删除时间(0=未删除)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_app_template_name` (`app_id`, `template_name`, `deleted_at`),
  KEY `idx_app_id` (`app_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='小程序页面模板表';

-- ---- 迁移: 2026_07_03_000002_mini_page_menus.sql ----
-- 小程序装修菜单补充
-- 将小程序装修功能菜单插入 yxshop_admin_menus（APP_ID=10001）
-- 执行前请确认 yxshop_admin_menus 表已存在且当前最大 ID < 93

SET NAMES utf8mb4;
SET @APP_ID = 10001;
SET @NOW = UNIX_TIMESTAMP();

-- -----------------------------------------------------------
-- 93: 小程序装修（顶级菜单，插入到 内容管理(25) 之后）
-- -----------------------------------------------------------
INSERT IGNORE INTO `yxshop_admin_menus` (`id`, `parent_id`, `name`, `model`, `url`, `icon`, `sort`, `is_show`, `permission`, `method`, `app_id`, `created_at`, `updated_at`) VALUES
(93, 0, '小程序装修', 'mini-page', NULL, 'icon-apps', 65, 1, NULL, 'GET', @APP_ID, @NOW, @NOW);

-- 小程序装修子菜单
INSERT IGNORE INTO `yxshop_admin_menus` (`id`, `parent_id`, `name`, `model`, `url`, `icon`, `sort`, `is_show`, `permission`, `method`, `app_id`, `created_at`, `updated_at`) VALUES
(94, 93, '页面管理', 'mini-page', '/mini-page', 'icon-list', 10, 1, 'mini-page/index', 'GET', @APP_ID, @NOW, @NOW),
(95, 93, '主题管理', 'mini-page', '/mini-page/theme', 'icon-skin', 20, 1, 'mini-page/themes', 'GET', @APP_ID, @NOW, @NOW),
(96, 93, 'TabBar配置', 'mini-page', '/mini-page/tabbar', 'icon-nav', 30, 1, 'mini-page/tabbar', 'GET', @APP_ID, @NOW, @NOW);

-- -----------------------------------------------------------
-- 自动授权：给超级管理员角色(role_id=1)授予所有新菜单权限
-- -----------------------------------------------------------
INSERT IGNORE INTO `yxshop_admin_role_auths` (`role_id`, `auth_id`, `created_at`, `updated_at`)
SELECT 1, `id`, @NOW, @NOW FROM `yxshop_admin_menus`
WHERE `id` >= 93 AND `is_show` = 1
ON DUPLICATE KEY UPDATE `updated_at` = @NOW;

-- ---- 迁移: 2026_07_03_000003_mini_theme_nav_text_color_hex.sql ----
-- 小程序主题 nav_text_color 改为存储 HEX 颜色
-- 原 varchar(5) 仅能存 light/dark，扩展为 varchar(7) 以支持任意 HEX 颜色(#RRGGBB)

-- 迁移旧数据: 将 light/dark 文本值转换为对应 HEX
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_mini_themes`
-- [DISABLED-REDUNDANT-MIGRATION]   MODIFY COLUMN `nav_text_color` varchar(7) NOT NULL DEFAULT '#333333' COMMENT '导航栏文字色(HEX)';
UPDATE `yxshop_mini_themes` SET `nav_text_color` = '#000000' WHERE `nav_text_color` = 'dark';

-- ---- 迁移: 2026_07_03_000004_user_list_menu.sql ----
-- 用户菜单调整
-- 1) 将现有"用户列表"(id=16, component=user/index) 更名为"用户分析"（该页面实为用户数据分析）
-- 2) 新增真正的"用户列表"菜单(id=97, component=user/list)，用于查看/管理注册用户
-- 执行前请确认 yxshop_admin_menus 表已存在且当前最大 ID < 97

SET NAMES utf8mb4;
SET @APP_ID = 10001;
SET @NOW = UNIX_TIMESTAMP();

-- -----------------------------------------------------------
-- 1) 现有菜单 16 更名为"用户分析"
--    保留其 url(/user/list) 与 permission(user/index)，仅改显示名称与图标、排序
-- -----------------------------------------------------------
UPDATE `yxshop_admin_menus`
SET `name` = '用户分析', `icon` = 'icon-bar-chart', `sort` = 5, `updated_at` = @NOW
WHERE `id` = 16 AND `app_id` = @APP_ID;

-- -----------------------------------------------------------
-- 2) 新增"用户列表"菜单（parent=用户管理 15，component=user/list）
-- -----------------------------------------------------------
INSERT IGNORE INTO `yxshop_admin_menus` (`id`, `parent_id`, `name`, `model`, `url`, `icon`, `sort`, `is_show`, `permission`, `method`, `app_id`, `created_at`, `updated_at`) VALUES
(97, 15, '用户列表', 'user', '/user/index', 'icon-user', 8, 1, 'user/list', 'GET', @APP_ID, @NOW, @NOW)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `url` = VALUES(`url`), `permission` = VALUES(`permission`), `updated_at` = @NOW;

-- -----------------------------------------------------------
-- 3) 自动授权：给超级管理员角色(role_id=1)授予新菜单权限
-- -----------------------------------------------------------
INSERT IGNORE INTO `yxshop_admin_role_auths` (`role_id`, `auth_id`, `created_at`, `updated_at`)
SELECT 1, 97, @NOW, @NOW
WHERE NOT EXISTS (
    SELECT 1 FROM `yxshop_admin_role_auths` WHERE `role_id` = 1 AND `auth_id` = 97
);

-- ---- 迁移: 2026_07_03_000005_user_levels_add_columns.sql ----
-- 用户等级表补充缺失字段
-- 后台/模型/服务层统一使用 level, experience, discount, status, is_default, description 等字段，
-- 但物理表 yxshop_user_levels 仍是旧结构（缺少这些列），导致保存时报 Unknown column 'level'。
-- 本迁移为该表补齐缺失字段。
-- 注意：MySQL 5.7 不支持 ADD COLUMN IF NOT EXISTS；status 列已存在，故此处不再添加。

SET NAMES utf8mb4;

-- ---- 迁移: 2026_07_fix_expresses_table.sql ----
-- ============================================================
-- 修复Express表字段名：express_name→name, express_code→code
-- 执行顺序：Phase 2.5
-- 日期：2026-06-20
-- ============================================================
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_user_levels`
-- [DISABLED-REDUNDANT-MIGRATION]   ADD COLUMN `level` int(11) NOT NULL DEFAULT 1 COMMENT '等级值' AFTER `name`,
-- [DISABLED-REDUNDANT-MIGRATION]   ADD COLUMN `experience` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '升级所需经验值' AFTER `level`,
-- [DISABLED-REDUNDANT-MIGRATION]   ADD COLUMN `discount` decimal(5,2) NOT NULL DEFAULT 100.00 COMMENT '折扣(百分比 0-100)' AFTER `experience`,
-- [DISABLED-REDUNDANT-MIGRATION]   ADD COLUMN `is_default` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否默认等级: 1是 0否' AFTER `status`,
-- [DISABLED-REDUNDANT-MIGRATION]   ADD COLUMN `description` varchar(255) NULL DEFAULT NULL COMMENT '等级描述' AFTER `is_default`,
-- [DISABLED-REDUNDANT-MIGRATION]   ADD INDEX `idx_app_level` (`app_id`, `level`);

-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_expresses` CHANGE `express_name` `name` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '快递公司名称';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_expresses` CHANGE `express_code` `code` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '快递公司编码';

-- ---- 迁移: 2026_08_fix_comments_table.sql ----
-- ============================================================
-- 修复Comment表字段名：is_picture→images, reply→reply_content
-- 执行顺序：Phase 2.6
-- 日期：2026-06-20
-- 说明：is_picture改为images(json数组存储图片)，reply改为reply_content
-- ============================================================

SET NAMES utf8mb4;

-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_comments` CHANGE `is_picture` `images` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '评价图片(JSON数组)';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_comments` CHANGE `reply` `reply_content` text CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '商家回复内容';

-- ---- 迁移: 2026_09_table_comments.sql ----
-- ============================================================
-- 重复表注释明确化
-- 执行顺序：Phase 3
-- 日期：2026-06-20
-- ============================================================

SET NAMES utf8mb4;

-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_item_attributes` COMMENT='属性定义表（定义属性名称和类型）';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_item_attrs` COMMENT='商品-属性值关联表（记录商品的属性值）';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_item_types` COMMENT='商品类型表（实物/虚拟/服务）';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_notification_variables` COMMENT='系统通知变量定义（如{{order_no}}、{{user_name}}）';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_notification_template_variables` COMMENT='模板-变量关联表（记录模板使用了哪些变量）';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_promotions` COMMENT='促销活动主表（定义活动规则）';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_prom_orders` COMMENT='订单级促销记录（满减/折扣等）';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_prom_goods` COMMENT='商品级促销记录（特价/限时等）';

-- ---- 迁移: 2026_10_add_indexes_and_charset.sql ----
-- ============================================================
-- 添加缺失索引
-- 执行顺序：Phase 7
-- 日期：2026-06-20
-- ============================================================

SET NAMES utf8mb4;

-- yxshop_orders 索引
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_orders` ADD INDEX `idx_status`(`status`);
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_orders` ADD INDEX `idx_pay_status`(`pay_status`);
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_orders` ADD INDEX `idx_user_id`(`user_id`);
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_orders` ADD INDEX `idx_created_at`(`created_at`);

-- yxshop_items 索引
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_items` ADD INDEX `idx_is_on_sale`(`is_on_sale`);
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_items` ADD INDEX `idx_category_id`(`category_id`);
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_items` ADD INDEX `idx_brand_id`(`brand_id`);
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_items` ADD INDEX `idx_created_at`(`created_at`);

-- yxshop_users 索引
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_users` ADD INDEX `idx_phone`(`phone`);
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_users` ADD INDEX `idx_status`(`status`);
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_users` ADD INDEX `idx_created_at`(`created_at`);

-- 字符集统一为utf8mb4
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_admin_logs` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_admin_auths` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_admin_menus` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_admin_roles` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_admins` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_advertisements` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_agent_capitals` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_agent_withdraws` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_app_helps` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_app_navbars` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_app_pages` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_app_prepay_ids` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_applies` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_apps` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_brands` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_categories` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_comments` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_coupon_goods` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_coupons` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_expresses` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_items` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_orders` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_users` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- ---- 迁移: 2026_11_create_role_auths_pivot.sql ----
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `yxshop_admin_role_auths` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '角色ID',
  `auth_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '权限(菜单)ID',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `idx_role_auth`(`role_id`, `auth_id`) USING BTREE,
  INDEX `idx_role_id`(`role_id`) USING BTREE,
  INDEX `idx_auth_id`(`auth_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '角色权限关联表' ROW_FORMAT = DYNAMIC;

-- 为超级管理员角色分配所有菜单权限
INSERT IGNORE INTO `yxshop_admin_role_auths` (`role_id`, `auth_id`, `created_at`, `updated_at`)
SELECT 1, `id`, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `yxshop_admin_menus` WHERE `is_show` = 1;

-- ---- 迁移: 2026_12_rebuild_menus_tree.sql ----
SET NAMES utf8mb4;

TRUNCATE TABLE `yxshop_admin_menus`;

INSERT IGNORE INTO `yxshop_admin_menus` (`id`, `parent_id`, `name`, `model`, `url`, `icon`, `sort`, `is_show`, `permission`, `method`, `app_id`, `created_at`, `updated_at`) VALUES
(1, 0, '仪表盘', 'dashboard', '/dashboard', 'icon-dashboard', 10, 1, 'dashboard/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(2, 0, '商品管理', 'product', NULL, 'icon-apps', 20, 1, NULL, 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(3, 2, '商品列表', 'product', '/product/items', 'icon-list', 10, 1, 'item/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(4, 2, '商品分类', 'product', '/product/categories', 'icon-menu-fold', 20, 1, 'category/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(5, 2, '商品品牌', 'product', '/product/brands', 'icon-tag', 30, 1, 'brand/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(6, 2, '商品标签', 'product', '/product/tags', 'icon-bookmark', 40, 1, 'item-tags/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(7, 2, '商品类型', 'product', '/product/types', 'icon-layers', 50, 1, 'item-types/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(8, 2, '规格管理', 'product', '/product/specs', 'icon-settings', 60, 1, 'specs/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(9, 2, '库存预警', 'product', '/product/stock-warnings', 'icon-exclamation-circle', 70, 1, 'item-stock-warnings/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(59, 2, '商品搜索', 'product', '/product/searches', 'icon-search', 80, 1, 'item-searches/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(60, 2, '商品属性', 'product', '/product/attributes', 'icon-thunder', 90, 1, 'item-attributes/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(61, 2, '商品收藏', 'product', '/product/favorites', 'icon-heart', 100, 1, 'item-favorites/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(10, 0, '订单管理', 'order', NULL, 'icon-file', 30, 1, NULL, 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(11, 10, '订单列表', 'order', '/order/list', 'icon-list', 10, 1, 'order/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(12, 10, '订单物流', 'order', '/order/deliveries', 'icon-send', 20, 1, 'order-deliveries/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(13, 10, '售后管理', 'order', '/order/after-sales', 'icon-tool', 30, 1, 'service/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(14, 10, '评价管理', 'order', '/order/comments', 'icon-star', 40, 1, 'comment/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(62, 10, '订单日志', 'order', '/order/logs', 'icon-file-text', 50, 1, 'order-logs/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(15, 0, '用户管理', 'user', NULL, 'icon-user-group', 40, 1, NULL, 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(16, 15, '用户列表', 'user', '/user/list', 'icon-user', 10, 1, 'user/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(17, 15, '用户地址', 'user', '/user/addresses', 'icon-location', 20, 1, 'user-addresses/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(18, 15, '用户等级', 'user', '/user/levels', 'icon-crown', 30, 1, 'user-levels/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(19, 15, '余额日志', 'user', '/user/money-log', 'icon-calendar', 40, 1, 'user-money-logs/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(63, 15, '用户优惠券', 'user', '/user/coupons', 'icon-gift', 50, 1, 'user-coupons/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(64, 15, '用户日志', 'user', '/user/logs', 'icon-file-text', 60, 1, 'user-logs/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20, 0, '营销管理', 'marketing', NULL, 'icon-fire', 50, 1, NULL, 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(21, 20, '优惠券', 'marketing', '/marketing/coupons', 'icon-gift', 10, 1, 'coupon/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(22, 20, '促销活动', 'marketing', '/marketing/promotions', 'icon-thunder', 20, 1, 'promotions/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(23, 20, '商品促销', 'marketing', '/marketing/prom-goods', 'icon-tag', 30, 1, 'prom-goods/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(24, 20, '订单促销', 'marketing', '/marketing/prom-orders', 'icon-percentage', 40, 1, 'prom-orders/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(25, 0, '内容管理', 'content', NULL, 'icon-edit', 60, 1, NULL, 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(26, 25, '文章管理', 'content', '/content/articles', 'icon-file', 10, 1, 'article/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(27, 25, '文章分类', 'content', '/content/article-categories', 'icon-folder', 20, 1, 'article-category/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(28, 25, '广告管理', 'content', '/content/advertisements', 'icon-image', 30, 1, 'advertisements/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(29, 25, '页面管理', 'content', '/content/pages', 'icon-layout', 40, 1, 'app-page/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(30, 0, '分销管理', 'distribution', NULL, 'icon-share-alt', 70, 1, NULL, 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(31, 30, '分销商', 'distribution', '/distribution/agents', 'icon-user-group', 10, 1, 'agents/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(32, 30, '入驻申请', 'distribution', '/distribution/applies', 'icon-idcard', 20, 1, 'apply/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(33, 30, '资金明细', 'distribution', '/distribution/capitals', 'icon-money-collect', 30, 1, 'agent-capitals/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(34, 30, '提现管理', 'distribution', '/distribution/withdraws', 'icon-wallet', 40, 1, 'agent-withdraws/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(65, 30, '推荐人', 'distribution', '/distribution/referees', 'icon-user-add', 50, 1, 'dealer-referees/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(35, 0, '财务管理', 'finance', NULL, 'icon-money-collect', 80, 1, NULL, 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(36, 35, '交易记录', 'finance', '/finance/transactions', 'icon-swap', 10, 1, 'finance/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(37, 35, '支付管理', 'finance', '/finance/payments', 'icon-credit-card', 20, 1, 'payment/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(66, 35, '利润日志', 'finance', '/finance/profit-logs', 'icon-line-chart', 30, 1, 'profit-logs/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(67, 35, '支付日志', 'finance', '/finance/payment-logs', 'icon-pay-circle', 40, 1, 'payment-logs/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(38, 0, '通知管理', 'notification', NULL, 'icon-notification', 90, 1, NULL, 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(39, 38, '通知场景', 'notification', '/notification/scenes', 'icon-layers', 10, 1, 'notification-scenes/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(40, 38, '通知模板', 'notification', '/notification/templates', 'icon-mail', 20, 1, 'notification-template/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(41, 38, '发送记录', 'notification', '/notification/sends', 'icon-send', 30, 1, 'notification-sends/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(42, 38, '通知配置', 'notification', '/notification/configs', 'icon-setting', 40, 1, 'notification-configs/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(68, 38, '通知黑名单', 'notification', '/notification/blacklists', 'icon-stop', 50, 1, 'notification-blacklists/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(69, 38, '通知变量', 'notification', '/notification/variables', 'icon-code', 60, 1, 'notification-variables/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(43, 0, '物流配送', 'logistics', NULL, 'icon-send', 100, 1, NULL, 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(44, 43, '物流公司', 'logistics', '/logistics/expresses', 'icon-rocket', 10, 1, 'express/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(45, 43, '配送模板', 'logistics', '/logistics/deliveries', 'icon-deliver', 20, 1, 'deliveries/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(46, 0, '系统设置', 'system', NULL, 'icon-setting', 110, 1, NULL, 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(47, 46, '基本设置', 'system', '/system/settings', 'icon-tool', 10, 1, 'setting/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(48, 46, '语言管理', 'system', '/system/languages', 'icon-language', 20, 1, 'language/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(49, 46, '货币管理', 'system', '/system/currencies', 'icon-dollar', 30, 1, 'currencies/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(50, 46, '地区管理', 'system', '/system/regions', 'icon-global', 40, 1, 'regions/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(51, 0, '权限管理', 'permission', NULL, 'icon-lock', 120, 1, NULL, 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(52, 51, '管理员', 'permission', '/permission/admins', 'icon-user', 10, 1, 'admin/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(53, 51, '角色管理', 'permission', '/permission/roles', 'icon-team', 20, 1, 'role/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(54, 51, '菜单管理', 'permission', '/permission/menus', 'icon-menu', 30, 1, 'menu/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(55, 0, '日志管理', 'log', NULL, 'icon-calendar', 130, 1, NULL, 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(56, 55, '登录日志', 'log', '/log/login', 'icon-login', 10, 1, 'log/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(57, 55, '操作日志', 'log', '/log/admin', 'icon-history', 20, 1, 'log/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(58, 55, '系统日志', 'log', '/log/system', 'icon-bug', 30, 1, 'log/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(70, 55, '数据变更日志', 'log', '/log/data-change', 'icon-swap', 40, 1, 'data-change-logs/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(71, 55, '短信日志', 'log', '/log/sms', 'icon-message', 50, 1, 'sms-logs/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(72, 55, '邮件日志', 'log', '/log/email', 'icon-mail', 60, 1, 'email-logs/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(73, 55, '文件日志', 'log', '/log/file', 'icon-folder', 70, 1, 'file-logs/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

INSERT IGNORE INTO `yxshop_admin_role_auths` (`role_id`, `auth_id`, `created_at`, `updated_at`)
SELECT 1, `id`, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `yxshop_admin_menus` WHERE `is_show` = 1
ON DUPLICATE KEY UPDATE `updated_at` = UNIX_TIMESTAMP();

-- ---- 迁移: 2026_13_fix_menu_permissions.sql ----
SET NAMES utf8mb4;

UPDATE `yxshop_admin_menus` SET `permission` = 'item-tags/index' WHERE `id` = 6 AND `permission` = 'item-tag/index';
UPDATE `yxshop_admin_menus` SET `permission` = 'item-types/index' WHERE `id` = 7 AND `permission` = 'item-type/index';
UPDATE `yxshop_admin_menus` SET `permission` = 'specs/index' WHERE `id` = 8 AND `permission` = 'spec/index';
UPDATE `yxshop_admin_menus` SET `permission` = 'item-stock-warnings/index' WHERE `id` = 9 AND `permission` = 'stock-warning/index';
UPDATE `yxshop_admin_menus` SET `permission` = 'order-deliveries/index' WHERE `id` = 12 AND `permission` = 'order-delivery/index';
UPDATE `yxshop_admin_menus` SET `permission` = 'user-addresses/index' WHERE `id` = 17 AND `permission` = 'user-address/index';
UPDATE `yxshop_admin_menus` SET `permission` = 'user-levels/index' WHERE `id` = 18 AND `permission` = 'user-level/index';
UPDATE `yxshop_admin_menus` SET `permission` = 'user-money-logs/index' WHERE `id` = 19 AND `permission` = 'user-money-log/index';
UPDATE `yxshop_admin_menus` SET `permission` = 'promotions/index' WHERE `id` = 22 AND `permission` = 'promotion/index';
UPDATE `yxshop_admin_menus` SET `permission` = 'prom-orders/index' WHERE `id` = 24 AND `permission` = 'prom-order/index';
UPDATE `yxshop_admin_menus` SET `permission` = 'advertisements/index' WHERE `id` = 28 AND `permission` = 'advertisement/index';
UPDATE `yxshop_admin_menus` SET `permission` = 'agents/index' WHERE `id` = 31 AND `permission` = 'agent/index';
UPDATE `yxshop_admin_menus` SET `permission` = 'agent-capitals/index' WHERE `id` = 33 AND `permission` = 'agent-capital/index';
UPDATE `yxshop_admin_menus` SET `permission` = 'agent-withdraws/index' WHERE `id` = 34 AND `permission` = 'agent-withdraw/index';
UPDATE `yxshop_admin_menus` SET `permission` = 'notification-scenes/index' WHERE `id` = 39 AND `permission` = 'notification-scene/index';
UPDATE `yxshop_admin_menus` SET `permission` = 'notification-sends/index' WHERE `id` = 41 AND `permission` = 'notification-send/index';
UPDATE `yxshop_admin_menus` SET `permission` = 'notification-configs/index' WHERE `id` = 42 AND `permission` = 'notification-config/index';
UPDATE `yxshop_admin_menus` SET `permission` = 'deliveries/index' WHERE `id` = 45 AND `permission` = 'delivery/index';
UPDATE `yxshop_admin_menus` SET `permission` = 'currencies/index' WHERE `id` = 49 AND `permission` = 'currency/index';
UPDATE `yxshop_admin_menus` SET `permission` = 'regions/index' WHERE `id` = 50 AND `permission` = 'region/index';

INSERT IGNORE INTO `yxshop_admin_menus` (`id`, `parent_id`, `name`, `model`, `url`, `icon`, `sort`, `is_show`, `permission`, `method`, `app_id`, `created_at`, `updated_at`) VALUES
(59, 2, '商品搜索', 'product', '/product/searches', 'icon-search', 80, 1, 'item-searches/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(60, 2, '商品属性', 'product', '/product/attributes', 'icon-thunder', 90, 1, 'item-attributes/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(61, 2, '商品收藏', 'product', '/product/favorites', 'icon-heart', 100, 1, 'item-favorites/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(62, 10, '订单日志', 'order', '/order/logs', 'icon-file-text', 50, 1, 'order-logs/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(63, 15, '用户优惠券', 'user', '/user/coupons', 'icon-gift', 50, 1, 'user-coupons/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(64, 15, '用户日志', 'user', '/user/logs', 'icon-file-text', 60, 1, 'user-logs/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(65, 30, '推荐人', 'distribution', '/distribution/referees', 'icon-user-add', 50, 1, 'dealer-referees/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(66, 35, '利润日志', 'finance', '/finance/profit-logs', 'icon-line-chart', 30, 1, 'profit-logs/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(67, 35, '支付日志', 'finance', '/finance/payment-logs', 'icon-pay-circle', 40, 1, 'payment-logs/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(68, 38, '通知黑名单', 'notification', '/notification/blacklists', 'icon-stop', 50, 1, 'notification-blacklists/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(69, 38, '通知变量', 'notification', '/notification/variables', 'icon-code', 60, 1, 'notification-variables/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(70, 55, '数据变更日志', 'log', '/log/data-change', 'icon-swap', 40, 1, 'data-change-logs/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(71, 55, '短信日志', 'log', '/log/sms', 'icon-message', 50, 1, 'sms-logs/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(72, 55, '邮件日志', 'log', '/log/email', 'icon-mail', 60, 1, 'email-logs/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(73, 55, '文件日志', 'log', '/log/file', 'icon-folder', 70, 1, 'file-logs/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

INSERT IGNORE INTO `yxshop_admin_role_auths` (`role_id`, `auth_id`, `created_at`, `updated_at`)
SELECT 1, `id`, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `yxshop_admin_menus` WHERE `id` >= 59 AND `id` <= 73 AND `is_show` = 1
ON DUPLICATE KEY UPDATE `updated_at` = UNIX_TIMESTAMP();

-- ---- 迁移: 2026_14_create_missing_tables.sql ----
-- 创建文章分类表
CREATE TABLE IF NOT EXISTS `yxshop_article_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '分类名称',
  `parent_id` int unsigned NOT NULL DEFAULT 0 COMMENT '父级ID',
  `sort` int unsigned NOT NULL DEFAULT 0 COMMENT '排序',
  `status` tinyint unsigned NOT NULL DEFAULT 1 COMMENT '状态 1启用 0禁用',
  `app_id` int unsigned NOT NULL DEFAULT 0 COMMENT '应用ID',
  `created_at` int unsigned NOT NULL DEFAULT 0,
  `updated_at` int unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_app_id` (`app_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='文章分类';

-- 创建文章表
CREATE TABLE IF NOT EXISTS `yxshop_articles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL DEFAULT '' COMMENT '标题',
  `category_id` int unsigned NOT NULL DEFAULT 0 COMMENT '分类ID',
  `author` varchar(50) NOT NULL DEFAULT '' COMMENT '作者',
  `cover` varchar(500) NOT NULL DEFAULT '' COMMENT '封面图',
  `description` varchar(500) NOT NULL DEFAULT '' COMMENT '摘要',
  `content` longtext COMMENT '内容',
  `sort` int unsigned NOT NULL DEFAULT 0 COMMENT '排序',
  `status` tinyint unsigned NOT NULL DEFAULT 1 COMMENT '状态 1启用 0禁用',
  `is_top` tinyint unsigned NOT NULL DEFAULT 0 COMMENT '是否置顶',
  `view_count` int unsigned NOT NULL DEFAULT 0 COMMENT '浏览量',
  `app_id` int unsigned NOT NULL DEFAULT 0 COMMENT '应用ID',
  `created_at` int unsigned NOT NULL DEFAULT 0,
  `updated_at` int unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_category_id` (`category_id`),
  KEY `idx_app_id` (`app_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='文章';

-- 创建代理商表
CREATE TABLE IF NOT EXISTS `yxshop_agents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL DEFAULT 0 COMMENT '用户ID',
  `parent_id` int unsigned NOT NULL DEFAULT 0 COMMENT '上级代理ID',
  `level` tinyint unsigned NOT NULL DEFAULT 1 COMMENT '代理等级',
  `agent_name` varchar(50) NOT NULL DEFAULT '' COMMENT '代理名称',
  `agent_code` varchar(50) NOT NULL DEFAULT '' COMMENT '代理编码',
  `status` tinyint unsigned NOT NULL DEFAULT 1 COMMENT '状态 1启用 0禁用',
  `commission_rate` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT '佣金比例',
  `total_commission` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '累计佣金',
  `available_commission` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '可用佣金',
  `frozen_commission` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '冻结佣金',
  `app_id` int unsigned NOT NULL DEFAULT 0 COMMENT '应用ID',
  `created_at` int unsigned NOT NULL DEFAULT 0,
  `updated_at` int unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_agent_code` (`agent_code`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_app_id` (`app_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='代理商';

-- 修复 app_pages 表：添加缺失的 sort 字段
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_app_pages` ADD COLUMN `sort` int unsigned NOT NULL DEFAULT 0 COMMENT '排序' AFTER `page_data`;

-- 修复 applies 表：确保 apply_type 和 apply_status 字段有默认值
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_applies` MODIFY COLUMN `apply_type` tinyint unsigned NOT NULL DEFAULT 0 COMMENT '申请类型';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_applies` MODIFY COLUMN `apply_status` tinyint unsigned NOT NULL DEFAULT 0 COMMENT '申请状态';

-- ---- 迁移: 2026_15_fix_upload_tables.sql ----
-- Fix upload_files: add missing columns, rename extension -> file_ext
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_upload_files`
-- [DISABLED-REDUNDANT-MIGRATION]   ADD COLUMN `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 AFTER `created_at`,
-- [DISABLED-REDUNDANT-MIGRATION]   ADD COLUMN `file_path` varchar(255) NOT NULL DEFAULT '' AFTER `file_name`,
-- [DISABLED-REDUNDANT-MIGRATION] 
-- [DISABLED-REDUNDANT-MIGRATION] -- ---- 迁移: 2026_16_normalize_all_tables.sql ----
-- [DISABLED-REDUNDANT-MIGRATION] -- ============================================================
-- [DISABLED-REDUNDANT-MIGRATION] -- yxshop Database Normalization Migration
-- [DISABLED-REDUNDANT-MIGRATION] -- Order: PART2 first (rename time cols), then PART1 (add deleted_at), then PART3 (add status), then PART4 (snowflake PK)
-- [DISABLED-REDUNDANT-MIGRATION] -- ============================================================
-- [DISABLED-REDUNDANT-MIGRATION] 
-- [DISABLED-REDUNDANT-MIGRATION] -- ============================================================
-- [DISABLED-REDUNDANT-MIGRATION] -- PART 2: create_time/update_time -> created_at/updated_at (MUST be first)
-- [DISABLED-REDUNDANT-MIGRATION] -- ============================================================
-- [DISABLED-REDUNDANT-MIGRATION] 
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_item_spec_price_histories` CHANGE COLUMN `create_time` `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_item_spec_price_histories` ADD COLUMN `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 AFTER `created_at`;

-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_item_spec_sales` CHANGE COLUMN `create_time` `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_item_spec_sales` CHANGE COLUMN `update_time` `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0;

-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_item_spec_stock_logs` CHANGE COLUMN `create_time` `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_item_spec_stock_logs` ADD COLUMN `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 AFTER `created_at`;

-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_item_tags` CHANGE COLUMN `create_time` `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_item_tags` CHANGE COLUMN `update_time` `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0;

-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_item_tag_relations` CHANGE COLUMN `create_time` `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_item_tag_relations` ADD COLUMN `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 AFTER `created_at`;

-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_order_addresses` CHANGE COLUMN `create_time` `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_order_addresses` ADD COLUMN `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 AFTER `created_at`;

-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_order_agents` CHANGE COLUMN `create_time` `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_order_agents` CHANGE COLUMN `update_time` `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0;

-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_order_deliveries` CHANGE COLUMN `create_time` `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_order_deliveries` ADD COLUMN `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 AFTER `created_at`;

-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_order_goods` CHANGE COLUMN `create_time` `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_order_goods` ADD COLUMN `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 AFTER `created_at`;

-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_prizes` CHANGE COLUMN `create_time` `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_prizes` CHANGE COLUMN `update_time` `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0;

-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_prize_lists` CHANGE COLUMN `create_time` `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_prize_lists` CHANGE COLUMN `update_time` `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0;

-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_profit_logs` CHANGE COLUMN `create_time` `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_profit_logs` CHANGE COLUMN `update_time` `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0;

-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_services` CHANGE COLUMN `create_time` `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_services` CHANGE COLUMN `update_time` `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0;

-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_service_details` CHANGE COLUMN `create_time` `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_service_details` CHANGE COLUMN `update_time` `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0;

-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_service_images` CHANGE COLUMN `create_time` `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_service_images` CHANGE COLUMN `update_time` `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0;

-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_signs` CHANGE COLUMN `create_time` `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_signs` ADD COLUMN `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 AFTER `created_at`;

-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_specs` CHANGE COLUMN `create_time` `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_specs` CHANGE COLUMN `update_time` `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0;

-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_spec_items` CHANGE COLUMN `create_time` `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_spec_items` CHANGE COLUMN `update_time` `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0;

-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_upload_types` CHANGE COLUMN `create_time` `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_upload_types` CHANGE COLUMN `update_time` `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0;

-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_user_coupons` CHANGE COLUMN `create_time` `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_user_coupons` CHANGE COLUMN `update_time` `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0;

-- ============================================================
-- PART 1: is_delete -> deleted_at (after time cols are renamed)
-- ============================================================

-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_admins` ADD COLUMN `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '删除时间' AFTER `updated_at`;
UPDATE `yxshop_admins` SET `deleted_at` = `updated_at` WHERE `is_delete` = 1;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_admins` DROP COLUMN `is_delete`;

-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_admin_roles` ADD COLUMN `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '删除时间' AFTER `updated_at`;
UPDATE `yxshop_admin_roles` SET `deleted_at` = `updated_at` WHERE `is_delete` = 1;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_admin_roles` DROP COLUMN `is_delete`;

-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_advertisements` ADD COLUMN `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '删除时间' AFTER `updated_at`;
UPDATE `yxshop_advertisements` SET `deleted_at` = `updated_at` WHERE `is_delete` = 1;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_advertisements` DROP COLUMN `is_delete`;

-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_app_pages` ADD COLUMN `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '删除时间' AFTER `updated_at`;
UPDATE `yxshop_app_pages` SET `deleted_at` = `updated_at` WHERE `is_delete` = 1;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_app_pages` DROP COLUMN `is_delete`;

-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_comments` ADD COLUMN `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '删除时间' AFTER `updated_at`;
UPDATE `yxshop_comments` SET `deleted_at` = `updated_at` WHERE `is_delete` = 1;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_comments` DROP COLUMN `is_delete`;

-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_coupons` ADD COLUMN `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '删除时间' AFTER `updated_at`;
UPDATE `yxshop_coupons` SET `deleted_at` = `updated_at` WHERE `is_delete` = 1;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_coupons` DROP COLUMN `is_delete`;

-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_coupon_goods` ADD COLUMN `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '删除时间' AFTER `updated_at`;
UPDATE `yxshop_coupon_goods` SET `deleted_at` = `updated_at` WHERE `is_delete` = 1;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_coupon_goods` DROP COLUMN `is_delete`;

-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_discounts` ADD COLUMN `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '删除时间' AFTER `updated_at`;
UPDATE `yxshop_discounts` SET `deleted_at` = `updated_at` WHERE `is_delete` = 1;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_discounts` DROP COLUMN `is_delete`;

-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_items` ADD COLUMN `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '删除时间' AFTER `updated_at`;
UPDATE `yxshop_items` SET `deleted_at` = `updated_at` WHERE `is_delete` = 1;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_items` DROP COLUMN `is_delete`;

-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_orders` ADD COLUMN `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '删除时间' AFTER `updated_at`;
UPDATE `yxshop_orders` SET `deleted_at` = `updated_at` WHERE `is_delete` = 1;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_orders` DROP COLUMN `is_delete`;

-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_services` ADD COLUMN `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '删除时间' AFTER `updated_at`;
UPDATE `yxshop_services` SET `deleted_at` = `updated_at` WHERE `is_delete` = 1;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_services` DROP COLUMN `is_delete`;

-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_service_details` ADD COLUMN `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '删除时间' AFTER `updated_at`;
UPDATE `yxshop_service_details` SET `deleted_at` = `updated_at` WHERE `is_delete` = 1;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_service_details` DROP COLUMN `is_delete`;

-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_upload_files` ADD COLUMN `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '删除时间' AFTER `updated_at`;
UPDATE `yxshop_upload_files` SET `deleted_at` = `updated_at` WHERE `is_delete` = 1;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_upload_files` DROP COLUMN `is_delete`;

-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_upload_groups` ADD COLUMN `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '删除时间' AFTER `updated_at`;
UPDATE `yxshop_upload_groups` SET `deleted_at` = `updated_at` WHERE `is_delete` = 1;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_upload_groups` DROP COLUMN `is_delete`;

-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_users` ADD COLUMN `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '删除时间' AFTER `updated_at`;
UPDATE `yxshop_users` SET `deleted_at` = `updated_at` WHERE `is_delete` = 1;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_users` DROP COLUMN `is_delete`;

-- ============================================================
-- PART 3: Add status to core business tables
-- ============================================================

-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_advertisements` ADD COLUMN `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '1启用 0禁用' AFTER `app_id`;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_item_tags` ADD COLUMN `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '1启用 0禁用' AFTER `app_id`;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_item_types` ADD COLUMN `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '1启用 0禁用' AFTER `app_id`;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_specs` ADD COLUMN `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '1启用 0禁用' AFTER `app_id`;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_user_coupons` ADD COLUMN `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '1可用 0不可用' AFTER `app_id`;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_deliveries` ADD COLUMN `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '1启用 0禁用' AFTER `app_id`;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_expresses` ADD COLUMN `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '1启用 0禁用' AFTER `app_id`;

-- ============================================================
-- PART 4: Core tables - remove AUTO_INCREMENT, use snowflake ID
-- ============================================================

-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_items` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_orders` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_users` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_advertisements` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_articles` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_article_categories` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_comments` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_coupons` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_deliveries` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_discounts` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_expresses` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_games` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_payments` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_promotions` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_services` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_service_details` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_service_images` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_specs` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_spec_items` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_spec_item_prices` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_item_types` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_item_tags` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_item_attributes` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_item_attrs` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_item_images` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_notifications` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_notification_configs` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_notification_scenes` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_notification_templates` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_settings` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_user_addresses` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_user_levels` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_user_coupons` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_upload_groups` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_upload_types` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_prizes` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_prize_lists` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL;

-- ---- 迁移: 2026_17_enterprise_refactor.sql ----
/*
 * YXwebai-admin 企业级电商数据库表结构优化与补充
 * 
 * 迁移目的：对现有107张表进行彻底重构优化，并补充20个企业级缺失模块的表结构
 * 执行顺序：Phase 1 → Phase 2 → Phase 3 → Phase 4 → Phase 5
 * 回滚方案：执行文件末尾的回滚SQL（逆序执行）
 * 
 * 命名规范总则：
 *   表名：yxshop_ 前缀 + snake_case + 复数形式
 *   主键：统一为 id
 *   外键：关联表名单数_id（如 user_id, item_id, order_id）
 *   商品相关：统一用 item（不用 goods/product）
 *   时间字段：created_at / updated_at / deleted_at（int 时间戳）
 *   软删除：deleted_at int(11) UNSIGNED NOT NULL DEFAULT 0
 *   状态字段：status tinyint
 *   布尔字段：is_ 前缀
 *   金额字段：decimal(10,2) 或 decimal(12,2)
 *   版本标识：COMMENT 中标注 [Community] 或 [Enterprise]
 *   字符集：utf8mb4 + utf8mb4_general_ci
 * 
 * Date: 2026-06-26
 */

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- Phase 1: 命名规范化
-- ============================================================

-- -----------------------------------------------------------
-- Phase 1.1a: 表名重命名（goods → items）
-- -----------------------------------------------------------
RENAME TABLE `yxshop_order_goods` TO `yxshop_order_items`;
RENAME TABLE `yxshop_coupon_goods` TO `yxshop_coupon_items`;
RENAME TABLE `yxshop_prom_goods` TO `yxshop_prom_items`;

-- -----------------------------------------------------------
-- Phase 1.1b: 字段重命名（goods_* → item_*）
-- -----------------------------------------------------------
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_order_items` CHANGE COLUMN `goods_price` `item_price` decimal(10, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '商品价格';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_order_items` CHANGE COLUMN `goods_attr` `item_attr` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '商品规格信息';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_order_items` CHANGE COLUMN `goods_no` `item_no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '商品编码';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_order_items` CHANGE COLUMN `goods_weight` `item_weight` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '商品重量(g)';

-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_coupon_items` CHANGE COLUMN `goods_id` `item_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '商品ID';

-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_item_attrs` CHANGE COLUMN `goods_id` `item_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '商品ID';

-- -----------------------------------------------------------
-- Phase 1.2: 主键统一为 id
-- -----------------------------------------------------------
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_roles` CHANGE COLUMN `role_id` `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_roles` DROP PRIMARY KEY, ADD PRIMARY KEY (`id`);

-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_order_logs` CHANGE COLUMN `log_id` `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_order_logs` DROP PRIMARY KEY, ADD PRIMARY KEY (`id`);

-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_payment_logs` CHANGE COLUMN `log_id` `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_payment_logs` DROP PRIMARY KEY, ADD PRIMARY KEY (`id`);

-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_user_logs` CHANGE COLUMN `log_id` `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_user_logs` DROP PRIMARY KEY, ADD PRIMARY KEY (`id`);

-- -----------------------------------------------------------
-- Phase 1.3: 时间字段统一
-- -----------------------------------------------------------
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_item_stock_warnings` CHANGE COLUMN `create_time` `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_item_stock_warnings` CHANGE COLUMN `update_time` `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间';

-- -----------------------------------------------------------
-- Phase 1.4: 单数表名改复数
-- -----------------------------------------------------------
RENAME TABLE `yxshop_card` TO `yxshop_cards`;
RENAME TABLE `yxshop_integral_log` TO `yxshop_integral_logs`;
RENAME TABLE `yxshop_user_money_log` TO `yxshop_user_money_logs`;

-- -----------------------------------------------------------
-- Phase 1.5: camelCase 字段改 snake_case
-- -----------------------------------------------------------
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_cards` CHANGE COLUMN `nickName` `nick_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '持有人昵称';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_after_sales` CHANGE COLUMN `reasonsText` `reasons_text` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '申请原因文本';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_games` CHANGE COLUMN `setnum` `set_num` int(5) NOT NULL COMMENT '中奖次数';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_games` CHANGE COLUMN `setup` `set_up` int(1) NOT NULL COMMENT '1为每日中奖次数,0为本轮活动中奖次数';

-- -----------------------------------------------------------
-- Phase 1.6: COMMENT 乱码修复
-- -----------------------------------------------------------
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_app_pages` MODIFY COLUMN `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除时间(0未删)';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_comments` MODIFY COLUMN `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除时间(0未删)';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_coupon_items` MODIFY COLUMN `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除时间(0未删)';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_discounts` MODIFY COLUMN `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除时间(0未删)';


-- ============================================================
-- Phase 2: 重复表合并（彻底重构）
-- ============================================================

-- -----------------------------------------------------------
-- Phase 2.1: 合并 yxshop_roles → yxshop_admin_roles
-- -----------------------------------------------------------
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_admin_roles` ADD COLUMN `permissions` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '权限列表(JSON格式)' AFTER `role_desc`;

INSERT IGNORE INTO `yxshop_admin_roles` (`id`, `role_name`, `role_desc`, `permissions`, `app_id`, `created_at`, `updated_at`, `deleted_at`)
SELECT `id`, `role_name`, `role_desc`, `permissions`, `app_id`, `created_at`, `updated_at`, 0
FROM `yxshop_roles`
WHERE `role_name` NOT IN (SELECT `role_name` FROM `yxshop_admin_roles`);

DROP TABLE IF EXISTS `yxshop_roles`;

-- -----------------------------------------------------------
-- Phase 2.2: 合并 yxshop_spec_item_prices → yxshop_item_spec_prices
-- -----------------------------------------------------------
INSERT IGNORE INTO `yxshop_item_spec_prices` (`item_id`, `spec_key`, `spec_key_name`, `price`, `cost_price`, `market_price`, `store_count`, `weight`, `sku`, `image`, `app_id`, `created_at`, `updated_at`)
SELECT 
  sip.`goods_id`,
  sip.`key`,
  sip.`key_name`,
  COALESCE(sip.`shop_price`, sip.`price`, 0.00),
  sip.`cost_price`,
  sip.`market_price`,
  COALESCE(sip.`store_count`, 0),
  sip.`weight`,
  sip.`sku`,
  sip.`image`,
  COALESCE(sip.`app_id`, 0),
  UNIX_TIMESTAMP(),
  UNIX_TIMESTAMP()
FROM `yxshop_spec_item_prices` sip
WHERE sip.`deleted_at` = 0
  AND NOT EXISTS (
    SELECT 1 FROM `yxshop_item_spec_prices` isp 
    WHERE isp.`item_id` = sip.`goods_id` AND isp.`spec_key` = sip.`key`
  );

DROP TABLE IF EXISTS `yxshop_spec_item_prices`;


-- ============================================================
-- Phase 3: orders 表冗余字段直接删除
-- ============================================================

-- 售后相关字段（已通过 yxshop_after_sales 关联查询）
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_orders` DROP COLUMN `sub_status`;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_orders` DROP COLUMN `sub_type`;

-- 拼团相关字段（将迁移至 group_buy_members 表）
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_orders` DROP COLUMN `prom_type`;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_orders` DROP COLUMN `prom_num`;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_orders` DROP COLUMN `prom_statis`;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_orders` DROP COLUMN `prom_time`;

-- 退款相关字段（已通过 yxshop_refund_records 关联查询）
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_orders` DROP COLUMN `refund_no`;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_orders` DROP COLUMN `refund_desc`;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_orders` DROP COLUMN `refund_status`;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_orders` DROP COLUMN `refund_amount`;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_orders` DROP COLUMN `refund_time`;

-- 其他冗余字段
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_orders` DROP COLUMN `car_no`;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_orders` DROP COLUMN `selected_date`;


-- ============================================================
-- Phase 4: 新增企业级业务表
-- ============================================================

-- -----------------------------------------------------------
-- 4.1 供应商/商户体系 [Enterprise]
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `yxshop_suppliers`;
CREATE TABLE `yxshop_suppliers` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `supplier_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '供应商名称',
  `contact_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '联系人姓名',
  `contact_phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '联系电话',
  `contact_email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '联系邮箱',
  `address` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '地址',
  `settlement_cycle` tinyint(3) UNSIGNED NOT NULL DEFAULT 3 COMMENT '结算周期(1日结 2周结 3月结)',
  `commission_rate` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT '佣金比例(0-100)',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态(0禁用 1启用)',
  `remark` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '备注',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '应用ID',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除时间(0未删)',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_app_id`(`app_id`) USING BTREE,
  INDEX `idx_status`(`status`) USING BTREE,
  INDEX `idx_supplier_name`(`supplier_name`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '供应商表 [Enterprise]' ROW_FORMAT = DYNAMIC;

DROP TABLE IF EXISTS `yxshop_merchant_applies`;
CREATE TABLE `yxshop_merchant_applies` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `merchant_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '商户名称',
  `business_license` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '营业执照编号',
  `legal_person` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '法人姓名',
  `contact_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '联系人',
  `contact_phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '联系电话',
  `bank_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '开户银行',
  `bank_account` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '银行账号',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '审核状态(0待审核 1已通过 2已驳回)',
  `reject_reason` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '驳回原因',
  `auditor_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '审核人ID',
  `audited_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '审核时间',
  `supplier_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '审核通过后创建的供应商ID',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '应用ID',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除时间(0未删)',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_app_id`(`app_id`) USING BTREE,
  INDEX `idx_status`(`status`) USING BTREE,
  INDEX `idx_business_license`(`business_license`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '商户入驻申请表 [Enterprise]' ROW_FORMAT = DYNAMIC;

DROP TABLE IF EXISTS `yxshop_supplier_settlements`;
CREATE TABLE `yxshop_supplier_settlements` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `supplier_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '供应商ID',
  `settlement_no` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '结算单号',
  `start_date` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '结算开始日期',
  `end_date` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '结算结束日期',
  `order_amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '订单金额',
  `commission_amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '佣金金额',
  `settlement_amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '应结金额',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '结算状态(0待结算 1已结算 2已驳回)',
  `settled_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '结算时间',
  `payment_voucher` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '支付凭证',
  `remark` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '备注',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '应用ID',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除时间(0未删)',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_settlement_no`(`settlement_no`) USING BTREE,
  INDEX `idx_supplier_id`(`supplier_id`) USING BTREE,
  INDEX `idx_app_id`(`app_id`) USING BTREE,
  INDEX `idx_status`(`status`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '商户结算表 [Enterprise]' ROW_FORMAT = DYNAMIC;

-- -----------------------------------------------------------
-- 4.2 仓库管理 [Enterprise]
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `yxshop_warehouses`;
CREATE TABLE `yxshop_warehouses` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `warehouse_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '仓库名称',
  `warehouse_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '仓库编码',
  `address` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '仓库地址',
  `contact_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '联系人',
  `contact_phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '联系电话',
  `warehouse_type` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '仓库类型(1总仓 2分仓 3虚拟仓)',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态(0禁用 1启用)',
  `remark` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '备注',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '应用ID',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除时间(0未删)',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_warehouse_code`(`warehouse_code`) USING BTREE,
  INDEX `idx_app_id`(`app_id`) USING BTREE,
  INDEX `idx_status`(`status`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '仓库表 [Enterprise]' ROW_FORMAT = DYNAMIC;

DROP TABLE IF EXISTS `yxshop_warehouse_stocks`;
CREATE TABLE `yxshop_warehouse_stocks` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `warehouse_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '仓库ID',
  `item_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '商品ID',
  `spec_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '规格ID',
  `quantity` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '库存数量',
  `locked_quantity` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '锁定数量',
  `available_quantity` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '可用数量',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '应用ID',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_warehouse_item_spec`(`warehouse_id`, `item_id`, `spec_id`) USING BTREE,
  INDEX `idx_warehouse_id`(`warehouse_id`) USING BTREE,
  INDEX `idx_item_id`(`item_id`) USING BTREE,
  INDEX `idx_app_id`(`app_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '仓库库存表 [Enterprise]' ROW_FORMAT = DYNAMIC;

DROP TABLE IF EXISTS `yxshop_stock_transfers`;
CREATE TABLE `yxshop_stock_transfers` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `transfer_no` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '调拨单号',
  `from_warehouse_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '源仓库ID',
  `to_warehouse_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '目标仓库ID',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '调拨状态(0待审核 1已审核 2已完成 3已拒绝)',
  `auditor_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '审核人ID',
  `audited_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '审核时间',
  `remark` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '备注',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '应用ID',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_transfer_no`(`transfer_no`) USING BTREE,
  INDEX `idx_from_warehouse`(`from_warehouse_id`) USING BTREE,
  INDEX `idx_to_warehouse`(`to_warehouse_id`) USING BTREE,
  INDEX `idx_status`(`status`) USING BTREE,
  INDEX `idx_app_id`(`app_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '库存调拨表 [Enterprise]' ROW_FORMAT = DYNAMIC;

DROP TABLE IF EXISTS `yxshop_stock_transfer_items`;
CREATE TABLE `yxshop_stock_transfer_items` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `transfer_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '调拨单ID',
  `item_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '商品ID',
  `spec_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '规格ID',
  `quantity` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '调拨数量',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '应用ID',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_transfer_id`(`transfer_id`) USING BTREE,
  INDEX `idx_item_id`(`item_id`) USING BTREE,
  INDEX `idx_app_id`(`app_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '库存调拨明细表 [Enterprise]' ROW_FORMAT = DYNAMIC;

-- -----------------------------------------------------------
-- 4.3 采购管理 [Enterprise]
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `yxshop_purchase_orders`;
CREATE TABLE `yxshop_purchase_orders` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `purchase_no` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '采购单号',
  `supplier_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '供应商ID',
  `warehouse_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '仓库ID',
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '总金额',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '采购状态(0草稿 1待审核 2已审核 3部分入库 4已入库 5已取消)',
  `auditor_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '审核人ID',
  `audited_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '审核时间',
  `remark` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '备注',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '应用ID',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_purchase_no`(`purchase_no`) USING BTREE,
  INDEX `idx_supplier_id`(`supplier_id`) USING BTREE,
  INDEX `idx_warehouse_id`(`warehouse_id`) USING BTREE,
  INDEX `idx_status`(`status`) USING BTREE,
  INDEX `idx_app_id`(`app_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '采购单表 [Enterprise]' ROW_FORMAT = DYNAMIC;

DROP TABLE IF EXISTS `yxshop_purchase_order_items`;
CREATE TABLE `yxshop_purchase_order_items` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `purchase_order_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '采购单ID',
  `item_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '商品ID',
  `spec_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '规格ID',
  `quantity` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '采购数量',
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '采购单价',
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '小计金额',
  `received_quantity` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '已入库数量',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '应用ID',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_purchase_order_id`(`purchase_order_id`) USING BTREE,
  INDEX `idx_item_id`(`item_id`) USING BTREE,
  INDEX `idx_app_id`(`app_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '采购单明细表 [Enterprise]' ROW_FORMAT = DYNAMIC;

-- -----------------------------------------------------------
-- 4.4 发票管理 [Enterprise]
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `yxshop_invoice_headers`;
CREATE TABLE `yxshop_invoice_headers` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
  `header_name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '抬头名称',
  `invoice_type` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '发票类型(1个人 2企业)',
  `tax_no` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '税号',
  `reg_address` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '注册地址',
  `reg_phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '注册电话',
  `bank_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '开户银行',
  `bank_account` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '银行账号',
  `is_default` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否默认(0否 1是)',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '应用ID',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除时间(0未删)',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_user_id`(`user_id`) USING BTREE,
  INDEX `idx_app_id`(`app_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '发票抬头表 [Enterprise]' ROW_FORMAT = DYNAMIC;

DROP TABLE IF EXISTS `yxshop_invoice_records`;
CREATE TABLE `yxshop_invoice_records` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `order_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '订单ID',
  `invoice_header_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '发票抬头ID',
  `invoice_content` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '发票内容',
  `invoice_amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '开票金额',
  `invoice_no` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '发票号码',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '开票状态(0待开票 1已开票 2开票失败)',
  `fail_reason` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '失败原因',
  `invoiced_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '开票时间',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '应用ID',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_order_id`(`order_id`) USING BTREE,
  INDEX `idx_invoice_header_id`(`invoice_header_id`) USING BTREE,
  INDEX `idx_status`(`status`) USING BTREE,
  INDEX `idx_app_id`(`app_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '发票开具记录表 [Enterprise]' ROW_FORMAT = DYNAMIC;

-- -----------------------------------------------------------
-- 4.5 商品审核 [Enterprise]
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `yxshop_item_audit_logs`;
CREATE TABLE `yxshop_item_audit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `item_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '商品ID',
  `audit_type` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '审核类型(1上架审核 2修改审核)',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '审核状态(0待审核 1已通过 2已驳回)',
  `submitter_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '提交人ID',
  `auditor_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '审核人ID',
  `audit_opinion` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '审核意见',
  `audited_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '审核时间',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '应用ID',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_item_id`(`item_id`) USING BTREE,
  INDEX `idx_status`(`status`) USING BTREE,
  INDEX `idx_submitter_id`(`submitter_id`) USING BTREE,
  INDEX `idx_app_id`(`app_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '商品审核日志表 [Enterprise]' ROW_FORMAT = DYNAMIC;

-- -----------------------------------------------------------
-- 4.6 秒杀/拼团 [Enterprise]
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `yxshop_seckill_sessions`;
CREATE TABLE `yxshop_seckill_sessions` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `session_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '场次名称',
  `start_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '开始时间',
  `end_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '结束时间',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态(0禁用 1启用)',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '应用ID',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_start_end`(`start_at`, `end_at`) USING BTREE,
  INDEX `idx_status`(`status`) USING BTREE,
  INDEX `idx_app_id`(`app_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '秒杀场次表 [Enterprise]' ROW_FORMAT = DYNAMIC;

DROP TABLE IF EXISTS `yxshop_seckill_items`;
CREATE TABLE `yxshop_seckill_items` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `session_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '场次ID',
  `item_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '商品ID',
  `spec_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '规格ID',
  `seckill_price` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '秒杀价格',
  `seckill_stock` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '秒杀库存',
  `sold_count` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '已售数量',
  `limit_quantity` int(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT '限购数量',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '应用ID',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_session_id`(`session_id`) USING BTREE,
  INDEX `idx_item_id`(`item_id`) USING BTREE,
  INDEX `idx_app_id`(`app_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '秒杀商品表 [Enterprise]' ROW_FORMAT = DYNAMIC;

DROP TABLE IF EXISTS `yxshop_group_buys`;
CREATE TABLE `yxshop_group_buys` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `activity_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '活动名称',
  `item_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '商品ID',
  `group_price` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '拼团价格',
  `required_count` tinyint(3) UNSIGNED NOT NULL DEFAULT 2 COMMENT '成团人数',
  `start_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '开始时间',
  `end_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '结束时间',
  `expire_hours` int(11) UNSIGNED NOT NULL DEFAULT 24 COMMENT '成团时限(小时)',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态(0禁用 1启用)',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '应用ID',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_item_id`(`item_id`) USING BTREE,
  INDEX `idx_start_end`(`start_at`, `end_at`) USING BTREE,
  INDEX `idx_status`(`status`) USING BTREE,
  INDEX `idx_app_id`(`app_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '拼团活动表 [Enterprise]' ROW_FORMAT = DYNAMIC;

DROP TABLE IF EXISTS `yxshop_group_buy_members`;
CREATE TABLE `yxshop_group_buy_members` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `group_buy_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '拼团活动ID',
  `group_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '拼团记录ID(同一次拼团共享)',
  `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
  `is_leader` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否团长(0否 1是)',
  `order_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '订单ID',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态(0待成团 1已成团 2失败)',
  `joined_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '参与时间',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '应用ID',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_group_buy_id`(`group_buy_id`) USING BTREE,
  INDEX `idx_group_id`(`group_id`) USING BTREE,
  INDEX `idx_user_id`(`user_id`) USING BTREE,
  INDEX `idx_order_id`(`order_id`) USING BTREE,
  INDEX `idx_app_id`(`app_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '拼团参与记录表 [Enterprise]' ROW_FORMAT = DYNAMIC;

-- -----------------------------------------------------------
-- 4.7 用户OAuth绑定 [Enterprise]
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `yxshop_user_oauths`;
CREATE TABLE `yxshop_user_oauths` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
  `oauth_type` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '第三方类型(wechat/alipay/qq/weibo)',
  `openid` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '第三方OpenID',
  `unionid` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '第三方UnionID',
  `nickname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '第三方昵称',
  `avatar` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '第三方头像',
  `access_token` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '访问令牌(加密存储)',
  `refresh_token` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '刷新令牌(加密存储)',
  `expires_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '令牌过期时间',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '应用ID',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除时间(0未删)',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_oauth_openid`(`oauth_type`, `openid`) USING BTREE,
  INDEX `idx_user_id`(`user_id`) USING BTREE,
  INDEX `idx_unionid`(`unionid`) USING BTREE,
  INDEX `idx_app_id`(`app_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '用户第三方OAuth绑定表 [Enterprise]' ROW_FORMAT = DYNAMIC;

-- -----------------------------------------------------------
-- 4.8 商品参数模板 [Community]
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `yxshop_param_templates`;
CREATE TABLE `yxshop_param_templates` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `template_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '模板名称',
  `category_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联分类ID',
  `sort` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序值',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态(0禁用 1启用)',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '应用ID',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除时间(0未删)',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_category_id`(`category_id`) USING BTREE,
  INDEX `idx_app_id`(`app_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '商品参数模板表 [Community]' ROW_FORMAT = DYNAMIC;

DROP TABLE IF EXISTS `yxshop_param_template_items`;
CREATE TABLE `yxshop_param_template_items` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `template_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '模板ID',
  `param_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '参数名称',
  `param_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'text' COMMENT '参数类型(text/number/enum)',
  `param_values` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '可选值(JSON数组)',
  `sort` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序值',
  `is_required` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否必填(0否 1是)',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '应用ID',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_template_id`(`template_id`) USING BTREE,
  INDEX `idx_app_id`(`app_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '参数模板项表 [Community]' ROW_FORMAT = DYNAMIC;

DROP TABLE IF EXISTS `yxshop_item_param_values`;
CREATE TABLE `yxshop_item_param_values` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `item_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '商品ID',
  `template_item_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '参数项ID',
  `param_value` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '参数值',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '应用ID',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_item_template_item`(`item_id`, `template_item_id`) USING BTREE,
  INDEX `idx_item_id`(`item_id`) USING BTREE,
  INDEX `idx_template_item_id`(`template_item_id`) USING BTREE,
  INDEX `idx_app_id`(`app_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '商品参数值表 [Community]' ROW_FORMAT = DYNAMIC;

-- -----------------------------------------------------------
-- 4.9 商品咨询 [Enterprise]
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `yxshop_item_consultations`;
CREATE TABLE `yxshop_item_consultations` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `item_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '商品ID',
  `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
  `consult_type` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '咨询类型(1商品 2物流 3售后)',
  `content` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '咨询内容',
  `reply_content` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '回复内容',
  `replier_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '回复人ID',
  `is_anonymous` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否匿名(0否 1是)',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态(0待回复 1已回复)',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '应用ID',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除时间(0未删)',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_item_id`(`item_id`) USING BTREE,
  INDEX `idx_user_id`(`user_id`) USING BTREE,
  INDEX `idx_status`(`status`) USING BTREE,
  INDEX `idx_app_id`(`app_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '商品咨询表 [Enterprise]' ROW_FORMAT = DYNAMIC;

-- -----------------------------------------------------------
-- 4.10 赠品活动 [Enterprise]
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `yxshop_gift_activities`;
CREATE TABLE `yxshop_gift_activities` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `activity_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '活动名称',
  `activity_type` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '活动类型(1满赠 2买赠)',
  `threshold_amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '门槛金额',
  `start_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '开始时间',
  `end_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '结束时间',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态(0禁用 1启用)',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '应用ID',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除时间(0未删)',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_start_end`(`start_at`, `end_at`) USING BTREE,
  INDEX `idx_status`(`status`) USING BTREE,
  INDEX `idx_app_id`(`app_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '赠品活动表 [Enterprise]' ROW_FORMAT = DYNAMIC;

DROP TABLE IF EXISTS `yxshop_gift_activity_items`;
CREATE TABLE `yxshop_gift_activity_items` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `gift_activity_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '赠品活动ID',
  `item_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '商品ID',
  `spec_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '规格ID',
  `gift_quantity` int(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT '赠品数量',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '应用ID',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_gift_activity_id`(`gift_activity_id`) USING BTREE,
  INDEX `idx_item_id`(`item_id`) USING BTREE,
  INDEX `idx_app_id`(`app_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '赠品活动明细表 [Enterprise]' ROW_FORMAT = DYNAMIC;

-- -----------------------------------------------------------
-- 4.11 定时任务 [Enterprise]
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `yxshop_scheduled_tasks`;
CREATE TABLE `yxshop_scheduled_tasks` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `task_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '任务名称',
  `task_type` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '任务类型(command/closure/url)',
  `cron_expression` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'Cron表达式',
  `task_command` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '执行命令',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态(0禁用 1启用)',
  `last_run_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '上次执行时间',
  `last_run_status` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '上次执行状态(0失败 1成功)',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '应用ID',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除时间(0未删)',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_status`(`status`) USING BTREE,
  INDEX `idx_app_id`(`app_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '定时任务表 [Enterprise]' ROW_FORMAT = DYNAMIC;

DROP TABLE IF EXISTS `yxshop_scheduled_task_logs`;
CREATE TABLE `yxshop_scheduled_task_logs` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `task_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '任务ID',
  `started_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '开始时间',
  `finished_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '结束时间',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '执行状态(0失败 1成功)',
  `result` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '执行结果',
  `error_message` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '错误信息',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '应用ID',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_task_id`(`task_id`) USING BTREE,
  INDEX `idx_status`(`status`) USING BTREE,
  INDEX `idx_app_id`(`app_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '定时任务日志表 [Enterprise]' ROW_FORMAT = DYNAMIC;

-- -----------------------------------------------------------
-- 4.12 数据统计 [Enterprise]
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `yxshop_daily_statistics`;
CREATE TABLE `yxshop_daily_statistics` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `stat_date` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '统计日期(YYYYMMDD)',
  `new_users` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '新增用户数',
  `order_users` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '下单用户数',
  `order_count` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '订单数',
  `order_amount` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '订单金额',
  `pay_amount` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '实付金额',
  `refund_amount` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '退款金额',
  `page_views` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '页面浏览量',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '应用ID',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_app_date`(`app_id`, `stat_date`) USING BTREE,
  INDEX `idx_stat_date`(`stat_date`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '日统计表 [Enterprise]' ROW_FORMAT = DYNAMIC;

DROP TABLE IF EXISTS `yxshop_monthly_statistics`;
CREATE TABLE `yxshop_monthly_statistics` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `stat_month` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '统计月份(YYYYMM)',
  `new_users` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '新增用户数',
  `total_users` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '累计用户数',
  `order_count` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '订单数',
  `order_amount` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '订单金额',
  `pay_amount` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '实付金额',
  `refund_amount` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '退款金额',
  `avg_order_amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '客单价',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '应用ID',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_app_month`(`app_id`, `stat_month`) USING BTREE,
  INDEX `idx_stat_month`(`stat_month`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '月统计表 [Enterprise]' ROW_FORMAT = DYNAMIC;

DROP TABLE IF EXISTS `yxshop_item_statistics`;
CREATE TABLE `yxshop_item_statistics` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `item_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '商品ID',
  `stat_date` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '统计日期(YYYYMMDD)',
  `view_count` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '浏览量',
  `favorite_count` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '收藏量',
  `cart_count` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '加购量',
  `order_count` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '下单量',
  `pay_count` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '支付量',
  `pay_amount` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '支付金额',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '应用ID',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_item_date`(`item_id`, `stat_date`) USING BTREE,
  INDEX `idx_stat_date`(`stat_date`) USING BTREE,
  INDEX `idx_app_id`(`app_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '商品统计表 [Enterprise]' ROW_FORMAT = DYNAMIC;

-- -----------------------------------------------------------
-- 4.13 细粒度权限 [Enterprise]
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `yxshop_permissions`;
CREATE TABLE `yxshop_permissions` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `permission_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '权限名称',
  `permission_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '权限编码',
  `permission_type` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '权限类型(1菜单 2按钮 3数据)',
  `parent_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '父级ID',
  `menu_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联菜单ID',
  `sort` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序值',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态(0禁用 1启用)',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '应用ID',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除时间(0未删)',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_permission_code`(`permission_code`) USING BTREE,
  INDEX `idx_parent_id`(`parent_id`) USING BTREE,
  INDEX `idx_menu_id`(`menu_id`) USING BTREE,
  INDEX `idx_app_id`(`app_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '权限表 [Enterprise]' ROW_FORMAT = DYNAMIC;

-- -----------------------------------------------------------
-- 4.14 用户反馈 [Enterprise]
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `yxshop_user_feedbacks`;
CREATE TABLE `yxshop_user_feedbacks` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
  `feedback_type` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '反馈类型(1功能建议 2Bug反馈 3投诉 4其他)',
  `content` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '反馈内容',
  `contact` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '联系方式',
  `images` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '图片(JSON数组)',
  `reply_content` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '回复内容',
  `replier_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '回复人ID',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态(0待处理 1已回复 2已关闭)',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '应用ID',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除时间(0未删)',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_user_id`(`user_id`) USING BTREE,
  INDEX `idx_status`(`status`) USING BTREE,
  INDEX `idx_app_id`(`app_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '用户反馈表 [Enterprise]' ROW_FORMAT = DYNAMIC;

-- -----------------------------------------------------------
-- 4.15 友情链接 [Enterprise]
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `yxshop_friendly_links`;
CREATE TABLE `yxshop_friendly_links` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `link_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '链接名称',
  `link_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '链接地址',
  `link_logo` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '链接Logo',
  `sort` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序值',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态(0禁用 1启用)',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '应用ID',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除时间(0未删)',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_status`(`status`) USING BTREE,
  INDEX `idx_sort`(`sort`) USING BTREE,
  INDEX `idx_app_id`(`app_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '友情链接表 [Enterprise]' ROW_FORMAT = DYNAMIC;

-- -----------------------------------------------------------
-- 4.16 到货通知 [Enterprise]
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `yxshop_arrival_notifications`;
CREATE TABLE `yxshop_arrival_notifications` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
  `item_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '商品ID',
  `spec_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '规格ID',
  `notify_type` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '通知方式(1站内信 2短信 3邮件)',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态(0待通知 1已通知)',
  `notified_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '通知时间',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '应用ID',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除时间(0未删)',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_user_id`(`user_id`) USING BTREE,
  INDEX `idx_item_id`(`item_id`) USING BTREE,
  INDEX `idx_user_item_spec`(`user_id`, `item_id`, `spec_id`) USING BTREE,
  INDEX `idx_app_id`(`app_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '到货通知表 [Enterprise]' ROW_FORMAT = DYNAMIC;

-- -----------------------------------------------------------
-- 4.17 余额充值 [Enterprise]
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `yxshop_recharge_packages`;
CREATE TABLE `yxshop_recharge_packages` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `package_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '套餐名称',
  `recharge_amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '充值金额',
  `bonus_amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '赠送金额',
  `bonus_points` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '赠送积分',
  `sort` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序值',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态(0禁用 1启用)',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '应用ID',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除时间(0未删)',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_status`(`status`) USING BTREE,
  INDEX `idx_sort`(`sort`) USING BTREE,
  INDEX `idx_app_id`(`app_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '充值套餐表 [Enterprise]' ROW_FORMAT = DYNAMIC;

DROP TABLE IF EXISTS `yxshop_recharge_orders`;
CREATE TABLE `yxshop_recharge_orders` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `order_no` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '订单号',
  `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
  `package_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '充值套餐ID',
  `recharge_amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '充值金额',
  `bonus_amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '赠送金额',
  `bonus_points` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '赠送积分',
  `pay_amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '实付金额',
  `pay_method` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '支付方式',
  `pay_status` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '支付状态(0未支付 1已支付)',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '订单状态(0待支付 1已完成 2已取消)',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '应用ID',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_order_no`(`order_no`) USING BTREE,
  INDEX `idx_user_id`(`user_id`) USING BTREE,
  INDEX `idx_package_id`(`package_id`) USING BTREE,
  INDEX `idx_app_id`(`app_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '充值订单表 [Enterprise]' ROW_FORMAT = DYNAMIC;

-- -----------------------------------------------------------
-- 4.18 商品翻译 [Enterprise]
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `yxshop_item_translations`;
CREATE TABLE `yxshop_item_translations` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `item_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '商品ID',
  `lang_code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '语言代码(如zh-CN,en-US)',
  `field_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '字段名(name/summary/description等)',
  `field_value` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '翻译值',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '应用ID',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_item_lang_field`(`item_id`, `lang_code`, `field_name`) USING BTREE,
  INDEX `idx_lang_code`(`lang_code`) USING BTREE,
  INDEX `idx_app_id`(`app_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '商品翻译表 [Enterprise]' ROW_FORMAT = DYNAMIC;

-- -----------------------------------------------------------
-- 4.19 管理员API令牌 [Enterprise]
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `yxshop_admin_api_tokens`;
CREATE TABLE `yxshop_admin_api_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `admin_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '管理员ID',
  `token_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '令牌名称',
  `token_hash` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '令牌哈希',
  `abilities` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '*' COMMENT '权限范围(JSON)',
  `expires_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '过期时间',
  `last_used_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '最后使用时间',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态(0禁用 1启用)',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '应用ID',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_token_hash`(`token_hash`) USING BTREE,
  INDEX `idx_admin_id`(`admin_id`) USING BTREE,
  INDEX `idx_app_id`(`app_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '管理员API令牌表 [Enterprise]' ROW_FORMAT = DYNAMIC;

DROP TABLE IF EXISTS `yxshop_admin_api_token_logs`;
CREATE TABLE `yxshop_admin_api_token_logs` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `token_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '令牌ID',
  `request_path` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '请求路径',
  `request_method` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '请求方法',
  `ip_address` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'IP地址',
  `requested_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '请求时间',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '应用ID',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_token_id`(`token_id`) USING BTREE,
  INDEX `idx_requested_at`(`requested_at`) USING BTREE,
  INDEX `idx_app_id`(`app_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '管理员API令牌日志表 [Enterprise]' ROW_FORMAT = DYNAMIC;

-- -----------------------------------------------------------
-- 4.20 页面SEO [Community]
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `yxshop_page_seos`;
CREATE TABLE `yxshop_page_seos` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `page_key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '页面标识(home/category/item/about等)',
  `lang_code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '语言代码',
  `title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'SEO标题',
  `description` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'SEO描述',
  `keywords` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'SEO关键词',
  `og_tags` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT 'OpenGraph标签(JSON)',
  `structured_data` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '结构化数据(JSON-LD)',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '应用ID',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_page_lang`(`page_key`, `lang_code`) USING BTREE,
  INDEX `idx_app_id`(`app_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '页面SEO表 [Community]' ROW_FORMAT = DYNAMIC;


-- ============================================================
-- Phase 5: 索引与约束补全
-- ============================================================

-- -----------------------------------------------------------
-- Phase 5.1: app_id 索引补全
-- -----------------------------------------------------------
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_advertisements` ADD INDEX `idx_app_id`(`app_id`) USING BTREE;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_after_sales` ADD INDEX `idx_app_id`(`app_id`) USING BTREE;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_after_sales_details` ADD INDEX `idx_app_id`(`app_id`) USING BTREE;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_after_sales_images` ADD INDEX `idx_app_id`(`app_id`) USING BTREE;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_applies` ADD INDEX `idx_app_id`(`app_id`) USING BTREE;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_cards` ADD INDEX `idx_app_id`(`app_id`) USING BTREE;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_coupon_items` ADD INDEX `idx_app_id`(`app_id`) USING BTREE;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_delivery_rules` ADD INDEX `idx_app_id`(`app_id`) USING BTREE;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_discounts` ADD INDEX `idx_app_id`(`app_id`) USING BTREE;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_games` ADD INDEX `idx_app_id`(`app_id`) USING BTREE;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_item_images` ADD INDEX `idx_app_id`(`app_id`) USING BTREE;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_order_agents` ADD INDEX `idx_app_id`(`app_id`) USING BTREE;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_order_deliveries` ADD INDEX `idx_app_id`(`app_id`) USING BTREE;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_order_items` ADD INDEX `idx_app_id`(`app_id`) USING BTREE;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_prize_lists` ADD INDEX `idx_app_id`(`app_id`) USING BTREE;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_prizes` ADD INDEX `idx_app_id`(`app_id`) USING BTREE;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_profit_logs` ADD INDEX `idx_app_id`(`app_id`) USING BTREE;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_prom_items` ADD INDEX `idx_app_id`(`app_id`) USING BTREE;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_signs` ADD INDEX `idx_app_id`(`app_id`) USING BTREE;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_spec_items` ADD INDEX `idx_app_id`(`app_id`) USING BTREE;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_specs` ADD INDEX `idx_app_id`(`app_id`) USING BTREE;

-- -----------------------------------------------------------
-- Phase 5.2: 高频查询索引补全
-- -----------------------------------------------------------
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_orders` ADD INDEX `idx_delivery_status_time`(`delivery_status`, `created_at`) USING BTREE;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_orders` ADD INDEX `idx_receipt_status`(`receipt_status`) USING BTREE;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_items` ADD INDEX `idx_is_recommended`(`is_recommended`) USING BTREE;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_items` ADD INDEX `idx_is_hot`(`is_hot`) USING BTREE;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_items` ADD INDEX `idx_is_new`(`is_new`) USING BTREE;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_users` ADD INDEX `idx_source`(`source`) USING BTREE;


SET FOREIGN_KEY_CHECKS = 1;


-- ============================================================
-- 回滚 SQL（逆序执行）
-- ============================================================
/*
-- Phase 5 回滚
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_users` DROP INDEX `idx_source`;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_items` DROP INDEX `idx_is_new`;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_items` DROP INDEX `idx_is_hot`;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_items` DROP INDEX `idx_is_recommended`;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_orders` DROP INDEX `idx_receipt_status`;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_orders` DROP INDEX `idx_delivery_status_time`;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_specs` DROP INDEX `idx_app_id`;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_spec_items` DROP INDEX `idx_app_id`;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_signs` DROP INDEX `idx_app_id`;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_prom_items` DROP INDEX `idx_app_id`;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_profit_logs` DROP INDEX `idx_app_id`;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_prizes` DROP INDEX `idx_app_id`;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_prize_lists` DROP INDEX `idx_app_id`;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_order_items` DROP INDEX `idx_app_id`;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_order_deliveries` DROP INDEX `idx_app_id`;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_order_agents` DROP INDEX `idx_app_id`;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_item_images` DROP INDEX `idx_app_id`;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_games` DROP INDEX `idx_app_id`;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_discounts` DROP INDEX `idx_app_id`;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_delivery_rules` DROP INDEX `idx_app_id`;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_coupon_items` DROP INDEX `idx_app_id`;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_cards` DROP INDEX `idx_app_id`;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_applies` DROP INDEX `idx_app_id`;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_after_sales_images` DROP INDEX `idx_app_id`;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_after_sales_details` DROP INDEX `idx_app_id`;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_after_sales` DROP INDEX `idx_app_id`;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_advertisements` DROP INDEX `idx_app_id`;

-- Phase 4 回滚
DROP TABLE IF EXISTS `yxshop_page_seos`;
DROP TABLE IF EXISTS `yxshop_admin_api_token_logs`;
DROP TABLE IF EXISTS `yxshop_admin_api_tokens`;
DROP TABLE IF EXISTS `yxshop_item_translations`;
DROP TABLE IF EXISTS `yxshop_recharge_orders`;
DROP TABLE IF EXISTS `yxshop_recharge_packages`;
DROP TABLE IF EXISTS `yxshop_arrival_notifications`;
DROP TABLE IF EXISTS `yxshop_friendly_links`;
DROP TABLE IF EXISTS `yxshop_user_feedbacks`;
DROP TABLE IF EXISTS `yxshop_permissions`;
DROP TABLE IF EXISTS `yxshop_item_statistics`;
DROP TABLE IF EXISTS `yxshop_monthly_statistics`;
DROP TABLE IF EXISTS `yxshop_daily_statistics`;
DROP TABLE IF EXISTS `yxshop_scheduled_task_logs`;
DROP TABLE IF EXISTS `yxshop_scheduled_tasks`;
DROP TABLE IF EXISTS `yxshop_gift_activity_items`;
DROP TABLE IF EXISTS `yxshop_gift_activities`;
DROP TABLE IF EXISTS `yxshop_item_consultations`;
DROP TABLE IF EXISTS `yxshop_item_param_values`;
DROP TABLE IF EXISTS `yxshop_param_template_items`;
DROP TABLE IF EXISTS `yxshop_param_templates`;
DROP TABLE IF EXISTS `yxshop_user_oauths`;
DROP TABLE IF EXISTS `yxshop_group_buy_members`;
DROP TABLE IF EXISTS `yxshop_group_buys`;
DROP TABLE IF EXISTS `yxshop_seckill_items`;
DROP TABLE IF EXISTS `yxshop_seckill_sessions`;
DROP TABLE IF EXISTS `yxshop_item_audit_logs`;
DROP TABLE IF EXISTS `yxshop_invoice_records`;
DROP TABLE IF EXISTS `yxshop_invoice_headers`;
DROP TABLE IF EXISTS `yxshop_purchase_order_items`;
DROP TABLE IF EXISTS `yxshop_purchase_orders`;
DROP TABLE IF EXISTS `yxshop_stock_transfer_items`;
DROP TABLE IF EXISTS `yxshop_stock_transfers`;
DROP TABLE IF EXISTS `yxshop_warehouse_stocks`;
DROP TABLE IF EXISTS `yxshop_warehouses`;
DROP TABLE IF EXISTS `yxshop_supplier_settlements`;
DROP TABLE IF EXISTS `yxshop_merchant_applies`;
DROP TABLE IF EXISTS `yxshop_suppliers`;

-- Phase 3 回滚（需从备份恢复字段）
-- ALTER TABLE `yxshop_orders` ADD COLUMN `selected_date` varchar(50) NULL DEFAULT NULL;
-- ALTER TABLE `yxshop_orders` ADD COLUMN `car_no` varchar(50) NULL DEFAULT NULL;
-- ALTER TABLE `yxshop_orders` ADD COLUMN `refund_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '退款时间';
-- ALTER TABLE `yxshop_orders` ADD COLUMN `refund_amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '已退款金额(累计)';
-- ALTER TABLE `yxshop_orders` ADD COLUMN `refund_status` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '退款状态(10无退款 20退款中 30已退款)';
-- ALTER TABLE `yxshop_orders` ADD COLUMN `refund_desc` varchar(255) NULL DEFAULT NULL;
-- ALTER TABLE `yxshop_orders` ADD COLUMN `refund_no` varchar(30) NULL DEFAULT NULL;
-- ALTER TABLE `yxshop_orders` ADD COLUMN `prom_time` int(3) NOT NULL DEFAULT 0 COMMENT '拼团限时';
-- ALTER TABLE `yxshop_orders` ADD COLUMN `prom_statis` int(2) NOT NULL DEFAULT 0 COMMENT '拼团状态';
-- ALTER TABLE `yxshop_orders` ADD COLUMN `prom_num` int(2) NOT NULL DEFAULT 0 COMMENT '参团人数';
-- ALTER TABLE `yxshop_orders` ADD COLUMN `prom_type` int(2) NOT NULL DEFAULT 0 COMMENT '订单分类：0普通 1拼团 2秒杀 3抢购';
-- ALTER TABLE `yxshop_orders` ADD COLUMN `sub_type` int(7) NOT NULL DEFAULT 0 COMMENT '售后方式1为退款2为换货';
-- ALTER TABLE `yxshop_orders` ADD COLUMN `sub_status` int(7) NOT NULL DEFAULT 0 COMMENT '售后状态10为申请20为已处理';

-- Phase 2 回滚（需从备份恢复旧表）
-- yxshop_roles 和 yxshop_spec_item_prices 需从数据库备份恢复

-- Phase 1 回滚
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_discounts` MODIFY COLUMN `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '??????';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_coupon_items` MODIFY COLUMN `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '??????';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_comments` MODIFY COLUMN `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '??????';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_app_pages` MODIFY COLUMN `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '??????';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_games` CHANGE COLUMN `set_up` `setup` int(1) NOT NULL COMMENT '1为每日中奖次数,0为本轮活动中奖次数';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_games` CHANGE COLUMN `set_num` `setnum` int(5) NOT NULL COMMENT '中奖次数';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_after_sales` CHANGE COLUMN `reasons_text` `reasonsText` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '申请原因文本';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_cards` CHANGE COLUMN `nick_name` `nickName` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '持有人昵称';
RENAME TABLE `yxshop_user_money_logs` TO `yxshop_user_money_log`;
RENAME TABLE `yxshop_integral_logs` TO `yxshop_integral_log`;
RENAME TABLE `yxshop_cards` TO `yxshop_card`;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_item_stock_warnings` CHANGE COLUMN `updated_at` `update_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_item_stock_warnings` CHANGE COLUMN `created_at` `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_user_logs` CHANGE COLUMN `id` `log_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_user_logs` DROP PRIMARY KEY, ADD PRIMARY KEY (`log_id`);
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_payment_logs` CHANGE COLUMN `id` `log_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_payment_logs` DROP PRIMARY KEY, ADD PRIMARY KEY (`log_id`);
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_order_logs` CHANGE COLUMN `id` `log_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_order_logs` DROP PRIMARY KEY, ADD PRIMARY KEY (`log_id`);
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_item_attrs` CHANGE COLUMN `item_id` `goods_id` mediumint(8) UNSIGNED NOT NULL DEFAULT 0 COMMENT '商品id';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_coupon_items` CHANGE COLUMN `item_id` `goods_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '商品ID';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_order_items` CHANGE COLUMN `item_weight` `goods_weight` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '商品重量(g)';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_order_items` CHANGE COLUMN `item_no` `goods_no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '商品编码';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_order_items` CHANGE COLUMN `item_attr` `goods_attr` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '商品规格信息';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_order_items` CHANGE COLUMN `item_price` `goods_price` decimal(10,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '商品价格';
RENAME TABLE `yxshop_prom_items` TO `yxshop_prom_goods`;
RENAME TABLE `yxshop_coupon_items` TO `yxshop_coupon_goods`;
RENAME TABLE `yxshop_order_items` TO `yxshop_order_goods`;
*/

-- ---- 迁移: 2026_18_enterprise_menus.sql ----
-- 企业级菜单补充
-- 将新功能菜单插入 yxshop_admin_menus（APP_ID=10001）
-- 执行前请确认 yxshop_admin_menus 表已存在且当前最大 ID < 74

SET NAMES utf8mb4;
SET @APP_ID = 10001;
SET @NOW = UNIX_TIMESTAMP();

-- -----------------------------------------------------------
-- 74: 供应链管理（顶级菜单，插入到 物流配送(43) 之后，营销管理(50) 之前）
-- -----------------------------------------------------------
INSERT IGNORE INTO `yxshop_admin_menus` (`id`, `parent_id`, `name`, `model`, `url`, `icon`, `sort`, `is_show`, `permission`, `method`, `app_id`, `created_at`, `updated_at`) VALUES
(74, 0, '供应链管理', 'supply', NULL, 'icon-shop', 45, 1, NULL, 'GET', @APP_ID, @NOW, @NOW);

-- 供应链子菜单
INSERT IGNORE INTO `yxshop_admin_menus` (`id`, `parent_id`, `name`, `model`, `url`, `icon`, `sort`, `is_show`, `permission`, `method`, `app_id`, `created_at`, `updated_at`) VALUES
(75, 74, '供应商管理', 'supply', '/supply/supplier', 'icon-user-tie', 10, 1, 'supplier/index', 'GET', @APP_ID, @NOW, @NOW),
(76, 74, '仓库管理', 'supply', '/supply/warehouse', 'icon-warehouse', 20, 1, 'warehouse/index', 'GET', @APP_ID, @NOW, @NOW),
(77, 74, '采购订单', 'supply', '/supply/purchase-order', 'icon-shopping-cart', 30, 1, 'purchase-order/index', 'GET', @APP_ID, @NOW, @NOW),
(78, 74, '库存调拨', 'supply', '/supply/stock-transfer', 'icon-swap', 40, 1, 'stock-transfer/index', 'GET', @APP_ID, @NOW, @NOW),
(79, 74, '供应商结算', 'supply', '/supply/settlement', 'icon-yen', 50, 1, 'settlement/index', 'GET', @APP_ID, @NOW, @NOW),
(80, 74, '商户入驻', 'supply', '/supply/merchant-apply', 'icon-idcard', 60, 1, 'merchant-apply/index', 'GET', @APP_ID, @NOW, @NOW),
(81, 74, '发票管理', 'supply', '/supply/invoice', 'icon-file-text', 70, 1, 'invoice/index', 'GET', @APP_ID, @NOW, @NOW);

-- -----------------------------------------------------------
-- 82: 营销管理子菜单：秒杀/拼团/赠品
-- -----------------------------------------------------------
INSERT IGNORE INTO `yxshop_admin_menus` (`id`, `parent_id`, `name`, `model`, `url`, `icon`, `sort`, `is_show`, `permission`, `method`, `app_id`, `created_at`, `updated_at`) VALUES
(82, 20, '秒杀管理', 'marketing', '/marketing/seckill', 'icon-thunder', 15, 1, 'seckill/index', 'GET', @APP_ID, @NOW, @NOW),
(83, 20, '拼团管理', 'marketing', '/marketing/group-buy', 'icon-team', 25, 1, 'group-buy/index', 'GET', @APP_ID, @NOW, @NOW),
(84, 20, '赠品活动', 'marketing', '/marketing/gift-activity', 'icon-gift', 35, 1, 'gift-activity/index', 'GET', @APP_ID, @NOW, @NOW);

-- -----------------------------------------------------------
-- 其他独立菜单
-- -----------------------------------------------------------
INSERT IGNORE INTO `yxshop_admin_menus` (`id`, `parent_id`, `name`, `model`, `url`, `icon`, `sort`, `is_show`, `permission`, `method`, `app_id`, `created_at`, `updated_at`) VALUES
(85, 0, '商品审核', 'audit', NULL, 'icon-check-circle', 47, 1, NULL, 'GET', @APP_ID, @NOW, @NOW),
(86, 85, '商品审核', 'audit', '/audit/item-audit', 'icon-audit', 10, 1, 'item-audit/index', 'GET', @APP_ID, @NOW, @NOW);

-- 系统设置子菜单：定时任务/用户反馈/友情链接/充值套餐/页面SEO
INSERT IGNORE INTO `yxshop_admin_menus` (`id`, `parent_id`, `name`, `model`, `url`, `icon`, `sort`, `is_show`, `permission`, `method`, `app_id`, `created_at`, `updated_at`) VALUES
(87, 46, '定时任务', 'system', '/system/scheduled-task', 'icon-clock', 45, 1, 'scheduled-task/index', 'GET', @APP_ID, @NOW, @NOW),
(88, 46, '用户反馈', 'system', '/system/user-feedback', 'icon-message', 50, 1, 'user-feedback/index', 'GET', @APP_ID, @NOW, @NOW),
(89, 46, '友情链接', 'system', '/system/friendly-link', 'icon-link', 55, 1, 'friendly-link/index', 'GET', @APP_ID, @NOW, @NOW),
(90, 46, '充值套餐', 'system', '/system/recharge-package', 'icon-wallet', 60, 1, 'recharge-package/index', 'GET', @APP_ID, @NOW, @NOW),
(91, 46, '页面SEO', 'system', '/system/page-seo', 'icon-global', 65, 1, 'page-seo/index', 'GET', @APP_ID, @NOW, @NOW),
(92, 46, '权限配置', 'system', '/system/permission-config', 'icon-lock', 70, 1, 'permission-config/index', 'GET', @APP_ID, @NOW, @NOW);

-- -----------------------------------------------------------
-- 自动授权：给超级管理员角色(role_id=1)授予所有新菜单权限
-- -----------------------------------------------------------
INSERT IGNORE INTO `yxshop_admin_role_auths` (`role_id`, `auth_id`, `created_at`, `updated_at`)
SELECT 1, `id`, @NOW, @NOW FROM `yxshop_admin_menus`
WHERE `id` >= 74 AND `is_show` = 1
ON DUPLICATE KEY UPDATE `updated_at` = @NOW;

-- ---- 迁移: 2026_18_fix_schema_review.sql ----
-- =====================================================
-- yxshop 数据库结构增量修复脚本
-- 生成时间: 2026-06-26
-- 适用范围: 已有数据的 yxshop 数据库
-- 执行方式: mysql -u root -p yxshop < 2026_18_fix_schema_review.sql
-- =====================================================
-- 修复内容:
--   P0: 索引错位 BUG 修复 (5 处)
--   P1: 字段类型修正 (varchar->decimal, float->decimal, int->bigint)
--   P2: 冗余索引清理 (5 处)
--   P3: AUTO_INCREMENT 大值残留清理 (2 张 Snowflake 表)
--   P4: 表 COMMENT 加 ID 策略标识 (143 张)
--   P5: 删除冗余字段 + 自动备份
-- =====================================================
-- 注意事项:
--   1. 执行前请自行备份数据库
--   2. yxshop_payments 和 yxshop_settings 已在脚本内自动备份为 _backup_20260626
--   3. yxshop_payments 删除 amount/status 后,业务代码需同步改用 payment_amount/payment_status
--   4. yxshop_settings 删除 description 后,业务代码需同步改用 describe
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET UNIQUE_CHECKS = 0;


-- =====================================================
-- P0: 索引错位 BUG 修复
-- =====================================================

-- 1. yxshop_coupon_items: 索引名与字段错位
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_coupon_items` DROP INDEX `coupon`;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_coupon_items` DROP INDEX `coupon_id`;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_coupon_items` ADD INDEX `idx_coupon_id`(`coupon_id`);
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_coupon_items` ADD INDEX `idx_item_id`(`item_id`);

-- 2. yxshop_user_levels: 索引名/字段错位 + 字段注释错误
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_user_levels` DROP INDEX `order_id`;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_user_levels` ADD INDEX `idx_integral`(`integral`);
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_user_levels` MODIFY COLUMN `integral` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '升级所需积分';

-- 3. yxshop_upload_files: 唯一索引命名规范
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_upload_files` DROP INDEX `path_idx`;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_upload_files` ADD UNIQUE INDEX `uk_file_name`(`file_name`);

-- 4. yxshop_regions: 索引命名规范
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_regions` DROP INDEX `name,level`;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_regions` ADD INDEX `idx_name`(`name`);

-- 5. yxshop_admins: 删除与主键重复的索引
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_admins` DROP INDEX `id`;

-- =====================================================
-- P1: 字段类型修正
--   - varchar -> decimal (要求当前数据全是数字,否则 ALTER 会失败)
--   - float  -> decimal (无损)
--   - int    -> bigint (无损)
-- =====================================================

-- 1. yxshop_item_attrs: attr_price varchar(255) -> decimal(10,2)
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_item_attrs` MODIFY COLUMN `attr_price` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '属性价格';

-- 2. yxshop_items: commission_amount varchar(20) -> decimal(10,2)
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_items` MODIFY COLUMN `commission_amount` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '分销金额';

-- 3. yxshop_order_agents: proportion float(5,2) -> decimal(5,2)
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_order_agents` MODIFY COLUMN `proportion` decimal(5, 2) NOT NULL COMMENT '分成比例';

-- 4. yxshop_profit_logs: proportion float(5,2) -> decimal(5,2)
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_profit_logs` MODIFY COLUMN `proportion` decimal(5, 2) NOT NULL COMMENT '分成比例';

-- 5. yxshop_regions: lng/lat varchar(100) -> decimal(10,6)
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_regions` MODIFY COLUMN `lng` decimal(10, 6) NULL DEFAULT NULL COMMENT '经度';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_regions` MODIFY COLUMN `lat` decimal(10, 6) NULL DEFAULT NULL COMMENT '纬度';

-- 6. yxshop_order_deliveries: id int(7) -> bigint(20) UNSIGNED (数据量为 0)
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_order_deliveries` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

-- 7. yxshop_signs: id int(7) -> bigint(20) UNSIGNED (数据量为 0)
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_signs` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

-- 8. yxshop_regions: id int(11) -> bigint(20) UNSIGNED (字典表 3748 行,ALTER 较快)
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_regions` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID';

-- =====================================================
-- P2: 冗余索引清理 (单列索引被复合索引覆盖)
-- =====================================================

-- yxshop_orders: idx_pay_status 被 idx_pay_status_time 覆盖,idx_user_id 被 idx_app_user_status 覆盖
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_orders` DROP INDEX `idx_pay_status`;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_orders` DROP INDEX `idx_user_id`;

-- yxshop_order_items: item_id 与 idx_item_id 重复
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_order_items` DROP INDEX `item_id`;

-- yxshop_admin_auths: idx_admin_id 被 idx_admin_auth 第一列覆盖
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_admin_auths` DROP INDEX `idx_admin_id`;

-- yxshop_admin_role_auths: idx_role_id 被 idx_role_auth 第一列覆盖
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_admin_role_auths` DROP INDEX `idx_role_id`;

-- =====================================================
-- P3: 清理 Snowflake 表的 AUTO_INCREMENT 大值残留
--   这两张表的 Model 用 Snowflake 写入,AUTO_INCREMENT 起始值是
--   Navicat 导出时的快照,实际从未使用。改为 1 重置。
-- =====================================================

-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_item_spec_prices` AUTO_INCREMENT = 1;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_login_logs` AUTO_INCREMENT = 1;

-- =====================================================
-- P4: 表 COMMENT 加 ID 策略标识 (静态拼接,兼容 MySQL 5.7)
--   [Snowflake ID] = 业务代码生成雪花 ID,SQL AUTO_INCREMENT 实际无效
--   [Auto Increment ID] = MySQL 自增 (yxshop_order_logs / yxshop_item_spec_stock_logs)
-- 幂等性: 重复执行会在 COMMENT 末尾重复拼接后缀,导致 [Snowflake ID] [Snowflake ID]
--   因此脚本只可执行一次,如需重复执行请先清理 COMMENT
-- =====================================================

-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_admin_api_token_logs` COMMENT = '管理员API令牌日志表 [Enterprise] [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_admin_api_tokens` COMMENT = '管理员API令牌表 [Enterprise] [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_admin_auths` COMMENT = '管理员权限关联表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_admin_logs` COMMENT = '管理员操作日志表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_admin_menus` COMMENT = '后台菜单表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_admin_role_auths` COMMENT = '角色权限关联表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_admin_roles` COMMENT = '管理员角色表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_admins` COMMENT = '商家用户记录表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_after_sales` COMMENT = '订单评价记录表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_after_sales_details` COMMENT = '订单评价记录表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_after_sales_images` COMMENT = '评价图片表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_agent_capitals` COMMENT = '分销商资金明细表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_agent_withdraws` COMMENT = '分销商提现明细表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_agents` COMMENT = '代理商 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_app_helps` COMMENT = '微信小程序帮助 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_app_navbars` COMMENT = '微信小程序导航栏设置 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_app_pages` COMMENT = '微信小程序diy页面表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_app_prepay_ids` COMMENT = '小程序prepay_id记录 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_applies` COMMENT = '分销商资金明细表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_apps` COMMENT = '应用表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_arrival_notifications` COMMENT = '到货通知表 [Enterprise] [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_article_categories` COMMENT = '文章分类 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_articles` COMMENT = '文章 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_brands` COMMENT = '商品品牌表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_cards` COMMENT = '虚拟商品卡密表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_carts` COMMENT = '购物车表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_categories` COMMENT = '商品分类表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_comments` COMMENT = '订单评价记录表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_coupon_items` COMMENT = '优惠券指定商品记录表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_coupon_receive_logs` COMMENT = '优惠券领取流水 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_coupons` COMMENT = '优惠券记录表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_currencies` COMMENT = '货币表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_daily_statistics` COMMENT = '日统计表 [Enterprise] [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_data_change_logs` COMMENT = '数据变更日志表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_dealer_referees` COMMENT = '分销商资金明细表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_deliveries` COMMENT = '配送模板主表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_delivery_rules` COMMENT = '配送模板区域及运费表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_delivery_tracks` COMMENT = '物流轨迹表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_discounts` COMMENT = '优惠活动表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_email_logs` COMMENT = '邮件日志表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_expresses` COMMENT = '物流公司记录表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_file_logs` COMMENT = '文件操作日志表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_friendly_links` COMMENT = '友情链接表 [Enterprise] [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_gift_activities` COMMENT = '赠品活动表 [Enterprise] [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_gift_activity_items` COMMENT = '赠品活动明细表 [Enterprise] [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_group_buy_members` COMMENT = '拼团参与记录表 [Enterprise] [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_group_buys` COMMENT = '拼团活动表 [Enterprise] [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_idempotency_records` COMMENT = '幂等记录表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_integral_logs` COMMENT = '积分日志表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_invoice_headers` COMMENT = '发票抬头表 [Enterprise] [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_invoice_records` COMMENT = '发票开具记录表 [Enterprise] [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_item_attributes` COMMENT = '属性定义表（定义属性名称和类型） [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_item_attrs` COMMENT = '商品-属性值关联表（记录商品的属性值） [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_item_audit_logs` COMMENT = '商品审核日志表 [Enterprise] [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_item_consultations` COMMENT = '商品咨询表 [Enterprise] [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_item_favorites` COMMENT = '商品收藏表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_item_images` COMMENT = '商品图片表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_item_param_values` COMMENT = '商品参数值表 [Community] [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_item_searches` COMMENT = '商品搜索记录表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_item_spec_price_histories` COMMENT = '商品规格价格历史表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_item_spec_prices` COMMENT = '商品规格价格表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_item_spec_sales` COMMENT = '商品规格销量统计表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_item_statistics` COMMENT = '商品统计表 [Enterprise] [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_item_stock_warnings` COMMENT = '商品规格库存预警表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_item_tag_relations` COMMENT = '商品标签关联表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_item_tags` COMMENT = '商品标签表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_item_translations` COMMENT = '商品翻译表 [Enterprise] [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_item_types` COMMENT = '商品类型表（实物/虚拟/服务） [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_item_views` COMMENT = '商品浏览记录表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_items` COMMENT = '商品表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_languages` COMMENT = '语言表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_login_logs` COMMENT = '登录日志表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_merchant_applies` COMMENT = '商户入驻申请表 [Enterprise] [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_monthly_statistics` COMMENT = '月统计表 [Enterprise] [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_notification_blacklists` COMMENT = '通知黑名单表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_notification_configs` COMMENT = '通知配置表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_notification_queues` COMMENT = '通知队列表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_notification_scene_templates` COMMENT = '通知场景模板关联表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_notification_scenes` COMMENT = '通知场景配置表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_notification_sends` COMMENT = '通知发送记录表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_notification_statistics` COMMENT = '通知统计表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_notification_template_variables` COMMENT = '模板-变量关联表（记录模板使用了哪些变量） [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_notification_templates` COMMENT = '通知模板表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_notification_variables` COMMENT = '系统通知变量定义（如{{order_no}}、{{user_name}}） [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_notifications` COMMENT = '消息推送 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_order_addresses` COMMENT = '订单收货地址记录表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_order_agents` COMMENT = '分销商订单记录表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_order_items` COMMENT = '订单商品记录表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_orders` COMMENT = '订单记录表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_packages` COMMENT = '商品套餐表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_page_seos` COMMENT = '页面SEO表 [Community] [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_param_template_items` COMMENT = '参数模板项表 [Community] [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_param_templates` COMMENT = '商品参数模板表 [Community] [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_payment_logs` COMMENT = '支付日志表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_payments` COMMENT = '支付记录表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_permissions` COMMENT = '权限表 [Enterprise] [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_profit_logs` COMMENT = '分销商订单记录表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_prom_items` COMMENT = '商品级促销记录（特价/限时等） [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_prom_orders` COMMENT = '订单级促销记录（满减/折扣等） [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_promotions` COMMENT = '促销活动主表（定义活动规则） [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_purchase_order_items` COMMENT = '采购单明细表 [Enterprise] [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_purchase_orders` COMMENT = '采购单表 [Enterprise] [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_recharge_orders` COMMENT = '充值订单表 [Enterprise] [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_recharge_packages` COMMENT = '充值套餐表 [Enterprise] [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_refund_records` COMMENT = '退款记录表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_regions` COMMENT = '省市表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_scheduled_task_logs` COMMENT = '定时任务日志表 [Enterprise] [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_scheduled_tasks` COMMENT = '定时任务表 [Enterprise] [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_schema_migrations` COMMENT = '迁移版本记录 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_seckill_items` COMMENT = '秒杀商品表 [Enterprise] [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_seckill_sessions` COMMENT = '秒杀场次表 [Enterprise] [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_settings` COMMENT = '商城设置记录表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_settlements` COMMENT = '结算单表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_sms_logs` COMMENT = '短信日志表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_spec_items` COMMENT = '规格名称表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_specs` COMMENT = '规格表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_stock_transfer_items` COMMENT = '库存调拨明细表 [Enterprise] [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_stock_transfers` COMMENT = '库存调拨表 [Enterprise] [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_supplier_settlements` COMMENT = '商户结算表 [Enterprise] [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_suppliers` COMMENT = '供应商表 [Enterprise] [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_system_logs` COMMENT = '系统操作日志表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_upload_files` COMMENT = '文件库记录表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_upload_groups` COMMENT = '文件库分组记录表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_user_addresses` COMMENT = '用户收货地址表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_user_coupons` COMMENT = '用户优惠券记录表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_user_feedbacks` COMMENT = '用户反馈表 [Enterprise] [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_user_integral_accounts` COMMENT = '用户积分账户表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_user_levels` COMMENT = '等级表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_user_logs` COMMENT = '用户操作日志表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_user_money_logs` COMMENT = '用户余额变动日志表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_user_oauths` COMMENT = '用户第三方OAuth绑定表 [Enterprise] [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_users` COMMENT = '用户记录表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_warehouse_stocks` COMMENT = '仓库库存表 [Enterprise] [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_warehouses` COMMENT = '仓库表 [Enterprise] [Snowflake ID]';

-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_order_logs` COMMENT = '订单变化日志表 [Auto Increment ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_item_spec_stock_logs` COMMENT = '商品规格库存变动记录表 [Auto Increment ID]';


-- 补充:以下 7 张表原 SQL 无 COMMENT,直接赋完整 COMMENT
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_advertisements` COMMENT = '广告表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_games` COMMENT = '营销活动表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_order_deliveries` COMMENT = '订单发货记录表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_prize_lists` COMMENT = '中奖记录表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_prizes` COMMENT = '奖品表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_signs` COMMENT = '签到记录表 [Snowflake ID]';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_upload_types` COMMENT = '上传类型关联表 [Snowflake ID]';
-- =====================================================
-- P5: 冗余字段删除 (含自动备份)
-- =====================================================

-- P5.1: yxshop_payments 删除 amount/status(保留 payment_amount/payment_status)
DROP TABLE IF EXISTS `yxshop_payments_backup_20260626`;
CREATE TABLE `yxshop_payments_backup_20260626` AS SELECT * FROM `yxshop_payments`;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_payments` DROP COLUMN `amount`, DROP COLUMN `status`;

-- P5.2: yxshop_settings 删除 description(保留 describe)
DROP TABLE IF EXISTS `yxshop_settings_backup_20260626`;
CREATE TABLE `yxshop_settings_backup_20260626` AS SELECT * FROM `yxshop_settings`;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_settings` DROP COLUMN `description`;

-- P5.3: yxshop_login_logs 暂不删除冗余字段(login_message/fail_reason 等),
--       因为业务代码耦合度高,建议后续逐字段迁移

SET FOREIGN_KEY_CHECKS = 1;
SET UNIQUE_CHECKS = 1;

-- =====================================================
-- 脚本结束
-- 验证命令:
--   SHOW INDEX FROM yxshop_coupon_items;     -- 应看到 idx_coupon_id / idx_item_id
--   SHOW INDEX FROM yxshop_admins;           -- 不应再有 id 索引
--   DESC yxshop_payments;                    -- 不应再有 amount/status 字段
--   DESC yxshop_settings;                    -- 不应再有 description 字段
--   SELECT TABLE_NAME, TABLE_COMMENT FROM information_schema.TABLES
--     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME LIKE 'yxshop\_%'
--     ORDER BY TABLE_NAME;
-- =====================================================

-- ---- 迁移: 2026_19_drop_admin_auths_merge_app_helps.sql ----
-- =====================================================
-- yxshop 冗余表清理迁移脚本
-- 生成时间: 2026-06-27
-- 适用范围: 已有数据的 yxshop 数据库
-- 执行方式: mysql -u root -p yxshop < 2026_19_drop_admin_auths_merge_app_helps.sql
-- =====================================================
-- 清理内容:
--   1. 删除 yxshop_admin_auths (管理员权限关联表 - 已被 yxshop_admin_role_auths 取代)
--   2. 合并 yxshop_app_helps 数据到 yxshop_articles,然后删除 yxshop_app_helps
-- =====================================================
-- 注意事项:
--   1. 执行前请自行备份数据库(脚本内也会自动备份)
--   2. yxshop_admin_auths 删除后,业务代码仍可运行(实际权限逻辑走 yxshop_admin_role_auths)
--   3. yxshop_app_helps 删除后,需要将 app/model/AppHelp.php 的 $table 改为 yxshop_articles
--      (或迁移业务代码使用 Article 模型)
--   4. app/model/AdminAuth.php 可手动删除(脚本不处理 PHP 文件)
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET UNIQUE_CHECKS = 0;


-- =====================================================
-- 任务 1: 删除 yxshop_admin_auths
-- =====================================================
-- 原因:
--   - 该表的 Model (AdminAuth.php) 完全没有引用 (grep "AdminAuth::" 无结果)
--   - AdminAuthService::check() 直接 return true,权限校验没启用
--   - 实际权限逻辑由 yxshop_admin_role_auths + yxshop_admin_roles + yxshop_admins.role_id 三表完成
--   - 表结构与 yxshop_admin_role_auths 重复 (都是 admin_id/role_id + auth_id 的多对多关联)
-- =====================================================

-- 1.1 自动备份(全量数据)
DROP TABLE IF EXISTS `yxshop_admin_auths_backup_20260627`;
CREATE TABLE `yxshop_admin_auths_backup_20260627` AS SELECT * FROM `yxshop_admin_auths`;

-- 1.2 删除表
DROP TABLE IF EXISTS `yxshop_admin_auths`;


-- =====================================================
-- 任务 2: 合并 yxshop_app_helps 到 yxshop_articles
-- =====================================================
-- 字段映射:
--   app_helps.title     -> articles.title
--   app_helps.content   -> articles.content
--   app_helps.sort      -> articles.sort
--   app_helps.app_id    -> articles.app_id
--   app_helps.created_at -> articles.created_at
--   app_helps.updated_at -> articles.updated_at
-- 固定填充:
--   articles.author    = 'system' (迁移来源标识)
--   articles.status    = 1       (默认启用)
--   articles.category_id = 0     (未分类,后续可手动调整)
-- =====================================================

-- 2.1 自动备份
DROP TABLE IF EXISTS `yxshop_app_helps_backup_20260627`;
CREATE TABLE `yxshop_app_helps_backup_20260627` AS SELECT * FROM `yxshop_app_helps`;

-- 2.2 迁移数据到 articles
-- 注意: articles.id 是 bigint(20) UNSIGNED NOT NULL (雪花 ID)
--       如果业务代码依赖 app_helps.id 的具体值,迁移后 ID 会变化!
--       建议在迁移前先确认是否有外部引用 (API/URL/缓存)
INSERT IGNORE INTO `yxshop_articles` (
    `title`, `category_id`, `author`, `cover`, `description`,
    `content`, `sort`, `status`, `is_top`, `view_count`,
    `app_id`, `created_at`, `updated_at`, `deleted_at`
)
SELECT
    `title`, 0, 'system', '', '',
    `content`, `sort`, 1, 0, 0,
    `app_id`, `created_at`, `updated_at`, 0
FROM `yxshop_app_helps`;

-- 2.3 验证迁移(应该返回 app_helps 和迁移后的 articles 行数一致)
-- SELECT COUNT(*) AS helps_count FROM yxshop_app_helps;
-- SELECT COUNT(*) AS migrated_count FROM yxshop_articles WHERE author = 'system';

-- 2.4 删除 app_helps 表
DROP TABLE IF EXISTS `yxshop_app_helps`;


-- =====================================================
-- 后续业务代码建议修改 (不在 SQL 范围内,需要手动处理)
-- =====================================================
-- 1. 删除 app/model/AdminAuth.php
-- 2. 修改 app/model/AppHelp.php:
--      protected $table = 'yxshop_articles';
--    或迁移业务代码使用 Article 模型,通过 category_id 过滤
-- 3. 移除所有 AdminAuth 引用 (虽然 grep 结果显示无引用,但仍建议全局搜索)
-- 4. AppHelpService 类如果有,需要重写以适配 Article 模型


SET FOREIGN_KEY_CHECKS = 1;
SET UNIQUE_CHECKS = 1;

-- =====================================================
-- 脚本结束
-- 验证命令:
--   SHOW TABLES LIKE 'yxshop_admin_auths';    -- 应返回空
--   SHOW TABLES LIKE 'yxshop_app_helps';      -- 应返回空
--   SHOW TABLES LIKE 'yxshop_admin_auths_backup_20260627';  -- 应有备份
--   SHOW TABLES LIKE 'yxshop_app_helps_backup_20260627';    -- 应有备份
--   SELECT COUNT(*) FROM yxshop_articles WHERE author = 'system';  -- 应等于原 app_helps 行数
-- =====================================================

-- ---- 迁移: 2026_20_rename_goods_to_item.sql ----
-- =====================================================
-- yxshop 字段重命名: goods_*  -> item_*
-- 生成时间: 2026-06-27
-- 适用范围: 已有数据的 yxshop 数据库
-- 执行方式: mysql -u root -p yxshop < 2026_20_rename_goods_to_item.sql
-- =====================================================
-- 重命名清单:
--   P1. yxshop_item_attrs 索引名 `goods_id` -> `idx_item_id`
--   P2. yxshop_coupons.goods_ids -> item_ids (mediumtext)
--   P3. yxshop_after_sales.order_goods_id -> order_item_id
--   P4. yxshop_after_sales_details.order_goods_id -> order_item_id
--   P5. yxshop_comments.order_goods_id -> order_item_id
-- =====================================================
-- 注意事项:
--   1. 执行前请自行备份数据库
--   2. order_goods_id 重命名后,业务代码无需修改 (grep 验证 0 引用)
--   3. goods_ids -> item_ids 需要同步修改 Coupon Model 的 $fillable
--   4. goods_id (索引名) 重命名后无需修改代码
--   5. CHANGE COLUMN 会锁表(短暂),建议在低峰期执行
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET UNIQUE_CHECKS = 0;


-- =====================================================
-- P1: yxshop_item_attrs 索引重命名
-- =====================================================
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_item_attrs` DROP INDEX `goods_id`;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_item_attrs` ADD INDEX `idx_item_id`(`item_id`);


-- =====================================================
-- P2: yxshop_coupons.goods_ids -> item_ids
-- =====================================================
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_coupons` CHANGE COLUMN `goods_ids` `item_ids` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL;


-- =====================================================
-- P3-P5: order_goods_id -> order_item_id (3 张表)
-- =====================================================

-- yxshop_after_sales
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_after_sales` CHANGE COLUMN `order_goods_id` `order_item_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0;

-- yxshop_after_sales_details
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_after_sales_details` CHANGE COLUMN `order_goods_id` `order_item_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0;

-- yxshop_comments
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_comments` CHANGE COLUMN `order_goods_id` `order_item_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0;


SET FOREIGN_KEY_CHECKS = 1;
SET UNIQUE_CHECKS = 1;

-- =====================================================
-- 脚本结束
-- 验证命令:
--   SHOW INDEX FROM yxshop_item_attrs WHERE Key_name = 'idx_item_id';   -- 应有结果
--   SHOW INDEX FROM yxshop_item_attrs WHERE Key_name = 'goods_id';      -- 应为空
--   DESC yxshop_coupons;                                                -- 不应有 goods_ids,应有 item_ids
--   DESC yxshop_after_sales;                                             -- 不应有 order_goods_id,应有 order_item_id
--   DESC yxshop_after_sales_details;                                    -- 同上
--   DESC yxshop_comments;                                               -- 同上
-- =====================================================

-- ---- 迁移: 2026_21_rename_remaining_goods_fields.sql ----
-- =====================================================
-- yxshop 剩余 goods_* 字段统一改名为 item_*
-- 生成时间: 2026-06-27
-- 适用范围: 已有数据的 yxshop 数据库
-- 执行方式: mysql -u root -p yxshop < 2026_21_rename_remaining_goods_fields.sql
-- =====================================================
-- 改动清单:
--   1. yxshop_after_sales.goods_status -> item_status
--   2. yxshop_coupons.goods_limit_type -> item_limit_type
--   3. yxshop_items.type_id COMMENT: goods_type -> item_types
--   4. yxshop_items.spec_type COMMENT: goods_type -> item_types
-- =====================================================
-- 注意事项:
--   1. 执行前请自行备份数据库
--   2. 字段重命名后,AfterSales.php 和 Coupon.php 的 $fillable/$casts 需同步修改
--   3. CHANGE COLUMN 会锁表,建议在低峰期执行
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET UNIQUE_CHECKS = 0;


-- =====================================================
-- 1. yxshop_after_sales.goods_status -> item_status
-- =====================================================
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_after_sales` CHANGE COLUMN `goods_status` `item_status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '货物状态';


-- =====================================================
-- 2. yxshop_coupons.goods_limit_type -> item_limit_type
-- =====================================================
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_coupons` CHANGE COLUMN `goods_limit_type` `item_limit_type` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '限制商品类型(10全部 20指定分类 30指定品牌)';


-- =====================================================
-- 3. yxshop_items.type_id COMMENT 修正
--    原注释: '商品所属类型id，取值表goods_type的cat_id'
--    改后:   '商品所属类型id，取值表yxshop_item_types'
-- =====================================================
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_items` MODIFY COLUMN `type_id` smallint(5) UNSIGNED NOT NULL DEFAULT 0 COMMENT '商品所属类型id，取值表yxshop_item_types';


-- =====================================================
-- 4. yxshop_items.spec_type COMMENT 修正
--    原注释: '商品规格类型，取值表goods_type的cat_id'
--    改后:   '商品规格类型，取值表yxshop_item_types'
-- =====================================================
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_items` MODIFY COLUMN `spec_type` smallint(5) NULL DEFAULT 0 COMMENT '商品规格类型，取值表yxshop_item_types';


SET FOREIGN_KEY_CHECKS = 1;
SET UNIQUE_CHECKS = 1;

-- =====================================================
-- 脚本结束
-- 验证命令:
--   DESC yxshop_after_sales;      -- 应有 item_status,不应有 goods_status
--   DESC yxshop_coupons;          -- 应有 item_limit_type,不应有 goods_limit_type
--   SHOW FULL COLUMNS FROM yxshop_items WHERE Field IN ('type_id', 'spec_type');
--                                 -- COMMENT 应提到 yxshop_item_types,不应有 goods_type
-- =====================================================

-- ---- 迁移: 2026_22_supply_chain_enterprise.sql ----
-- ============================================================
-- 供应链企业级改造：操作日志表 + 乐观锁 + 软删除
-- ============================================================
SET NAMES utf8mb4;
SET @NOW = UNIX_TIMESTAMP();

-- -----------------------------------------------------------
-- 1. yxshop_purchase_order_logs  采购单操作日志
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `yxshop_purchase_order_logs` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '日志ID',
  `order_id`    BIGINT UNSIGNED NOT NULL COMMENT '采购单ID',
  `action`      VARCHAR(32) NOT NULL COMMENT '动作：create/approve/reject/receive/cancel/update/delete',
  `before_status` TINYINT UNSIGNED DEFAULT NULL COMMENT '操作前状态',
  `after_status`  TINYINT UNSIGNED DEFAULT NULL COMMENT '操作后状态',
  `actor_id`    BIGINT UNSIGNED DEFAULT NULL COMMENT '操作人ID（admin）',
  `actor_name`  VARCHAR(64) DEFAULT NULL COMMENT '操作人姓名',
  `remark`      VARCHAR(512) DEFAULT NULL COMMENT '备注/拒绝原因',
  `extra`       JSON DEFAULT NULL COMMENT '扩展数据（JSON）',
  `created_at`  INT UNSIGNED NOT NULL COMMENT '操作时间（程序层写入）',
  PRIMARY KEY (`id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_action` (`action`),
  KEY `idx_actor_id` (`actor_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='采购单操作日志';

-- -----------------------------------------------------------
-- 2. yxshop_stock_transfer_logs  库存调拨操作日志
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `yxshop_stock_transfer_logs` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '日志ID',
  `transfer_id` BIGINT UNSIGNED NOT NULL COMMENT '调拨单ID',
  `action`      VARCHAR(32) NOT NULL COMMENT '动作：create/approve/reject/complete/update/delete',
  `before_status` TINYINT UNSIGNED DEFAULT NULL COMMENT '操作前状态',
  `after_status`  TINYINT UNSIGNED DEFAULT NULL COMMENT '操作后状态',
  `actor_id`    BIGINT UNSIGNED DEFAULT NULL COMMENT '操作人ID',
  `actor_name`  VARCHAR(64) DEFAULT NULL COMMENT '操作人姓名',
  `remark`      VARCHAR(512) DEFAULT NULL COMMENT '备注/拒绝原因',
  `extra`       JSON DEFAULT NULL COMMENT '扩展数据',
  `created_at`  INT UNSIGNED NOT NULL COMMENT '操作时间（程序层写入）',
  PRIMARY KEY (`id`),
  KEY `idx_transfer_id` (`transfer_id`),
  KEY `idx_action` (`action`),
  KEY `idx_actor_id` (`actor_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='库存调拨操作日志';

-- -----------------------------------------------------------
-- 3. yxshop_merchant_apply_logs  商户入驻申请操作日志
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `yxshop_merchant_apply_logs` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '日志ID',
  `apply_id`    BIGINT UNSIGNED NOT NULL COMMENT '申请ID',
  `action`      VARCHAR(32) NOT NULL COMMENT '动作：create/approve/reject/update/delete',
  `before_status` TINYINT UNSIGNED DEFAULT NULL COMMENT '操作前状态',
  `after_status`  TINYINT UNSIGNED DEFAULT NULL COMMENT '操作后状态',
  `actor_id`    BIGINT UNSIGNED DEFAULT NULL COMMENT '操作人ID',
  `actor_name`  VARCHAR(64) DEFAULT NULL COMMENT '操作人姓名',
  `remark`      VARCHAR(512) DEFAULT NULL COMMENT '备注/拒绝原因',
  `extra`       JSON DEFAULT NULL COMMENT '扩展数据',
  `created_at`  INT UNSIGNED NOT NULL COMMENT '操作时间（程序层写入）',
  PRIMARY KEY (`id`),
  KEY `idx_apply_id` (`apply_id`),
  KEY `idx_action` (`action`),
  KEY `idx_actor_id` (`actor_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='商户入驻申请操作日志';

-- -----------------------------------------------------------
-- 4. yxshop_supplier_settlement_logs  供应商结算操作日志
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `yxshop_supplier_settlement_logs` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '日志ID',
  `settlement_id` BIGINT UNSIGNED NOT NULL COMMENT '结算单ID',
  `action`      VARCHAR(32) NOT NULL COMMENT '动作：create/confirm/reject/pay/cancel/update/delete',
  `before_status` TINYINT UNSIGNED DEFAULT NULL COMMENT '操作前状态',
  `after_status`  TINYINT UNSIGNED DEFAULT NULL COMMENT '操作后状态',
  `actor_id`    BIGINT UNSIGNED DEFAULT NULL COMMENT '操作人ID',
  `actor_name`  VARCHAR(64) DEFAULT NULL COMMENT '操作人姓名',
  `remark`      VARCHAR(512) DEFAULT NULL COMMENT '备注/拒绝原因',
  `extra`       JSON DEFAULT NULL COMMENT '扩展数据',
  `created_at`  INT UNSIGNED NOT NULL COMMENT '操作时间（程序层写入）',
  PRIMARY KEY (`id`),
  KEY `idx_settlement_id` (`settlement_id`),
  KEY `idx_action` (`action`),
  KEY `idx_actor_id` (`actor_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='供应商结算操作日志';

-- -----------------------------------------------------------
-- 5. 给主表加 version 乐观锁 + deleted_at 软删除
-- -----------------------------------------------------------
-- ---- 迁移: 2026_23_after_sales_enterprise.sql ----
-- ============================================================
-- 售后工单企业级改造：操作日志表 + 乐观锁 + 软删除
-- ============================================================
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_supplier_settlements`
-- [DISABLED-REDUNDANT-MIGRATION]   ADD COLUMN `version` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '乐观锁版本号',
-- [DISABLED-REDUNDANT-MIGRATION]   ADD COLUMN `deleted_at` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除标记',
-- [DISABLED-REDUNDANT-MIGRATION]   ADD INDEX `idx_status` (`status`),
-- [DISABLED-REDUNDANT-MIGRATION]   ADD INDEX `idx_deleted_at` (`deleted_at`);

-- -----------------------------------------------------------
-- 1. yxshop_after_sales_logs 售后工单操作日志
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `yxshop_after_sales_logs` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '日志ID',
  `after_sales_id`  BIGINT UNSIGNED NOT NULL COMMENT '售后单ID',
  `action`          VARCHAR(32) NOT NULL COMMENT '动作：apply/approve/reject/return_ship/receive/complete/cancel',
  `before_status`   TINYINT UNSIGNED DEFAULT NULL COMMENT '操作前状态',
  `after_status`    TINYINT UNSIGNED DEFAULT NULL COMMENT '操作后状态',
  `actor_id`        BIGINT UNSIGNED DEFAULT NULL COMMENT '操作人ID（admin）',
  `actor_name`      VARCHAR(64) DEFAULT NULL COMMENT '操作人姓名',
  `remark`          VARCHAR(512) DEFAULT NULL COMMENT '备注/拒绝原因',
  `extra`           JSON DEFAULT NULL COMMENT '扩展数据（退款金额、快递信息等）',
  `created_at`      INT UNSIGNED NOT NULL COMMENT '操作时间（程序层写入）',
  PRIMARY KEY (`id`),
  KEY `idx_after_sales_id` (`after_sales_id`),
  KEY `idx_action` (`action`),
  KEY `idx_actor_id` (`actor_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='售后工单操作日志';

-- -----------------------------------------------------------
-- 2. 给 yxshop_after_sales 加 version + deleted_at
-- -----------------------------------------------------------
-- -----------------------------------------------------------
-- 3. yxshop_refund_records 退款记录表（财务闭环）
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `yxshop_refund_records` (
  `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '记录ID',
  `after_sales_id`    BIGINT UNSIGNED NOT NULL COMMENT '关联售后单ID',
  `order_id`          BIGINT UNSIGNED NOT NULL COMMENT '关联订单ID',
  `user_id`           BIGINT UNSIGNED NOT NULL COMMENT '退款用户ID',
  `refund_amount`     DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '退款金额',
  `refund_type`       TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '退款方式：1=原路退回 2=余额退回 3=银行卡',
  `refund_status`     TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '退款状态：0=待处理 1=处理中 2=已完成 3=失败',
  `transaction_id`    VARCHAR(64) DEFAULT NULL COMMENT '第三方交易流水号',
  `operator_id`       BIGINT UNSIGNED DEFAULT NULL COMMENT '操作人ID',
  `operator_name`     VARCHAR(64) DEFAULT NULL COMMENT '操作人姓名',
  `remark`            VARCHAR(512) DEFAULT NULL COMMENT '备注',
  `processed_at`      INT UNSIGNED DEFAULT NULL COMMENT '处理完成时间',
  `app_id`            BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '所属应用',
  `created_at`        INT UNSIGNED NOT NULL COMMENT '创建时间（程序层写入）',
  `updated_at`        INT UNSIGNED NOT NULL COMMENT '更新时间（程序层写入）',
  PRIMARY KEY (`id`),
  KEY `idx_after_sales_id` (`after_sales_id`),
  KEY `idx_order_id`       (`order_id`),
  KEY `idx_user_id`        (`user_id`),
  KEY `idx_refund_status`  (`refund_status`)
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_after_sales`
-- [DISABLED-REDUNDANT-MIGRATION]   ADD COLUMN `version`        INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '乐观锁版本号',
-- [DISABLED-REDUNDANT-MIGRATION]   ADD COLUMN `deleted_at`     INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除标记（0=未删，时间戳=已删）',
-- [DISABLED-REDUNDANT-MIGRATION]   ADD COLUMN `process_deadline` INT UNSIGNED DEFAULT NULL COMMENT '处理截止时间（SLA，Unix时间戳）',
-- [DISABLED-REDUNDANT-MIGRATION]   ADD INDEX `idx_status`       (`status`),
-- [DISABLED-REDUNDANT-MIGRATION]   ADD INDEX `idx_deleted_at`    (`deleted_at`);

-- ---- 迁移: 2026_24_article_enterprise.sql ----
-- ============================================================
-- 内容管理企业级改造：版本历史 + 定时发布 + 软删除
-- ============================================================
SET NAMES utf8mb4;

-- -----------------------------------------------------------
-- 1. yxshop_article_versions 文章版本历史
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `yxshop_article_versions` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '版本ID',
  `article_id`   BIGINT UNSIGNED NOT NULL COMMENT '文章ID',
  `version`     INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '版本号',
  `title`       VARCHAR(255) NOT NULL COMMENT '标题（快照）',
  `subtitle`    VARCHAR(255) DEFAULT NULL COMMENT '副标题（快照）',
  `content`     LONGTEXT NOT NULL COMMENT '内容（快照）',
  `excerpt`     TEXT DEFAULT NULL COMMENT '摘要（快照）',
  `cover_image` VARCHAR(512) DEFAULT NULL COMMENT '封面图（快照）',
  `category_id`  BIGINT UNSIGNED DEFAULT NULL COMMENT '分类ID（快照）',
  `author`      VARCHAR(64) DEFAULT NULL COMMENT '作者（快照）',
  `modifier_id` BIGINT UNSIGNED DEFAULT NULL COMMENT '修改人ID',
  `modifier_name` VARCHAR(64) DEFAULT NULL COMMENT '修改人姓名',
  `change_summary` VARCHAR(255) DEFAULT NULL COMMENT '变更摘要',
  `created_at`  INT UNSIGNED NOT NULL COMMENT '版本创建时间（程序层写入）',
  PRIMARY KEY (`id`),
  KEY `idx_article_id` (`article_id`),
  KEY `idx_version` (`article_id`, `version`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='文章版本历史';

-- -----------------------------------------------------------
-- 2. yxshop_article_logs 文章操作日志
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `yxshop_article_logs` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '日志ID',
  `article_id`   BIGINT UNSIGNED NOT NULL COMMENT '文章ID',
  `action`       VARCHAR(32) NOT NULL COMMENT '动作：create/update/publish/unpublish/delete',
  `before_status` TINYINT UNSIGNED DEFAULT NULL COMMENT '操作前状态',
  `after_status`  TINYINT UNSIGNED DEFAULT NULL COMMENT '操作后状态',
  `actor_id`     BIGINT UNSIGNED DEFAULT NULL COMMENT '操作人ID',
  `actor_name`   VARCHAR(64) DEFAULT NULL COMMENT '操作人姓名',
  `remark`       VARCHAR(512) DEFAULT NULL COMMENT '备注',
  `created_at`   INT UNSIGNED NOT NULL COMMENT '操作时间（程序层写入）',
  PRIMARY KEY (`id`),
  KEY `idx_article_id` (`article_id`),
  KEY `idx_action`     (`action`),
  KEY `idx_actor_id`   (`actor_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='文章操作日志';

-- -----------------------------------------------------------
-- 3. 给 yxshop_articles 加字段
-- -----------------------------------------------------------
-- 软删除支持：确保 deleted_at 全为 0
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_articles`
-- [DISABLED-REDUNDANT-MIGRATION]   ADD COLUMN `version`          INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '乐观锁版本号',
-- [DISABLED-REDUNDANT-MIGRATION]   ADD COLUMN `deleted_at`       INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除标记（0=未删）',
-- [DISABLED-REDUNDANT-MIGRATION]   ADD COLUMN `published_at`     INT UNSIGNED DEFAULT NULL COMMENT '发布时间（Unix戳）',
-- [DISABLED-REDUNDANT-MIGRATION]   ADD COLUMN `scheduled_at`     INT UNSIGNED DEFAULT NULL COMMENT '定时发布时间（Unix戳）',
-- [DISABLED-REDUNDANT-MIGRATION]   ADD COLUMN `change_summary`   VARCHAR(255) DEFAULT NULL COMMENT '最近一次变更摘要',
-- [DISABLED-REDUNDANT-MIGRATION]   ADD COLUMN `modifier_id`      BIGINT UNSIGNED DEFAULT NULL COMMENT '最后修改人ID',
-- [DISABLED-REDUNDANT-MIGRATION]   ADD COLUMN `modifier_name`    VARCHAR(64) DEFAULT NULL COMMENT '最后修改人姓名',
-- [DISABLED-REDUNDANT-MIGRATION]   ADD INDEX `idx_deleted_at`    (`deleted_at`),
-- [DISABLED-REDUNDANT-MIGRATION]   ADD INDEX `idx_scheduled_at`  (`scheduled_at`),
-- [DISABLED-REDUNDANT-MIGRATION]   ADD INDEX `idx_published_at`  (`published_at`);

-- ---- 迁移: 2026_25_file_enterprise.sql ----
-- ============================================================
-- 文件管理企业级改造：存储配额 + 下载审计
-- ============================================================
SET NAMES utf8mb4;

-- -----------------------------------------------------------
-- 1. yxshop_app_storage_quotas 应用存储配额表
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `yxshop_app_storage_quotas` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `app_id`       BIGINT UNSIGNED NOT NULL COMMENT '应用ID',
  `quota_bytes`  BIGINT UNSIGNED NOT NULL DEFAULT 10737418240 COMMENT '配额上限（字节），默认10GB',
  `warn_percent` TINYINT UNSIGNED NOT NULL DEFAULT 80 COMMENT '预警百分比',
  `used_bytes`   BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '已使用字节数',
  `operator_id`  BIGINT UNSIGNED DEFAULT NULL COMMENT '最后修改人ID',
  `operator_name` VARCHAR(64) DEFAULT NULL COMMENT '最后修改人姓名',
  `created_at`   INT UNSIGNED NOT NULL COMMENT '创建时间（程序层写入）',
  `updated_at`   INT UNSIGNED NOT NULL COMMENT '更新时间（程序层写入）',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_app_id` (`app_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='应用存储配额表';

-- -----------------------------------------------------------
-- 2. yxshop_file_download_logs 文件下载日志
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `yxshop_file_download_logs` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '日志ID',
  `file_id`      BIGINT UNSIGNED NOT NULL COMMENT '文件ID',
  `file_name`    VARCHAR(255) NOT NULL COMMENT '文件名',
  `file_size`   BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '文件大小（字节）',
  `user_id`     BIGINT UNSIGNED DEFAULT NULL COMMENT '下载人ID（admin）',
  `user_name`   VARCHAR(64) DEFAULT NULL COMMENT '下载人姓名',
  `ip`          VARCHAR(45) DEFAULT NULL COMMENT 'IP地址',
  `user_agent`  VARCHAR(512) DEFAULT NULL COMMENT 'User-Agent',
  `created_at`  INT UNSIGNED NOT NULL COMMENT '下载时间（程序层写入）',
  PRIMARY KEY (`id`),
  KEY `idx_file_id`    (`file_id`),
  KEY `idx_user_id`   (`user_id`),
  KEY `idx_created_at`(`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='文件下载日志';

-- -----------------------------------------------------------
-- 3. 给 yxshop_upload_files 加字段
-- -----------------------------------------------------------
-- -----------------------------------------------------------
-- 4. 初始化默认配额（所有 app 默认 10GB）
-- -----------------------------------------------------------
INSERT IGNORE INTO `yxshop_app_storage_quotas` (`app_id`, `quota_bytes`, `warn_percent`, `used_bytes`, `created_at`, `updated_at`)
SELECT `id`, 10737418240, 80, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `yxshop_apps`
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_upload_files`
-- [DISABLED-REDUNDANT-MIGRATION]   ADD COLUMN `audit_status`  TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '审核状态：0=待审核 1=通过 2=违规',
-- [DISABLED-REDUNDANT-MIGRATION]   ADD COLUMN `is_sensitive`  TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '敏感标记：0=正常 1=敏感',
-- [DISABLED-REDUNDANT-MIGRATION]   ADD COLUMN `audit_remark`  VARCHAR(255) DEFAULT NULL COMMENT '审核备注',
-- [DISABLED-REDUNDANT-MIGRATION]   ADD COLUMN `auditor_id`    BIGINT UNSIGNED DEFAULT NULL COMMENT '审核人ID',
-- [DISABLED-REDUNDANT-MIGRATION]   ADD COLUMN `audited_at`    INT UNSIGNED DEFAULT NULL COMMENT '审核时间',
-- [DISABLED-REDUNDANT-MIGRATION]   ADD COLUMN `download_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '下载次数',
-- [DISABLED-REDUNDANT-MIGRATION]   ADD INDEX `idx_audit_status`  (`audit_status`),
-- [DISABLED-REDUNDANT-MIGRATION]   ADD INDEX `idx_is_sensitive`  (`is_sensitive`);

-- ---- 迁移: 2026_26_coupon_finance.sql ----
-- ============================================================
-- 优惠券企业级改造：核销日志表 + 领取风控
-- ============================================================
SET NAMES utf8mb4;

-- -----------------------------------------------------------
-- 1. yxshop_coupon_use_logs 优惠券核销/领取日志
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `yxshop_coupon_use_logs` (
  `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '日志ID',
  `user_coupon_id` BIGINT UNSIGNED NOT NULL COMMENT '用户优惠券ID',
  `coupon_id`     BIGINT UNSIGNED NOT NULL COMMENT '优惠券ID',
  `user_id`       BIGINT UNSIGNED NOT NULL COMMENT '用户ID',
  `action`        VARCHAR(20) NOT NULL COMMENT '动作：receive/use/expire/cancel',
  `order_id`      BIGINT UNSIGNED DEFAULT NULL COMMENT '关联订单ID（核销时）',
  `discount_amount` DECIMAL(10,2) DEFAULT NULL COMMENT '实际优惠金额（核销时）',
  `ip`            VARCHAR(45) DEFAULT NULL COMMENT '操作IP',
  `device`        VARCHAR(64) DEFAULT NULL COMMENT '设备信息',
  `remark`        VARCHAR(255) DEFAULT NULL COMMENT '备注',
  `operator_id`   BIGINT UNSIGNED DEFAULT NULL COMMENT '操作人ID（admin）',
  `operator_name` VARCHAR(64) DEFAULT NULL COMMENT '操作人姓名',
  `created_at`    INT UNSIGNED NOT NULL COMMENT '操作时间（程序层写入）',
  PRIMARY KEY (`id`),
  KEY `idx_user_coupon_id` (`user_coupon_id`),
  KEY `idx_user_id`      (`user_id`),
  KEY `idx_coupon_id`   (`coupon_id`),
  KEY `idx_order_id`    (`order_id`),
  KEY `idx_action`      (`action`),
  KEY `idx_created_at`  (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='优惠券核销/领取日志';

-- -----------------------------------------------------------
-- 2. 给 yxshop_coupons 加 user_limit_count 每人最大领取次数
-- -----------------------------------------------------------
-- -----------------------------------------------------------
-- 3. 给 yxshop_user_coupons 加 version 乐观锁
-- -----------------------------------------------------------
-- -----------------------------------------------------------
-- 4. yxshop_coupon_issues 优惠券发放记录（用于统计）
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `yxshop_coupon_issues` (
  `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `coupon_id`     BIGINT UNSIGNED NOT NULL COMMENT '优惠券ID',
  `issue_type`    VARCHAR(20) NOT NULL COMMENT '发放方式：auto/manual/code/batch',
  `quantity`     INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '发放数量',
  `used_quantity` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '使用数量',
  `operator_id`   BIGINT UNSIGNED DEFAULT NULL COMMENT '操作人ID',
  `operator_name` VARCHAR(64) DEFAULT NULL COMMENT '操作人姓名',
  `remark`        VARCHAR(255) DEFAULT NULL COMMENT '备注',
  `app_id`        BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '所属应用',
  `created_at`    INT UNSIGNED NOT NULL COMMENT '发放时间（程序层写入）',
  PRIMARY KEY (`id`),
  KEY `idx_coupon_id`  (`coupon_id`),
  KEY `idx_app_id`    (`app_id`)
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_user_coupons`
-- [DISABLED-REDUNDANT-MIGRATION]   ADD COLUMN `version` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '乐观锁版本号';

-- ---- 迁移: 2026_27_user_risk_tag.sql ----
-- ============================================================
-- 用户体系企业级改造：标签体系 + 风险标记 + 登录异常
-- ============================================================
SET NAMES utf8mb4;

-- -----------------------------------------------------------
-- 1. yxshop_user_tags 用户标签表
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `yxshop_user_tags` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '标签ID',
  `name`        VARCHAR(32) NOT NULL COMMENT '标签名称',
  `color`       VARCHAR(7) DEFAULT '#666666' COMMENT '标签颜色',
  `tag_type`    VARCHAR(20) NOT NULL DEFAULT 'manual' COMMENT '标签类型：manual=手动 auto=自动 system=系统',
  `description` VARCHAR(255) DEFAULT NULL COMMENT '标签描述',
  `sort`        INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序',
  `status`      TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态：0=禁用 1=启用',
  `app_id`      BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '所属应用',
  `created_at`  INT UNSIGNED NOT NULL COMMENT '创建时间（程序层写入）',
  `updated_at`  INT UNSIGNED NOT NULL COMMENT '更新时间（程序层写入）',
  PRIMARY KEY (`id`),
  KEY `idx_app_id`    (`app_id`),
  KEY `idx_tag_type` (`tag_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户标签表';

-- -----------------------------------------------------------
-- 2. yxshop_user_tagRelations 用户标签关联表
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `yxshop_user_tag_relations` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id`   BIGINT UNSIGNED NOT NULL COMMENT '用户ID',
  `tag_id`    BIGINT UNSIGNED NOT NULL COMMENT '标签ID',
  `operator_id` BIGINT UNSIGNED DEFAULT NULL COMMENT '打标签人ID（admin）',
  `operator_name` VARCHAR(64) DEFAULT NULL COMMENT '打标签人姓名',
  `reason`    VARCHAR(255) DEFAULT NULL COMMENT '打标签原因',
  `app_id`    BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '所属应用',
  `created_at` INT UNSIGNED NOT NULL COMMENT '打标签时间（程序层写入）',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_tag`  (`user_id`, `tag_id`),
  KEY `idx_tag_id`    (`tag_id`),
  KEY `idx_user_id`  (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户标签关联表';

-- -----------------------------------------------------------
-- 3. yxshop_user_risk_logs 用户风险/异常行为日志
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `yxshop_user_risk_logs` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id`   BIGINT UNSIGNED DEFAULT NULL COMMENT '用户ID（未登录可为空）',
  `event_type` VARCHAR(30) NOT NULL COMMENT '事件类型：login_fail/multi_login/high_freq/chargeback/suspicious_order/flagged',
  `level`      TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '风险等级：1=低 2=中 3=高',
  `ip`         VARCHAR(45) DEFAULT NULL COMMENT 'IP地址',
  `device`     VARCHAR(128) DEFAULT NULL COMMENT '设备指纹',
  `content`    VARCHAR(512) DEFAULT NULL COMMENT '事件描述',
  `evidence`  JSON DEFAULT NULL COMMENT '证据数据',
  `operator_id` BIGINT UNSIGNED DEFAULT NULL COMMENT '处理人ID',
  `handle_remark` VARCHAR(255) DEFAULT NULL COMMENT '处理备注',
  `handled_at`  INT UNSIGNED DEFAULT NULL COMMENT '处理时间',
  `app_id`     BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '所属应用',
  `created_at` INT UNSIGNED NOT NULL COMMENT '发生时间（程序层写入）',
  PRIMARY KEY (`id`),
  KEY `idx_user_id`    (`user_id`),
  KEY `idx_event_type` (`event_type`),
  KEY `idx_level`      (`level`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户风险行为日志';

-- -----------------------------------------------------------
-- 4. 给 yxshop_users 加风险字段
-- -----------------------------------------------------------
-- ---- 迁移: 2026_28_card_recharge.sql ----
-- ============================================================
-- 会员卡企业级改造：卡套餐 + 充值规则 + 充值赠送
-- ============================================================
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_users`
-- [DISABLED-REDUNDANT-MIGRATION]   ADD COLUMN `risk_level`     TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '风险等级：0=正常 1=低 2=中 3=高',
-- [DISABLED-REDUNDANT-MIGRATION]   ADD COLUMN `risk_remark`   VARCHAR(255) DEFAULT NULL COMMENT '风险备注',
-- [DISABLED-REDUNDANT-MIGRATION]   ADD COLUMN `frozen_reason` VARCHAR(255) DEFAULT NULL COMMENT '冻结原因',
-- [DISABLED-REDUNDANT-MIGRATION]   ADD COLUMN `login_security_status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '登录安全状态：0=正常 1=异常',
-- [DISABLED-REDUNDANT-MIGRATION]   ADD INDEX `idx_risk_level`   (`risk_level`),
-- [DISABLED-REDUNDANT-MIGRATION]   ADD INDEX `idx_login_security_status` (`login_security_status`);

-- -----------------------------------------------------------
-- 1. yxshop_card_packages 会员卡套餐表
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `yxshop_card_packages` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '套餐ID',
  `name`         VARCHAR(64) NOT NULL COMMENT '套餐名称',
  `card_type`   VARCHAR(20) NOT NULL COMMENT '卡类型：month/quarter/year/permanent',
  `price`        DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '售价',
  `original_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '原价',
  `discount_rate` DECIMAL(3,2) NOT NULL DEFAULT 10.00 COMMENT '折扣率（0-10）',
  `benefits`     JSON DEFAULT NULL COMMENT '权益说明（JSON数组）',
  `open_threshold` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '开卡门槛（最低充值）',
  `sort`         INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序',
  `status`       TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态：0=下架 1=上架',
  `app_id`       BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '所属应用',
  `created_at`   INT UNSIGNED NOT NULL COMMENT '创建时间（程序层写入）',
  `updated_at`   INT UNSIGNED NOT NULL COMMENT '更新时间（程序层写入）',
  PRIMARY KEY (`id`),
  KEY `idx_app_id`  (`app_id`),
  KEY `idx_card_type` (`card_type`),
  KEY `idx_status`   (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='会员卡套餐表';

-- -----------------------------------------------------------
-- 2. yxshop_card_recharge_rules 充值规则表
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `yxshop_card_recharge_rules` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '规则ID',
  `name`         VARCHAR(64) NOT NULL COMMENT '规则名称',
  `min_amount`   DECIMAL(10,2) NOT NULL COMMENT '最低充值金额',
  `max_amount`   DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '最高充值金额（0=不限制）',
  `gift_amount`  DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '赠送金额',
  `gift_ratio`   DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT '充值比例（如充100送20则gift_ratio=20.00）',
  `gift_coupon_ids` VARCHAR(255) DEFAULT NULL COMMENT '赠送优惠券ID列表，逗号分隔',
  `gift_points`   INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '赠送积分',
  `status`        TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态：0=禁用 1=启用',
  `sort`          INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序',
  `app_id`        BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '所属应用',
  `created_at`    INT UNSIGNED NOT NULL COMMENT '创建时间（程序层写入）',
  `updated_at`    INT UNSIGNED NOT NULL COMMENT '更新时间（程序层写入）',
  PRIMARY KEY (`id`),
  KEY `idx_app_id` (`app_id`),
  KEY `idx_status`  (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='充值规则表';

-- -----------------------------------------------------------
-- 3. yxshop_card_recharge_logs 充值记录日志
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `yxshop_card_recharge_logs` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '记录ID',
  `user_id`        BIGINT UNSIGNED NOT NULL COMMENT '充值用户ID',
  `card_id`        BIGINT UNSIGNED DEFAULT NULL COMMENT '充值到的会员卡ID',
  `amount`         DECIMAL(10,2) NOT NULL COMMENT '充值金额',
  `gift_amount`    DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '赠送金额',
  `gift_points`    INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '赠送积分',
  `gift_coupon_ids` VARCHAR(255) DEFAULT NULL COMMENT '赠送优惠券ID列表',
  `payment_method` VARCHAR(20) NOT NULL COMMENT '支付方式：wechat/alipay/balance/other',
  `transaction_id` VARCHAR(64) DEFAULT NULL COMMENT '第三方交易号',
  `operator_id`   BIGINT UNSIGNED DEFAULT NULL COMMENT '操作人ID（admin）',
  `operator_name` VARCHAR(64) DEFAULT NULL COMMENT '操作人姓名',
  `app_id`         BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '所属应用',
  `created_at`     INT UNSIGNED NOT NULL COMMENT '充值时间（程序层写入）',
  PRIMARY KEY (`id`),
  KEY `idx_user_id`    (`user_id`),
  KEY `idx_card_id`    (`card_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='会员卡充值记录日志';

-- -----------------------------------------------------------
-- 4. 给 yxshop_cards 加套餐和门槛字段
-- -----------------------------------------------------------
-- ---- 迁移: 2026_29_finance_account.sql ----
-- ============================================================
-- 财务报表企业级改造：对账功能 + 账单导出
-- ============================================================
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_cards`
-- [DISABLED-REDUNDANT-MIGRATION]   ADD COLUMN `package_id`      BIGINT UNSIGNED DEFAULT NULL COMMENT '所属套餐ID',
-- [DISABLED-REDUNDANT-MIGRATION]   ADD COLUMN `open_threshold`  DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '开卡门槛金额',
-- [DISABLED-REDUNDANT-MIGRATION]   ADD COLUMN `recharge_total` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '累计充值金额',
-- [DISABLED-REDUNDANT-MIGRATION]   ADD COLUMN `version`        INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '乐观锁版本号',
-- [DISABLED-REDUNDANT-MIGRATION]   ADD INDEX `idx_package_id`   (`package_id`);

-- -----------------------------------------------------------
-- 1. yxshop_account_statements 平台对账单
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `yxshop_account_statements` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '账单ID',
  `statement_no`    VARCHAR(32) NOT NULL COMMENT '账单号',
  `statement_type`  VARCHAR(20) NOT NULL COMMENT '账单类型：daily/weekly/monthly',
  `start_date`      INT UNSIGNED NOT NULL COMMENT '统计开始日期（Unix戳）',
  `end_date`        INT UNSIGNED NOT NULL COMMENT '统计结束日期（Unix戳）',
  `total_orders`    INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '订单总数',
  `total_amount`    DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '订单总金额',
  `total_refund`    DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '退款总金额',
  `platform_fee`    DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '平台佣金',
  `settle_amount`   DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '应结算金额',
  `status`          TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态：0=待确认 1=已确认 2=已打款',
  `confirmed_at`    INT UNSIGNED DEFAULT NULL COMMENT '确认时间',
  `paid_at`         INT UNSIGNED DEFAULT NULL COMMENT '打款时间',
  `operator_id`     BIGINT UNSIGNED DEFAULT NULL COMMENT '确认人ID',
  `operator_name`   VARCHAR(64) DEFAULT NULL COMMENT '确认人姓名',
  `remark`          VARCHAR(512) DEFAULT NULL COMMENT '备注',
  `app_id`          BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '所属应用',
  `created_at`      INT UNSIGNED NOT NULL COMMENT '创建时间（程序层写入）',
  `updated_at`      INT UNSIGNED NOT NULL COMMENT '更新时间（程序层写入）',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_statement_no` (`statement_no`),
  KEY `idx_statement_type` (`statement_type`),
  KEY `idx_status`   (`status`),
  KEY `idx_app_id`   (`app_id`),
  KEY `idx_start_date` (`start_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='平台对账单';

-- -----------------------------------------------------------
-- 2. yxshop_account_statement_items 对账明细
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `yxshop_account_statement_items` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `statement_id`    BIGINT UNSIGNED NOT NULL COMMENT '账单ID',
  `order_id`        BIGINT UNSIGNED NOT NULL COMMENT '订单ID',
  `order_no`        VARCHAR(32) NOT NULL COMMENT '订单号',
  `user_id`         BIGINT UNSIGNED NOT NULL COMMENT '用户ID',
  `order_amount`    DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '订单金额',
  `refund_amount`   DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '退款金额',
  `commission`      DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '平台佣金',
  `item_type`       VARCHAR(20) NOT NULL COMMENT '明细类型：order/refund/adjustment',
  `remark`          VARCHAR(255) DEFAULT NULL COMMENT '备注',
  `app_id`          BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '所属应用',
  `created_at`      INT UNSIGNED NOT NULL COMMENT '创建时间（程序层写入）',
  PRIMARY KEY (`id`),
  KEY `idx_statement_id` (`statement_id`),
  KEY `idx_order_id`     (`order_id`),
  KEY `idx_user_id`      (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='对账明细';

-- -----------------------------------------------------------
-- 3. yxshop_supplier_statements 供应商对账单
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `yxshop_supplier_statements` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `statement_no`    VARCHAR(32) NOT NULL COMMENT '账单号',
  `supplier_id`     BIGINT UNSIGNED NOT NULL COMMENT '供应商ID',
  `start_date`      INT UNSIGNED NOT NULL COMMENT '统计开始',
  `end_date`        INT UNSIGNED NOT NULL COMMENT '统计结束',
  `total_orders`    INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '订单数',
  `total_sales`     DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '销售总额',
  `commission`      DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '平台抽成',
  `settle_amount`   DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '应结金额',
  `status`          TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态：0=待确认 1=已确认 2=已打款',
  `operator_id`     BIGINT UNSIGNED DEFAULT NULL COMMENT '确认人ID',
  `paid_at`         INT UNSIGNED DEFAULT NULL COMMENT '打款时间',
  `remark`          VARCHAR(512) DEFAULT NULL COMMENT '备注',
  `app_id`          BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '所属应用',
  `created_at`      INT UNSIGNED NOT NULL COMMENT '创建时间（程序层写入）',
  `updated_at`      INT UNSIGNED NOT NULL COMMENT '更新时间（程序层写入）',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_statement_no` (`statement_no`),
  KEY `idx_supplier_id` (`supplier_id`),
  KEY `idx_status`   (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='供应商对账单';

-- ---- 迁移: 2026_30_dealer_enterprise.sql ----
-- ============================================================
-- 经销商企业级改造：等级管理 + 操作日志 + 佣金结算
-- ============================================================
SET NAMES utf8mb4;

-- -----------------------------------------------------------
-- 1. yxshop_dealer_levels 经销商等级表
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `yxshop_dealer_levels` (
  `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '等级ID',
  `name`          VARCHAR(32) NOT NULL COMMENT '等级名称',
  `level`         INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '等级值（1=普通 2=银牌 3=金牌 4=钻石）',
  `min_spent`     DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '最低消费门槛',
  `min_team_size` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '最低团队规模',
  `commission_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT '佣金比例（%）',
  `discount_rate`  DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT '折扣比例（%）',
  `icon`          VARCHAR(255) DEFAULT NULL COMMENT '等级图标',
  `benefits`      JSON DEFAULT NULL COMMENT '等级权益',
  `sort`          INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序',
  `status`        TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态：0=禁用 1=启用',
  `app_id`        BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '所属应用',
  `created_at`    INT UNSIGNED NOT NULL COMMENT '创建时间（程序层写入）',
  `updated_at`    INT UNSIGNED NOT NULL COMMENT '更新时间（程序层写入）',
  PRIMARY KEY (`id`),
  KEY `idx_app_id`  (`app_id`),
  KEY `idx_level`   (`level`),
  KEY `idx_status`  (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='经销商等级表';

-- -----------------------------------------------------------
-- 2. yxshop_dealer_commission_logs 经销商佣金变动日志
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `yxshop_dealer_commission_logs` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '日志ID',
  `dealer_id`        BIGINT UNSIGNED NOT NULL COMMENT '经销商ID',
  `user_id`          BIGINT UNSIGNED DEFAULT NULL COMMENT '关联用户ID（产生佣金的来源用户）',
  `order_id`         BIGINT UNSIGNED DEFAULT NULL COMMENT '关联订单ID',
  `change_type`      VARCHAR(20) NOT NULL COMMENT '变动类型：order_commission/refund/freeze/unfreeze/withdraw/adjust',
  `amount`           DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '变动金额（正负）',
  `balance_before`   DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '变动前余额',
  `balance_after`    DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '变动后余额',
  `freeze_before`    DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '冻结前金额',
  `freeze_after`     DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '冻结后金额',
  `remark`          VARCHAR(255) DEFAULT NULL COMMENT '备注',
  `operator_id`     BIGINT UNSIGNED DEFAULT NULL COMMENT '操作人ID（admin）',
  `app_id`          BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '所属应用',
  `created_at`      INT UNSIGNED NOT NULL COMMENT '变动时间（程序层写入）',
  PRIMARY KEY (`id`),
  KEY `idx_dealer_id`  (`dealer_id`),
  KEY `idx_user_id`    (`user_id`),
  KEY `idx_order_id`   (`order_id`),
  KEY `idx_change_type`(`change_type`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='经销商佣金变动日志';

-- -----------------------------------------------------------
-- 3. yxshop_dealer_logs 经销商操作日志
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `yxshop_dealer_logs` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '日志ID',
  `dealer_id`    BIGINT UNSIGNED NOT NULL COMMENT '经销商ID',
  `action`       VARCHAR(30) NOT NULL COMMENT '动作：apply/approve/reject/upgrade/downgrade/freeze/unfreeze/withdraw',
  `before_status` TINYINT UNSIGNED DEFAULT NULL COMMENT '操作前状态',
  `after_status`  TINYINT UNSIGNED DEFAULT NULL COMMENT '操作后状态',
  `actor_id`     BIGINT UNSIGNED DEFAULT NULL COMMENT '操作人ID',
  `actor_name`   VARCHAR(64) DEFAULT NULL COMMENT '操作人姓名',
  `remark`       VARCHAR(512) DEFAULT NULL COMMENT '备注/拒绝原因',
  `extra`        JSON DEFAULT NULL COMMENT '扩展数据',
  `app_id`       BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '所属应用',
  `created_at`   INT UNSIGNED NOT NULL COMMENT '操作时间（程序层写入）',
  PRIMARY KEY (`id`),
  KEY `idx_dealer_id` (`dealer_id`),
  KEY `idx_action`    (`action`),
  KEY `idx_created_at`(`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='经销商操作日志';

-- -----------------------------------------------------------
-- 4. 给 yxshop_dealers (yxshop_users is_dealer) 加字段
-- 先检查表是否存在，暂用 ALTER TABLE yxshop_users
-- -----------------------------------------------------------
-- ---- 迁移: 2026_31_warehouse_supplier_enterprise.sql ----
-- ============================================================
-- 仓库/供应商企业级改造：状态机 + 操作日志 + 软删除
-- ============================================================
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_users`
-- [DISABLED-REDUNDANT-MIGRATION]   ADD COLUMN `dealer_level_id`   BIGINT UNSIGNED DEFAULT NULL COMMENT '经销商等级ID',
-- [DISABLED-REDUNDANT-MIGRATION]   ADD COLUMN `dealer_status`     TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '经销商状态：0=非经销商 1=正常 2=冻结',
-- [DISABLED-REDUNDANT-MIGRATION]   ADD COLUMN `total_commission`  DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '累计佣金',
-- [DISABLED-REDUNDANT-MIGRATION]   ADD COLUMN `pending_commission` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '待结佣金',
-- [DISABLED-REDUNDANT-MIGRATION]   ADD COLUMN `withdrawn_commission` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '已提佣金',
-- [DISABLED-REDUNDANT-MIGRATION]   ADD COLUMN `frozen_commission` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '冻结佣金',
-- [DISABLED-REDUNDANT-MIGRATION]   ADD COLUMN `dealer_apply_time` INT UNSIGNED DEFAULT NULL COMMENT '申请成为经销商时间',
-- [DISABLED-REDUNDANT-MIGRATION]   ADD COLUMN `dealer_active_time` INT UNSIGNED DEFAULT NULL COMMENT '经销商激活时间',
-- [DISABLED-REDUNDANT-MIGRATION]   ADD INDEX `idx_dealer_status`   (`dealer_status`);

-- -----------------------------------------------------------
-- 1. yxshop_warehouse_logs 仓库操作日志
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `yxshop_warehouse_logs` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '日志ID',
  `warehouse_id` BIGINT UNSIGNED NOT NULL COMMENT '仓库ID',
  `action`       VARCHAR(30) NOT NULL COMMENT '动作：create/update/enable/disable/delete',
  `before_status` TINYINT UNSIGNED DEFAULT NULL COMMENT '操作前状态',
  `after_status`  TINYINT UNSIGNED DEFAULT NULL COMMENT '操作后状态',
  `actor_id`     BIGINT UNSIGNED DEFAULT NULL COMMENT '操作人ID',
  `actor_name`   VARCHAR(64) DEFAULT NULL COMMENT '操作人姓名',
  `remark`       VARCHAR(512) DEFAULT NULL COMMENT '备注',
  `extra`       JSON DEFAULT NULL COMMENT '扩展数据',
  `created_at`  INT UNSIGNED NOT NULL COMMENT '操作时间（程序层写入）',
  PRIMARY KEY (`id`),
  KEY `idx_warehouse_id` (`warehouse_id`),
  KEY `idx_action`    (`action`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='仓库操作日志';

-- -----------------------------------------------------------
-- 2. yxshop_supplier_logs 供应商操作日志
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `yxshop_supplier_logs` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '日志ID',
  `supplier_id`  BIGINT UNSIGNED NOT NULL COMMENT '供应商ID',
  `action`       VARCHAR(30) NOT NULL COMMENT '动作：create/update/enable/disable/delete',
  `before_status` TINYINT UNSIGNED DEFAULT NULL COMMENT '操作前状态',
  `after_status`  TINYINT UNSIGNED DEFAULT NULL COMMENT '操作后状态',
  `actor_id`     BIGINT UNSIGNED DEFAULT NULL COMMENT '操作人ID',
  `actor_name`   VARCHAR(64) DEFAULT NULL COMMENT '操作人姓名',
  `remark`       VARCHAR(512) DEFAULT NULL COMMENT '备注',
  `extra`       JSON DEFAULT NULL COMMENT '扩展数据',
  `created_at`  INT UNSIGNED NOT NULL COMMENT '操作时间（程序层写入）',
  PRIMARY KEY (`id`),
  KEY `idx_supplier_id` (`supplier_id`),
  KEY `idx_action`    (`action`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='供应商操作日志';

-- -----------------------------------------------------------
-- 3. 给 yxshop_warehouses 加 version + deleted_at + 操作日志字段
-- -----------------------------------------------------------
-- -----------------------------------------------------------
-- 4. 给 yxshop_suppliers 加 version + deleted_at + 状态字段
-- -----------------------------------------------------------
-- ---- 迁移: 2026_32_add_sort_to_item_types.sql ----
-- 添加 sort 字段到 item_types 表（商品类型管理需要）
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_suppliers`
-- [DISABLED-REDUNDANT-MIGRATION]   ADD COLUMN `version`        INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '乐观锁版本号',
-- [DISABLED-REDUNDANT-MIGRATION]   ADD COLUMN `deleted_at`     INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除标记（0=未删）',
-- [DISABLED-REDUNDANT-MIGRATION]   ADD COLUMN `status`        TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态：0=禁用 1=启用',
-- [DISABLED-REDUNDANT-MIGRATION]   ADD INDEX `idx_deleted_at`  (`deleted_at`);

-- ---- 迁移: 2026_33_order_enhance.sql ----
CREATE TABLE `yxshop_refund_records` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `refund_no` varchar(32) NOT NULL DEFAULT '' COMMENT '退款单号',
  `order_id` varchar(64) NOT NULL DEFAULT '' COMMENT '订单ID',
  `order_no` varchar(32) NOT NULL DEFAULT '' COMMENT '订单编号',
  `after_sales_id` varchar(64) NOT NULL DEFAULT '0' COMMENT '售后ID',
  `user_id` varchar(64) NOT NULL DEFAULT '' COMMENT '用户ID',
  `refund_amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '退款金额',
  `refund_reason` varchar(500) NOT NULL DEFAULT '' COMMENT '退款原因',
  `refund_type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '退款类型：1=原路退回 2=余额退回',
  `refund_status` tinyint(1) NOT NULL DEFAULT 10 COMMENT '退款状态：10=待处理 20=退款中 30=退款成功 40=退款失败',
  `transaction_id` varchar(64) NOT NULL DEFAULT '' COMMENT '支付流水号',
  `refund_transaction_id` varchar(64) NOT NULL DEFAULT '' COMMENT '退款流水号',
  `operator_type` tinyint(1) NOT NULL DEFAULT 20 COMMENT '操作者类型：10=用户 20=管理员 30=系统',
  `operator_id` varchar(64) NOT NULL DEFAULT '0' COMMENT '操作者ID',
  `operator_name` varchar(50) NOT NULL DEFAULT '' COMMENT '操作者名称',
  `failed_reason` varchar(500) NOT NULL DEFAULT '' COMMENT '失败原因',
  `app_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '应用ID',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_refund_no` (`refund_no`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_refund_status` (`refund_status`),
  KEY `idx_created_at` (`created_at`)
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_orders`
-- [DISABLED-REDUNDANT-MIGRATION]   ADD COLUMN `audit_status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '审核状态：0=未审核 10=审核通过 20=审核拒绝' AFTER `receipt_status`,
-- [DISABLED-REDUNDANT-MIGRATION]   ADD COLUMN `audit_remark` varchar(500) NOT NULL DEFAULT '' COMMENT '审核备注' AFTER `audit_status`,
-- [DISABLED-REDUNDANT-MIGRATION]   ADD COLUMN `audit_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '审核时间' AFTER `audit_remark`;

-- ---- 迁移: 2026_35_data_change_log_enhance.sql ----
-- ============================================================
-- 数据变更日志增强迁移
-- 版本：2026_35
-- 说明：为 yxshop_data_change_logs 增加字段级变更记录支持
-- ============================================================

-- ---- 迁移: 2026_35_referral_system_fix.sql ----
-- ============================================================
-- 推荐系统：dealer_referees 表结构补全
-- 模型 DealerReferee 定义了 referee_id/referee_type/referee_name 等字段，
-- 但实际数据库表缺少这些列，此处补全。
-- ============================================================
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_data_change_logs`
-- [DISABLED-REDUNDANT-MIGRATION]   MODIFY COLUMN `record_id` varchar(64) NOT NULL DEFAULT '' COMMENT '记录ID（兼容雪花ID）',
-- [DISABLED-REDUNDANT-MIGRATION]   MODIFY COLUMN `user_id` varchar(64) NOT NULL DEFAULT '0' COMMENT '操作者ID（兼容雪花ID）',
-- [DISABLED-REDUNDANT-MIGRATION]   ADD COLUMN `operator_name` varchar(100) NOT NULL DEFAULT '' COMMENT '操作者名称' AFTER `user_type`,
-- [DISABLED-REDUNDANT-MIGRATION]   ADD INDEX `idx_table_record` (`table_name`, `record_id`),
-- [DISABLED-REDUNDANT-MIGRATION]   ADD INDEX `idx_operation_type` (`operation_type`),
-- [DISABLED-REDUNDANT-MIGRATION]   ADD INDEX `idx_user` (`user_id`, `user_type`),
-- [DISABLED-REDUNDANT-MIGRATION]   ADD INDEX `idx_created_at` (`created_at`);

-- 1. 补全 yxshop_dealer_referees 缺失字段
-- 索引
-- 工具存储过程：幂等添加列/索引（MySQL 5.7 兼容）
DROP PROCEDURE IF EXISTS `yxshop_add_column_if_missing`;
DELIMITER //
CREATE PROCEDURE `yxshop_add_column_if_missing`(
  IN p_table VARCHAR(100),
  IN p_column VARCHAR(100),
  IN p_definition TEXT
)
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table AND COLUMN_NAME = p_column
  ) THEN
    SET @ddl = CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN `', p_column, '` ', p_definition);
    PREPARE s FROM @ddl; EXECUTE s; DEALLOCATE PREPARE s;
  END IF;
END//
DELIMITER ;

DROP PROCEDURE IF EXISTS `yxshop_add_index_if_missing`;
DELIMITER //
CREATE PROCEDURE `yxshop_add_index_if_missing`(
  IN p_table VARCHAR(100), IN p_index VARCHAR(100), IN p_columns TEXT
)
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table AND INDEX_NAME = p_index
  ) THEN
    SET @ddl = CONCAT('ALTER TABLE `', p_table, '` ADD INDEX `', p_index, '` (', p_columns, ')');
    PREPARE s FROM @ddl; EXECUTE s; DEALLOCATE PREPARE s;
  END IF;
END//
DELIMITER ;

CALL yxshop_add_index_if_missing('yxshop_dealer_referees', 'idx_dealer_id', '`dealer_id`');
CALL yxshop_add_index_if_missing('yxshop_dealer_referees', 'idx_referee_id', '`referee_id`');

-- 2. 确保 yxshop_order_agents 表的 confirm 列有默认值
-- 3. 确保 yxshop_agents 表的 commission_rate 有默认值
-- ---- 迁移: 2026_36_distribution_tasks.sql ----
-- ============================================================
-- 分銷定時任務種子數據
-- 添加佣金自動結算和等級升降級檢查的定時任務
-- ============================================================
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_agents`
-- [DISABLED-REDUNDANT-MIGRATION]   MODIFY COLUMN `commission_rate` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT '佣金比例(%)';
SET @APP_ID := 0;
SET @NOW_TS := UNIX_TIMESTAMP();

-- 分銷佣金自動結算（每天凌晨 1 點執行）
INSERT IGNORE INTO `yxshop_scheduled_tasks` (`task_name`, `task_type`, `cron_expression`, `task_command`, `status`, `last_run_at`, `last_run_status`, `app_id`, `created_at`, `updated_at`) VALUES
('分銷佣金自動結算', 'command', '0 1 * * *', 'php webman commission:settle', 1, 0, 0, @APP_ID, @NOW_TS, @NOW_TS),
('分銷等級升降級檢查', 'command', '0 2 * * 1', 'php webman agent:check-level', 1, 0, 0, @APP_ID, @NOW_TS, @NOW_TS);

-- ---- 迁移: 2026_36_order_agent_menu.sql ----
-- ============================================================
-- 迁移：添加"分销订单"菜单到后台菜单表
-- 位置：分销管理(parent_id=30) → 推荐人(sort=50) 之后
-- ============================================================

-- 获取当前最大ID，确保不冲突
-- 已有最大ID为73（AUTO_INCREMENT=74），使用74
INSERT IGNORE INTO `yxshop_admin_menus` (`id`, `parent_id`, `name`, `model`, `url`, `icon`, `sort`, `is_show`, `permission`, `method`, `app_id`, `created_at`, `updated_at`)
VALUES (74, 30, '分销订单', 'distribution', '/distribution/order-agents', 'icon-file', 45, 1, 'order-agents/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 如果ID=74已存在，用AUTO_INCREMENT自动分配
-- 以下为安全备选（可注释掉上面的，用这条代替）
-- INSERT INTO `yxshop_admin_menus` (`parent_id`, `name`, `model`, `url`, `icon`, `sort`, `is_show`, `permission`, `method`, `app_id`, `created_at`, `updated_at`)
-- VALUES (30, '分销订单', 'distribution', '/distribution/order-agents', 'icon-file', 45, 1, 'order-agents/index', 'GET', 10001, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- ---- 迁移: 2026_36_promotion_enhance.sql ----
-- ============================================================
-- 促销功能增强迁移
-- 版本：2026_36
-- 说明：秒杀库存锁定标记 + 拼团状态字段
-- ============================================================

-- ---- 迁移: 2026_37_login_security.sql ----
-- 登录安全策略迁移
-- Admin表新增字段
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_group_buys`
-- [DISABLED-REDUNDANT-MIGRATION]   ADD COLUMN `current_count` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '当前参团人数' AFTER `required_count`,
-- [DISABLED-REDUNDANT-MIGRATION]   ADD COLUMN `group_status` tinyint(1) UNSIGNED NOT NULL DEFAULT 10 COMMENT '拼团状态：10=进行中 20=已成团 30=已失败' AFTER `current_count`;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_admins` ADD COLUMN `locked_until` int unsigned NULL DEFAULT NULL COMMENT '锁定截止时间戳' AFTER `login_fail_count`;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_admins` ADD COLUMN `password_changed_at` int unsigned NULL DEFAULT NULL COMMENT '最后修改密码时间戳' AFTER `locked_until`;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_admins` ADD COLUMN `force_password_change` tinyint unsigned NOT NULL DEFAULT 0 COMMENT '是否强制修改密码' AFTER `password_changed_at`;

-- 密码历史表
CREATE TABLE IF NOT EXISTS `yxshop_admin_password_histories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` bigint unsigned NOT NULL COMMENT '管理员ID',
  `password_hash` varchar(255) NOT NULL COMMENT '密码哈希',
  `created_at` int unsigned NOT NULL DEFAULT 0 COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_admin_id` (`admin_id`),
  KEY `idx_admin_id_created` (`admin_id`, `created_at` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='管理员密码历史';

-- 安全策略默认配置
INSERT IGNORE INTO `yxshop_settings` (`key`, `value`, `type`, `group`, `description`, `app_id`) VALUES
('login_lock_threshold', '5', 'integer', 'security', '登录失败锁定阈值(次)', 0),
('login_lock_duration', '30', 'integer', 'security', '登录锁定时长(分钟)', 0),
('password_min_length', '8', 'integer', 'security', '密码最小长度', 0),
('password_require_uppercase', '0', 'integer', 'security', '密码必须包含大写字母', 0),
('password_require_lowercase', '1', 'integer', 'security', '密码必须包含小写字母', 0),
('password_require_digit', '1', 'integer', 'security', '密码必须包含数字', 0),
('password_require_special', '0', 'integer', 'security', '密码必须包含特殊字符', 0),
('password_expire_days', '90', 'integer', 'security', '密码有效期天数(0=不限制)', 0),
('password_history_count', '3', 'integer', 'security', '密码历史保留次数(0=不限制)', 0),
('session_timeout', '120', 'integer', 'security', '会话超时时间(分钟)', 0);

-- ---- 迁移: 2026_38_wechat_login.sql ----
-- 微信扫码登录字段迁移
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_admins` ADD COLUMN `wechat_openid` varchar(64) DEFAULT NULL COMMENT '微信openid' AFTER `force_password_change`;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_admins` ADD COLUMN `wechat_bound_at` int unsigned DEFAULT NULL COMMENT '微信绑定时间戳' AFTER `wechat_openid`;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_admins` ADD COLUMN `wechat_nickname` varchar(128) DEFAULT NULL COMMENT '微信昵称' AFTER `wechat_bound_at`;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_admins` ADD COLUMN `wechat_avatar` varchar(512) DEFAULT NULL COMMENT '微信头像URL' AFTER `wechat_nickname`;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_admins` ADD UNIQUE INDEX `uk_wechat_openid` (`wechat_openid`);

-- 微信应用配置
INSERT IGNORE INTO `yxshop_settings` (`key`, `value`, `type`, `group`, `description`, `app_id`) VALUES
('wechat_official_appid', '', 'string', 'wechat', '前台C端微信AppID', 0),
('wechat_official_secret', '', 'string', 'wechat', '前台C端微信AppSecret', 0),
('wechat_official_callback_url', '', 'string', 'wechat', '前台C端微信回调地址', 0),
('wechat_admin_appid', '', 'string', 'wechat', '后台管理端微信AppID', 0),
('wechat_admin_secret', '', 'string', 'wechat', '后台管理端微信AppSecret', 0),
('wechat_admin_callback_url', '', 'string', 'wechat', '后台管理端微信回调地址', 0);

-- ---- 迁移: 2026_39_live_stream.sql ----
-- =====================================================
-- 直播帶貨模塊 - 數據庫遷移
-- 商業版功能，需商業版授權
-- =====================================================

-- 直播間表
CREATE TABLE IF NOT EXISTS `yxshop_live_rooms` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主鍵（雪花ID）',
    `title` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '直播標題',
    `cover_image` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '封面圖',
    `anchor_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '主播用戶ID',
    `anchor_name` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '主播名稱',
    `anchor_avatar` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '主播頭像',
    `push_url` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '推流地址',
    `pull_url` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '拉流地址（FLV/HLS）',
    `replay_url` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '回放地址',
    `start_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '預計開始時間',
    `end_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '預計結束時間',
    `actual_start_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '實際開始時間',
    `actual_end_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '實際結束時間',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '狀態：0待播 1直播中 2已結束 3已回放 4已取消',
    `viewer_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '當前觀看人數',
    `max_viewer_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '歷史最高觀看人數',
    `total_viewer_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '累計觀看人次',
    `like_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '點讚數',
    `comment_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '評論數',
    `share_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '分享數',
    `notice` TEXT NULL COMMENT '直播須知',
    `tags` VARCHAR(500) NOT NULL DEFAULT '[]' COMMENT '標籤（JSON數組）',
    `sort` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序（降序）',
    `deleted_at` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '軟刪除時間戳',
    `app_id` BIGINT UNSIGNED NOT NULL DEFAULT 10001 COMMENT '應用ID',
    `created_at` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '創建時間',
    `updated_at` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新時間',
    PRIMARY KEY (`id`),
    INDEX `idx_app_status` (`app_id`, `status`),
    INDEX `idx_anchor` (`anchor_id`),
    INDEX `idx_start_time` (`start_time`),
    INDEX `idx_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='直播間表';

-- 直播間商品表
CREATE TABLE IF NOT EXISTS `yxshop_live_room_products` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主鍵（雪花ID）',
    `room_id` BIGINT UNSIGNED NOT NULL COMMENT '直播間ID',
    `item_id` BIGINT UNSIGNED NOT NULL COMMENT '商品ID',
    `item_name` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '商品名稱（冗餘）',
    `item_image` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '商品主圖（冗餘）',
    `item_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '商品價格（冗餘）',
    `sort` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序',
    `is_explaining` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否講解中',
    `is_pinned` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否置頂',
    `click_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '點擊次數',
    `order_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '下單數',
    `sale_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '銷售額',
    `app_id` BIGINT UNSIGNED NOT NULL DEFAULT 10001 COMMENT '應用ID',
    `created_at` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '創建時間',
    `updated_at` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新時間',
    PRIMARY KEY (`id`),
    INDEX `idx_room` (`room_id`),
    INDEX `idx_item` (`item_id`),
    INDEX `idx_room_explaining` (`room_id`, `is_explaining`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='直播間商品表';

-- 直播觀看記錄表
CREATE TABLE IF NOT EXISTS `yxshop_live_viewer_logs` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主鍵（雪花ID）',
    `room_id` BIGINT UNSIGNED NOT NULL COMMENT '直播間ID',
    `user_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '用戶ID',
    `user_name` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '用戶名稱',
    `user_avatar` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '用戶頭像',
    `enter_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '進入時間',
    `leave_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '離開時間',
    `stay_duration` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '停留時長（秒）',
    `like_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '點讚數',
    `comment_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '評論數',
    `order_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '下單數',
    `app_id` BIGINT UNSIGNED NOT NULL DEFAULT 10001 COMMENT '應用ID',
    `created_at` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '創建時間',
    `updated_at` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新時間',
    PRIMARY KEY (`id`),
    INDEX `idx_room` (`room_id`),
    INDEX `idx_user` (`user_id`),
    INDEX `idx_room_enter` (`room_id`, `enter_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='直播觀看記錄表';

-- 插入直播管理菜單
INSERT IGNORE INTO `yxshop_admin_menus` (`id`, `parent_id`, `name`, `icon`, `url`, `model`, `sort`, `is_show`, `app_id`, `created_at`, `updated_at`)
SELECT
    UNIX_TIMESTAMP() * 1000 + 1,
    0,
    '直播管理',
    'fas fa-video',
    '/admin/live-stream',
    'live_stream',
    85,
    1,
    10001,
    UNIX_TIMESTAMP(),
    UNIX_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM `yxshop_admin_menus` WHERE `model` = 'live_stream' AND `app_id` = 10001
);

-- 插入子菜單
INSERT IGNORE INTO `yxshop_admin_menus` (`id`, `parent_id`, `name`, `icon`, `url`, `model`, `sort`, `is_show`, `app_id`, `created_at`, `updated_at`)
SELECT
    UNIX_TIMESTAMP() * 1000 + 2,
    (SELECT `id` FROM `yxshop_admin_menus` WHERE `model` = 'live_stream' AND `app_id` = 10001 LIMIT 1),
    '直播间列表',
    'fas fa-broadcast-tower',
    '/admin/live-stream/list',
    'live_stream_list',
    1,
    1,
    10001,
    UNIX_TIMESTAMP(),
    UNIX_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM `yxshop_admin_menus` WHERE `model` = 'live_stream_list' AND `app_id` = 10001
);

-- ---- 迁移: 2026_40_multi_shop.sql ----
-- =====================================================
-- 多店鋪系統完善 - 企業版數據庫遷移
-- 分賬結算引擎、店鋪等級、店鋪表
-- =====================================================

-- 店鋪等級表
CREATE TABLE IF NOT EXISTS `yxshop_shop_levels` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主鍵（雪花ID）',
    `name` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '等級名稱',
    `commission_rate` DECIMAL(5,2) NOT NULL DEFAULT 5.00 COMMENT '平台抽傭率（%）',
    `max_products` INT UNSIGNED NOT NULL DEFAULT 1000 COMMENT '最大商品數',
    `max_storage` BIGINT UNSIGNED NOT NULL DEFAULT 5120 COMMENT '最大存儲空間（MB）',
    `features` JSON NULL COMMENT '功能特性（JSON）',
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序（升序）',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '狀態：0禁用 1啟用',
    `app_id` BIGINT UNSIGNED NOT NULL DEFAULT 10001 COMMENT '應用ID',
    `created_at` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '創建時間',
    `updated_at` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新時間',
    PRIMARY KEY (`id`),
    INDEX `idx_app_status` (`app_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='店鋪等級表';

-- 店鋪表
CREATE TABLE IF NOT EXISTS `yxshop_shops` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主鍵（雪花ID）',
    `name` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '店鋪名稱',
    `logo` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '店鋪LOGO',
    `description` TEXT NULL COMMENT '店鋪描述',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '狀態：0待審 1已通過 2已拒絕 3已封禁',
    `level_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '店鋪等級ID',
    `commission_rate` DECIMAL(5,2) NOT NULL DEFAULT 5.00 COMMENT '抽傭率（覆蓋等級默認）',
    `owner_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '店主用戶ID',
    `owner_name` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '店主名稱',
    `contact_phone` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '聯繫電話',
    `business_license` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '營業執照號',
    `license_image` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '執照圖片',
    `settlement_cycle` INT UNSIGNED NOT NULL DEFAULT 7 COMMENT '結算週期（天）',
    `balance` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '可提現餘額',
    `frozen_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '凍結金額',
    `total_settled` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '累計已結算',
    `total_products` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '商品數',
    `total_orders` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '訂單數',
    `rating` DECIMAL(3,2) NOT NULL DEFAULT 5.00 COMMENT '店鋪評分',
    `deleted_at` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '軟刪除時間戳',
    `app_id` BIGINT UNSIGNED NOT NULL DEFAULT 10001 COMMENT '應用ID',
    `created_at` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '創建時間',
    `updated_at` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新時間',
    PRIMARY KEY (`id`),
    INDEX `idx_app_status` (`app_id`, `status`),
    INDEX `idx_owner` (`owner_id`),
    INDEX `idx_level` (`level_id`),
    INDEX `idx_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='店鋪表';

-- 分賬結算記錄表
CREATE TABLE IF NOT EXISTS `yxshop_shop_settlements` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主鍵（雪花ID）',
    `shop_id` BIGINT UNSIGNED NOT NULL COMMENT '店鋪ID',
    `settlement_no` VARCHAR(30) NOT NULL DEFAULT '' COMMENT '結算單號',
    `period_start` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '結算週期開始',
    `period_end` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '結算週期結束',
    `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '訂單總金額',
    `refund_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '退款金額',
    `net_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '淨金額',
    `commission_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT '抽傭率',
    `commission_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '平台抽傭',
    `settlement_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '商家應得',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '狀態：0待結算 1結算中 2已完成 3失敗',
    `settle_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '結算時間',
    `remark` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '備註',
    `app_id` BIGINT UNSIGNED NOT NULL DEFAULT 10001 COMMENT '應用ID',
    `created_at` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '創建時間',
    `updated_at` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新時間',
    PRIMARY KEY (`id`),
    INDEX `idx_shop` (`shop_id`),
    INDEX `idx_shop_period` (`shop_id`, `period_start`, `period_end`),
    INDEX `idx_status` (`status`),
    INDEX `idx_settlement_no` (`settlement_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='店鋪結算記錄表';

-- 插入默認店鋪等級
INSERT IGNORE INTO `yxshop_shop_levels` (`id`, `name`, `commission_rate`, `max_products`, `max_storage`, `features`, `sort_order`, `status`, `app_id`, `created_at`, `updated_at`)
SELECT
    UNIX_TIMESTAMP() * 1000 + 1,
    '基础版',
    8.00,
    500,
    2048,
    '{"features": ["basic_store", "standard_settlement"]}',
    1,
    1,
    10001,
    UNIX_TIMESTAMP(),
    UNIX_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM `yxshop_shop_levels` WHERE `name` = '基础版' AND `app_id` = 10001);

INSERT IGNORE INTO `yxshop_shop_levels` (`id`, `name`, `commission_rate`, `max_products`, `max_storage`, `features`, `sort_order`, `status`, `app_id`, `created_at`, `updated_at`)
SELECT
    UNIX_TIMESTAMP() * 1000 + 2,
    '标准版',
    5.00,
    2000,
    10240,
    '{"features": ["basic_store", "standard_settlement", "data_analytics", "marketing_tools"]}',
    2,
    1,
    10001,
    UNIX_TIMESTAMP(),
    UNIX_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM `yxshop_shop_levels` WHERE `name` = '标准版' AND `app_id` = 10001);

INSERT IGNORE INTO `yxshop_shop_levels` (`id`, `name`, `commission_rate`, `max_products`, `max_storage`, `features`, `sort_order`, `status`, `app_id`, `created_at`, `updated_at`)
SELECT
    UNIX_TIMESTAMP() * 1000 + 3,
    '旗舰版',
    3.00,
    999999,
    51200,
    '{"features": ["basic_store", "standard_settlement", "data_analytics", "marketing_tools", "api_access", "dedicated_support"]}',
    3,
    1,
    10001,
    UNIX_TIMESTAMP(),
    UNIX_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM `yxshop_shop_levels` WHERE `name` = '旗舰版' AND `app_id` = 10001);

-- 為訂單表添加 shop_id 字段（企業版）
CALL yxshop_add_column_if_missing('yxshop_orders', 'shop_id', "BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '店鋪ID（0=平台自營）' AFTER `app_id`");
CALL yxshop_add_index_if_missing('yxshop_orders', 'idx_shop_id', '`shop_id`');

-- ---- 迁移: 2026_41_supply_chain_enhance.sql ----
-- ============================================================
-- 供应链管理完善 (Phase 4.2)
-- 供应商评估体系 / 采购建议 / 供应商门户账户 / 库存周转分析
-- ============================================================
SET NAMES utf8mb4;
SET @NOW = UNIX_TIMESTAMP();

-- -----------------------------------------------------------
-- 1. yxshop_supplier_evaluations  供应商评估记录
--    定期对供应商进行多维度评分（交货准时率/质量合格率/价格竞争力/响应速度/合作态度）
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `yxshop_supplier_evaluations` (
  `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `supplier_id`       BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '供应商ID',
  `eval_period`       VARCHAR(16) NOT NULL DEFAULT '' COMMENT '评估周期（如 2026-Q3 / 2026-08）',
  `eval_type`         TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '评估类型(1=季度 2=月度 3=年度 4=临时)',
  `delivery_score`    DECIMAL(3,1) NOT NULL DEFAULT 0.0 COMMENT '交货准时率得分(0-100)',
  `quality_score`     DECIMAL(3,1) NOT NULL DEFAULT 0.0 COMMENT '质量合格率得分(0-100)',
  `price_score`       DECIMAL(3,1) NOT NULL DEFAULT 0.0 COMMENT '价格竞争力得分(0-100)',
  `response_score`    DECIMAL(3,1) NOT NULL DEFAULT 0.0 COMMENT '响应速度得分(0-100)',
  `service_score`     DECIMAL(3,1) NOT NULL DEFAULT 0.0 COMMENT '服务态度得分(0-100)',
  `total_score`       DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT '综合总分(加权)',
  `grade`             VARCHAR(4) NOT NULL DEFAULT '' COMMENT '评级(A/B/C/D)',
  `order_count`       INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '期内采购单数',
  `order_total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '期内采购总金额',
  `defective_count`   INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '次品数量',
  `late_delivery_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '逾期交货次数',
  `avg_delivery_days` DECIMAL(5,1) NOT NULL DEFAULT 0.0 COMMENT '平均交货天数',
  `remark`            VARCHAR(512) DEFAULT NULL COMMENT '评估备注',
  `evaluator_id`      BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '评估人ID',
  `evaluator_name`    VARCHAR(64) DEFAULT NULL COMMENT '评估人姓名',
  `app_id`            BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '应用ID',
  `version`           INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '乐观锁版本号',
  `deleted_at`        INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除标记',
  `created_at`        INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at`        INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uk_supplier_period` (`supplier_id`, `eval_period`, `app_id`) USING BTREE,
  KEY `idx_supplier_id` (`supplier_id`) USING BTREE,
  KEY `idx_grade` (`grade`) USING BTREE,
  KEY `idx_eval_period` (`eval_period`) USING BTREE,
  KEY `idx_app_id` (`app_id`) USING BTREE,
  KEY `idx_deleted_at` (`deleted_at`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '供应商评估记录表 [Enterprise]' ROW_FORMAT = DYNAMIC;

-- -----------------------------------------------------------
-- 2. yxshop_purchase_suggestions  采购建议
--    系统根据库存预警/销售趋势/安全库存自动生成的采购建议
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `yxshop_purchase_suggestions` (
  `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `suggestion_no`     VARCHAR(32) NOT NULL DEFAULT '' COMMENT '建议单号',
  `item_id`           BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '商品ID',
  `spec_id`           BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '规格ID(0=无规格)',
  `supplier_id`       BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '建议供应商ID',
  `warehouse_id`      BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '建议入库仓库ID',
  `current_stock`     INT NOT NULL DEFAULT 0 COMMENT '当前库存',
  `safety_stock`      INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '安全库存',
  `suggested_qty`     INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '建议采购数量',
  `unit_price`        DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '建议单价',
  `suggested_amount`  DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '建议采购金额',
  `reason_type`       TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '建议原因(1=库存不足 2=安全库存预警 3=销售趋势上升 4=促销备货 5=季节性备货)',
  `reason_detail`     VARCHAR(512) DEFAULT NULL COMMENT '建议原因描述',
  `sales_avg_daily`   DECIMAL(8,2) NOT NULL DEFAULT 0.00 COMMENT '日均销量',
  `sales_avg_weekly`  DECIMAL(8,2) NOT NULL DEFAULT 0.00 COMMENT '周均销量',
  `stock_turnover_days` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '预计库存可周转天数',
  `status`            TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态(0=待处理 1=已采纳生成采购单 2=已忽略 3=已过期)',
  `purchase_order_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联采购单ID(采纳后写入)',
  `ignored_reason`    VARCHAR(256) DEFAULT NULL COMMENT '忽略原因',
  `expire_at`         INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '建议过期时间',
  `creator_id`        BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建人ID(0=系统自动)',
  `creator_name`      VARCHAR(64) DEFAULT NULL COMMENT '创建人姓名',
  `app_id`            BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '应用ID',
  `version`           INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '乐观锁版本号',
  `deleted_at`        INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除标记',
  `created_at`        INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at`        INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_suggestion_no` (`suggestion_no`) USING BTREE,
  KEY `idx_item_id` (`item_id`) USING BTREE,
  KEY `idx_supplier_id` (`supplier_id`) USING BTREE,
  KEY `idx_warehouse_id` (`warehouse_id`) USING BTREE,
  KEY `idx_status` (`status`) USING BTREE,
  KEY `idx_reason_type` (`reason_type`) USING BTREE,
  KEY `idx_app_id` (`app_id`) USING BTREE,
  KEY `idx_deleted_at` (`deleted_at`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '采购建议表 [Enterprise]' ROW_FORMAT = DYNAMIC;

-- -----------------------------------------------------------
-- 3. yxshop_supplier_portal_accounts  供应商门户账户
--    供应商自助登录门户，查看采购单/结算单/发货通知
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `yxshop_supplier_portal_accounts` (
  `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `supplier_id`       BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '供应商ID',
  `username`          VARCHAR(64) NOT NULL DEFAULT '' COMMENT '登录用户名',
  `password_hash`     VARCHAR(255) NOT NULL DEFAULT '' COMMENT '密码哈希(bcrypt)',
  `contact_name`      VARCHAR(64) DEFAULT NULL COMMENT '联系人姓名',
  `contact_phone`     VARCHAR(20) DEFAULT NULL COMMENT '联系电话',
  `contact_email`     VARCHAR(128) DEFAULT NULL COMMENT '联系邮箱',
  `last_login_at`     INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '最后登录时间',
  `last_login_ip`     VARCHAR(45) DEFAULT NULL COMMENT '最后登录IP',
  `status`            TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态(0=禁用 1=启用)',
  `remark`            VARCHAR(256) DEFAULT NULL COMMENT '备注',
  `app_id`            BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '应用ID',
  `version`           INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '乐观锁版本号',
  `deleted_at`        INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除标记',
  `created_at`        INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at`        INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uk_username` (`username`, `app_id`) USING BTREE,
  UNIQUE KEY `uk_supplier_id` (`supplier_id`, `app_id`) USING BTREE,
  KEY `idx_status` (`status`) USING BTREE,
  KEY `idx_app_id` (`app_id`) USING BTREE,
  KEY `idx_deleted_at` (`deleted_at`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '供应商门户账户表 [Enterprise]' ROW_FORMAT = DYNAMIC;

-- -----------------------------------------------------------
-- 4. yxshop_supplier_shipments  供应商发货通知
--    供应商通过门户提交的发货通知，关联采购单
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `yxshop_supplier_shipments` (
  `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `shipment_no`       VARCHAR(32) NOT NULL DEFAULT '' COMMENT '发货单号',
  `supplier_id`       BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '供应商ID',
  `purchase_order_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联采购单ID',
  `logistics_company` VARCHAR(64) DEFAULT NULL COMMENT '物流公司',
  `logistics_no`      VARCHAR(64) DEFAULT NULL COMMENT '物流单号',
  `shipped_qty`       INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '发货数量',
  `shipped_at`        INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '发货时间',
  `estimated_arrival` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '预计到货时间',
  `status`            TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态(0=已发货 1=已到货 2=已拒收 3=部分到货)',
  `received_qty`      INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '到货数量',
  `received_at`       INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '到货时间',
  `remark`            VARCHAR(512) DEFAULT NULL COMMENT '备注',
  `portal_account_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '门户账户ID',
  `app_id`            BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '应用ID',
  `version`           INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '乐观锁版本号',
  `deleted_at`        INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除标记',
  `created_at`        INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at`        INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_shipment_no` (`shipment_no`) USING BTREE,
  KEY `idx_supplier_id` (`supplier_id`) USING BTREE,
  KEY `idx_purchase_order_id` (`purchase_order_id`) USING BTREE,
  KEY `idx_status` (`status`) USING BTREE,
  KEY `idx_app_id` (`app_id`) USING BTREE,
  KEY `idx_deleted_at` (`deleted_at`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '供应商发货通知表 [Enterprise]' ROW_FORMAT = DYNAMIC;

-- -----------------------------------------------------------
-- 5. 给 yxshop_suppliers 补充评估汇总字段
-- -----------------------------------------------------------
CALL yxshop_add_column_if_missing('yxshop_suppliers', 'latest_grade', "VARCHAR(4) NOT NULL DEFAULT '' COMMENT '最新评级(A/B/C/D)'");
CALL yxshop_add_column_if_missing('yxshop_suppliers', 'latest_score', "DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT '最新综合得分'");
CALL yxshop_add_column_if_missing('yxshop_suppliers', 'evaluated_at', "INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '最后评估时间'");

-- -----------------------------------------------------------
-- 6. 给 yxshop_warehouse_stocks 补充周转分析字段
-- -----------------------------------------------------------
CALL yxshop_add_column_if_missing('yxshop_warehouse_stocks', 'safety_stock', "INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '安全库存量'");
CALL yxshop_add_column_if_missing('yxshop_warehouse_stocks', 'avg_daily_sales', "DECIMAL(8,2) NOT NULL DEFAULT 0.00 COMMENT '日均销量'");
CALL yxshop_add_column_if_missing('yxshop_warehouse_stocks', 'last_in_at', "INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '最后入库时间'");
CALL yxshop_add_column_if_missing('yxshop_warehouse_stocks', 'last_out_at', "INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '最后出库时间'");

-- ---- 迁移: 2026_41_supply_chain_menus.sql ----
-- ============================================================
-- 供应链管理完善 — 菜单补充 (Phase 4.2)
-- 供应商评估 / 采购建议 / 供应商门户 / 库存大盘
-- ============================================================
SET NAMES utf8mb4;
SET @APP_ID = 10001;
SET @NOW = UNIX_TIMESTAMP();

-- 供应链子菜单补充（假设 74 为供应链管理顶级菜单ID，75-81 为已有子菜单）
-- 使用大于 81 的 ID 避免冲突

-- 供应商评估
INSERT IGNORE INTO `yxshop_admin_menus` (`id`, `parent_id`, `name`, `model`, `url`, `icon`, `sort`, `is_show`, `permission`, `method`, `app_id`, `created_at`, `updated_at`) VALUES
(82, 74, '供应商评估', 'supply', '/supply/supplier-evaluation', 'icon-star', 80, 1, 'supplier-evaluation/index', 'GET', @APP_ID, @NOW, @NOW);

-- 采购建议
INSERT IGNORE INTO `yxshop_admin_menus` (`id`, `parent_id`, `name`, `model`, `url`, `icon`, `sort`, `is_show`, `permission`, `method`, `app_id`, `created_at`, `updated_at`) VALUES
(83, 74, '采购建议', 'supply', '/supply/purchase-suggestion', 'icon-lightbulb', 90, 1, 'purchase-suggestion/index', 'GET', @APP_ID, @NOW, @NOW);

-- 供应商门户
INSERT IGNORE INTO `yxshop_admin_menus` (`id`, `parent_id`, `name`, `model`, `url`, `icon`, `sort`, `is_show`, `permission`, `method`, `app_id`, `created_at`, `updated_at`) VALUES
(84, 74, '供应商门户', 'supply', '/supply/supplier-portal', 'icon-door-open', 100, 1, 'supplier-portal/index', 'GET', @APP_ID, @NOW, @NOW);

-- 库存大盘
INSERT IGNORE INTO `yxshop_admin_menus` (`id`, `parent_id`, `name`, `model`, `url`, `icon`, `sort`, `is_show`, `permission`, `method`, `app_id`, `created_at`, `updated_at`) VALUES
(85, 74, '库存大盘', 'supply', '/supply/inventory-dashboard', 'icon-dashboard', 110, 1, 'inventory-dashboard/index', 'GET', @APP_ID, @NOW, @NOW);

-- 供应商门户子菜单
INSERT IGNORE INTO `yxshop_admin_menus` (`id`, `parent_id`, `name`, `model`, `url`, `icon`, `sort`, `is_show`, `permission`, `method`, `app_id`, `created_at`, `updated_at`) VALUES
(86, 84, '门户账户', 'supply', '/supply/supplier-portal/accounts', 'icon-user', 10, 1, 'supplier-portal/accounts', 'GET', @APP_ID, @NOW, @NOW),
(87, 84, '发货通知', 'supply', '/supply/supplier-portal/shipments', 'icon-truck', 20, 1, 'supplier-portal/shipments', 'GET', @APP_ID, @NOW, @NOW);

-- ---- 迁移: 2026_42_audit_trail_enhance.sql ----
-- ============================================================
-- 审计追踪增强 (Phase 4.3)
-- 合规报告表 + 数据变更日志增强字段
-- ============================================================
SET NAMES utf8mb4;
SET @NOW = UNIX_TIMESTAMP();

-- -----------------------------------------------------------
-- 1. yxshop_audit_compliance_reports  合规审计报告
--    定期/手动生成的合规审计报告
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `yxshop_audit_compliance_reports` (
  `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `report_no`         VARCHAR(32) NOT NULL DEFAULT '' COMMENT '报告编号',
  `report_type`       TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '报告类型(1=月度 2=季度 3=年度 4=临时)',
  `report_period`     VARCHAR(16) NOT NULL DEFAULT '' COMMENT '报告周期(如 2026-08)',
  `start_date`        INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '统计开始时间',
  `end_date`          INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '统计结束时间',
  `total_changes`     INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '总变更次数',
  `compliance_changes` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '合规敏感变更次数',
  `create_count`      INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '新增记录数',
  `update_count`      INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '修改记录数',
  `delete_count`      INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '删除记录数',
  `table_stats`       JSON DEFAULT NULL COMMENT '按表统计(JSON)',
  `operator_stats`    JSON DEFAULT NULL COMMENT '按操作人统计(JSON)',
  `compliance_details` JSON DEFAULT NULL COMMENT '合规变更详情(JSON)',
  `summary`           TEXT DEFAULT NULL COMMENT '报告摘要',
  `status`            TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态(0=生成中 1=已完成 2=已归档)',
  `generator_id`      BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '生成人ID(0=系统自动)',
  `generator_name`    VARCHAR(64) DEFAULT NULL COMMENT '生成人姓名',
  `app_id`            BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '应用ID',
  `version`           INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '乐观锁版本号',
  `deleted_at`        INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除标记',
  `created_at`        INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at`        INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_report_no` (`report_no`) USING BTREE,
  KEY `idx_report_period` (`report_period`) USING BTREE,
  KEY `idx_report_type` (`report_type`) USING BTREE,
  KEY `idx_status` (`status`) USING BTREE,
  KEY `idx_app_id` (`app_id`) USING BTREE,
  KEY `idx_deleted_at` (`deleted_at`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '合规审计报告表 [Enterprise]' ROW_FORMAT = DYNAMIC;

-- -----------------------------------------------------------
-- 2. 给 yxshop_data_change_logs 增加合规标记字段
-- -----------------------------------------------------------
CALL yxshop_add_column_if_missing('yxshop_data_change_logs', 'is_compliance', "TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否合规敏感变更(0=否 1=是)'");
CALL yxshop_add_column_if_missing('yxshop_data_change_logs', 'compliance_level', "TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '合规级别(0=普通 1=敏感 2=高危)'");
CALL yxshop_add_index_if_missing('yxshop_data_change_logs', 'idx_is_compliance', '`is_compliance`');

-- ---- 迁移: create_currencies_table.sql ----
-- 货币表
CREATE TABLE IF NOT EXISTS `yxshop_currencies` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT '货币名称',
  `code` varchar(10) NOT NULL COMMENT '货币代码（如：CNY, USD）',
  `symbol` varchar(10) NOT NULL COMMENT '货币符号（如：¥, $）',
  `exchange_rate` decimal(10, 4) NOT NULL DEFAULT 1.0000 COMMENT '兑换率（相对于基础货币）',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：1-启用，0-禁用',
  `is_default` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否默认货币：1-是，0-否',
  `sort` int(11) NOT NULL DEFAULT 0 COMMENT '排序',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `status` (`status`),
  KEY `is_default` (`is_default`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='货币表';

-- 插入默认货币数据
INSERT IGNORE INTO `yxshop_currencies` (`id`, `name`, `code`, `symbol`, `exchange_rate`, `status`, `is_default`, `sort`, `created_at`, `updated_at`) VALUES
(1, '人民币', 'CNY', '¥', 1.0000, 1, 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(2, '美元', 'USD', '$', 0.1400, 1, 0, 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(3, '欧元', 'EUR', '€', 0.1300, 1, 0, 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(4, '英镑', 'GBP', '£', 0.1100, 1, 0, 4, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(5, '日元', 'JPY', '¥', 20.0000, 1, 0, 5, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- ---- 迁移: create_languages_table.sql ----
-- 语言表
CREATE TABLE IF NOT EXISTS `yxshop_languages` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT '语言名称',
  `code` varchar(10) NOT NULL COMMENT '语言代码（如：zh-CN, en-US）',
  `flag` varchar(50) DEFAULT NULL COMMENT '国旗图标',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：1-启用，0-禁用',
  `is_default` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否默认语言：1-是，0-否',
  `sort` int(11) NOT NULL DEFAULT 0 COMMENT '排序',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `status` (`status`),
  KEY `is_default` (`is_default`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='语言表';

-- 插入默认语言数据
INSERT IGNORE INTO `yxshop_languages` (`id`, `name`, `code`, `flag`, `status`, `is_default`, `sort`, `created_at`, `updated_at`) VALUES
(1, '简体中文', 'zh-CN', 'cn', 1, 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(2, 'English', 'en-US', 'us', 1, 0, 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(3, '繁體中文', 'zh-TW', 'tw', 1, 0, 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(4, '日本語', 'ja-JP', 'jp', 1, 0, 4, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(5, '한국어', 'ko-KR', 'kr', 1, 0, 5, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- ---- 迁移: create_user_contracts.sql ----
-- 用户合同表
CREATE TABLE IF NOT EXISTS `yxshop_user_contracts` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
  `contract_no` varchar(64) NOT NULL DEFAULT '' COMMENT '合同编号',
  `contract_name` varchar(200) NOT NULL DEFAULT '' COMMENT '合同名称',
  `contract_type` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '合同类型(1采购 2销售 3服务 4代理 9其他)',
  `party_a` varchar(200) NOT NULL DEFAULT '' COMMENT '甲方名称',
  `party_b` varchar(200) NOT NULL DEFAULT '' COMMENT '乙方名称',
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '合同金额',
  `start_date` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '开始日期',
  `end_date` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '结束日期',
  `file_url` varchar(500) NOT NULL DEFAULT '' COMMENT '合同文件URL',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态(0草稿 1待签署 2已签署 3已过期 4已终止)',
  `remark` varchar(500) NOT NULL DEFAULT '' COMMENT '备注',
  `signed_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '签署时间',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '应用ID',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_user_id` (`user_id`) USING BTREE,
  INDEX `idx_status` (`status`) USING BTREE,
  INDEX `idx_app_id` (`app_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='用户合同表';

-- ---- 迁移: migration_01_charset_softdelete.sql ----
-- ============================================================
-- Migration 20260622_01: charset_softdelete
-- 1) 64 张 utf8(3字节) 表转换为 utf8mb4（支持 emoji，订单地址/商品名/昵称不再报错）
-- 2) 给缺 deleted_at 的核心业务表补字段（与 BaseModel not_deleted 全局 scope 配套）
-- 3) 修复 10 处 "??????" 乱码注释
-- 兼容 MySQL 5.7 / 8.0，幂等可重复执行
-- ============================================================

-- 版本记录表（若不存在则创建）
CREATE TABLE IF NOT EXISTS `yxshop_schema_migrations` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `version` varchar(32) NOT NULL,
  `name` varchar(100) NOT NULL DEFAULT '',
  `executed_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `uk_version`(`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='迁移版本记录';

-- 幂等工具存储过程：列不存在才 ADD
DROP PROCEDURE IF EXISTS `yxshop_add_column_if_missing`;
DELIMITER //
CREATE PROCEDURE `yxshop_add_column_if_missing`(
  IN p_table VARCHAR(100),
  IN p_column VARCHAR(100),
  IN p_definition TEXT
)
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table AND COLUMN_NAME = p_column
  ) THEN
    SET @ddl = CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN `', p_column, '` ', p_definition);
    PREPARE s FROM @ddl; EXECUTE s; DEALLOCATE PREPARE s;
  END IF;
END//
DELIMITER ;

-- 幂等工具：索引不存在才 ADD
DROP PROCEDURE IF EXISTS `yxshop_add_index_if_missing`;
DELIMITER //
CREATE PROCEDURE `yxshop_add_index_if_missing`(
  IN p_table VARCHAR(100),
  IN p_index VARCHAR(100),
  IN p_columns TEXT
)
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table AND INDEX_NAME = p_index
  ) THEN
    SET @ddl = CONCAT('ALTER TABLE `', p_table, '` ADD INDEX `', p_index, '` (', p_columns, ')');
    PREPARE s FROM @ddl; EXECUTE s; DEALLOCATE PREPARE s;
  END IF;
END//
DELIMITER ;

-- ------------------------------------------------------------
-- 1. 批量转换 utf8 -> utf8mb4（表级 + 列级）
--    MySQL PREPARE 只支持单条语句，不能用 GROUP_CONCAT 拼多条；
--    改用游标逐条 PREPARE/EXECUTE。
-- ------------------------------------------------------------
DROP PROCEDURE IF EXISTS `yxshop_convert_charset`;
DELIMITER //
CREATE PROCEDURE `yxshop_convert_charset`()
BEGIN
  DECLARE done INT DEFAULT 0;
  DECLARE tbl VARCHAR(100);
  DECLARE cur CURSOR FOR
    SELECT TABLE_NAME
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME LIKE 'yxshop\_%'
      AND TABLE_TYPE = 'BASE TABLE'
      AND TABLE_COLLATION NOT LIKE 'utf8mb4%';
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

  OPEN cur;
  read_loop: LOOP
    FETCH cur INTO tbl;
    IF done = 1 THEN
      LEAVE read_loop;
    END IF;
    -- 表+列同时转换
    SET @ddl = CONCAT('ALTER TABLE `', tbl, '` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');
    PREPARE s FROM @ddl; EXECUTE s; DEALLOCATE PREPARE s;
  END LOOP;
  CLOSE cur;
END//
DELIMITER ;

CALL `yxshop_convert_charset`();
DROP PROCEDURE `yxshop_convert_charset`;

-- 兜底：修正表级 default charset（极少数情况 CONVERT 后 TABLE_COLLATION 仍非 utf8mb4）
DROP PROCEDURE IF EXISTS `yxshop_fix_default_charset`;
DELIMITER //
CREATE PROCEDURE `yxshop_fix_default_charset`()
BEGIN
  DECLARE done INT DEFAULT 0;
  DECLARE tbl VARCHAR(100);
  DECLARE cur CURSOR FOR
    SELECT TABLE_NAME
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME LIKE 'yxshop\_%'
      AND TABLE_TYPE = 'BASE TABLE'
      AND TABLE_COLLATION NOT LIKE 'utf8mb4%';
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

  OPEN cur;
  fix_loop: LOOP
    FETCH cur INTO tbl;
    IF done = 1 THEN
      LEAVE fix_loop;
    END IF;
    SET @ddl = CONCAT('ALTER TABLE `', tbl, '` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');
    PREPARE s FROM @ddl; EXECUTE s; DEALLOCATE PREPARE s;
  END LOOP;
  CLOSE cur;
END//
DELIMITER ;

CALL `yxshop_fix_default_charset`();
DROP PROCEDURE `yxshop_fix_default_charset`;

-- ------------------------------------------------------------
-- 2. 给缺 deleted_at 的核心业务表补字段
--    BaseModel not_deleted 全局 scope 会 where deleted_at = 0，
--    无此字段的表查询会 SQL 报错。日志类表不补。
-- ------------------------------------------------------------
CALL yxshop_add_column_if_missing('yxshop_order_goods',     'deleted_at', "int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除时间(0未删)'");
CALL yxshop_add_column_if_missing('yxshop_user_addresses',  'deleted_at', "int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除时间(0未删)'");
CALL yxshop_add_column_if_missing('yxshop_brands',          'deleted_at', "int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除时间(0未删)'");
CALL yxshop_add_column_if_missing('yxshop_categories',      'deleted_at', "int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除时间(0未删)'");
CALL yxshop_add_column_if_missing('yxshop_payments',        'deleted_at', "int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除时间(0未删)'");
CALL yxshop_add_column_if_missing('yxshop_user_coupons',    'deleted_at', "int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除时间(0未删)'");
CALL yxshop_add_column_if_missing('yxshop_articles',        'deleted_at', "int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除时间(0未删)'");
CALL yxshop_add_column_if_missing('yxshop_article_categories','deleted_at', "int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除时间(0未删)'");
CALL yxshop_add_column_if_missing('yxshop_apps',            'deleted_at', "int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除时间(0未删)'");
CALL yxshop_add_column_if_missing('yxshop_item_images',     'deleted_at', "int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除时间(0未删)'");
CALL yxshop_add_column_if_missing('yxshop_item_favorites',  'deleted_at', "int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除时间(0未删)'");
CALL yxshop_add_column_if_missing('yxshop_specs',           'deleted_at', "int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除时间(0未删)'");
CALL yxshop_add_column_if_missing('yxshop_spec_items',      'deleted_at', "int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除时间(0未删)'");
CALL yxshop_add_column_if_missing('yxshop_spec_item_prices','deleted_at', "int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除时间(0未删)'");
CALL yxshop_add_column_if_missing('yxshop_upload_files',    'deleted_at', "int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除时间(0未删)'");
CALL yxshop_add_column_if_missing('yxshop_upload_groups',   'deleted_at', "int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除时间(0未删)'");

CALL yxshop_add_index_if_missing('yxshop_order_goods',     'idx_deleted_at', '`deleted_at`');
CALL yxshop_add_index_if_missing('yxshop_user_addresses',  'idx_deleted_at', '`deleted_at`');
CALL yxshop_add_index_if_missing('yxshop_brands',          'idx_deleted_at', '`deleted_at`');
CALL yxshop_add_index_if_missing('yxshop_categories',      'idx_deleted_at', '`deleted_at`');
CALL yxshop_add_index_if_missing('yxshop_payments',        'idx_deleted_at', '`deleted_at`');
CALL yxshop_add_index_if_missing('yxshop_user_coupons',    'idx_deleted_at', '`deleted_at`');
CALL yxshop_add_index_if_missing('yxshop_articles',        'idx_deleted_at', '`deleted_at`');

-- ------------------------------------------------------------
-- 3. 修复 10 处 "??????" 乱码注释
-- ------------------------------------------------------------
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_admin_roles`     MODIFY COLUMN `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除时间(0未删)';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_admins`          MODIFY COLUMN `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除时间(0未删)';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_orders`          MODIFY COLUMN `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除时间(0未删)';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_items`           MODIFY COLUMN `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除时间(0未删)';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_coupons`         MODIFY COLUMN `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除时间(0未删)';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_advertisements`  MODIFY COLUMN `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除时间(0未删)';


-- ============================================================
-- 修复：签到表结构 + 全局主键类型（配合雪花 ID）
-- BaseModel 使用 SnowflakeTrait 生成 19 位 ID，int 主键会报
-- "Out of range value for column 'id'"，主键必须为 bigint unsigned。
-- ============================================================
CREATE TABLE IF NOT EXISTS `yxshop_signs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint DEFAULT NULL,
  `sign_date` date DEFAULT NULL COMMENT '签到日期',
  `sign_points` int unsigned NOT NULL DEFAULT 0 COMMENT '本次获得积分',
  `sign_continuous` int unsigned NOT NULL DEFAULT 0 COMMENT '连续签到天数',
  `sign_total` int unsigned NOT NULL DEFAULT 0 COMMENT '累计签到天数',
  `sign_reward` json DEFAULT NULL COMMENT '奖励内容',
  `sign_status` tinyint unsigned NOT NULL DEFAULT 1 COMMENT '1正常 2奖励 3额外',
  `day` int DEFAULT NULL,
  `note` mediumtext DEFAULT NULL,
  `app_id` bigint DEFAULT NULL,
  `created_at` int unsigned NOT NULL DEFAULT 0,
  `updated_at` int unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_user_date` (`user_id`, `sign_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户签到记录';

CALL yxshop_add_column_if_missing('yxshop_signs', 'sign_date', "date DEFAULT NULL COMMENT '签到日期'");
CALL yxshop_add_column_if_missing('yxshop_signs', 'sign_points', "int unsigned NOT NULL DEFAULT 0 COMMENT '本次获得积分'");
CALL yxshop_add_column_if_missing('yxshop_signs', 'sign_continuous', "int unsigned NOT NULL DEFAULT 0 COMMENT '连续签到天数'");
CALL yxshop_add_column_if_missing('yxshop_signs', 'sign_total', "int unsigned NOT NULL DEFAULT 0 COMMENT '累计签到天数'");
CALL yxshop_add_column_if_missing('yxshop_signs', 'sign_reward', "json DEFAULT NULL COMMENT '奖励内容'");
CALL yxshop_add_column_if_missing('yxshop_signs', 'sign_status', "tinyint unsigned NOT NULL DEFAULT 1 COMMENT '1正常 2奖励 3额外'");
CALL yxshop_add_index_if_missing('yxshop_signs', 'idx_user_date', "`user_id`, `sign_date`");

ALTER TABLE `yxshop_admin_logs` MODIFY COLUMN `id` bigint unsigned NOT NULL AUTO_INCREMENT;
ALTER TABLE `yxshop_agent_capitals` MODIFY COLUMN `id` bigint unsigned NOT NULL AUTO_INCREMENT;
ALTER TABLE `yxshop_agent_withdraws` MODIFY COLUMN `id` bigint unsigned NOT NULL AUTO_INCREMENT;
ALTER TABLE `yxshop_app_navbars` MODIFY COLUMN `id` bigint unsigned NOT NULL AUTO_INCREMENT;
ALTER TABLE `yxshop_app_pages` MODIFY COLUMN `id` bigint unsigned NOT NULL AUTO_INCREMENT;
ALTER TABLE `yxshop_app_prepay_ids` MODIFY COLUMN `id` bigint unsigned NOT NULL AUTO_INCREMENT;
ALTER TABLE `yxshop_applies` MODIFY COLUMN `id` bigint unsigned NOT NULL AUTO_INCREMENT;
ALTER TABLE `yxshop_coupon_goods` MODIFY COLUMN `id` bigint unsigned NOT NULL AUTO_INCREMENT;
ALTER TABLE `yxshop_coupon_items` MODIFY COLUMN `id` bigint unsigned NOT NULL AUTO_INCREMENT;
ALTER TABLE `yxshop_data_change_logs` MODIFY COLUMN `id` bigint unsigned NOT NULL AUTO_INCREMENT;
ALTER TABLE `yxshop_dealer_referees` MODIFY COLUMN `id` bigint unsigned NOT NULL AUTO_INCREMENT;
ALTER TABLE `yxshop_delivery_rules` MODIFY COLUMN `id` bigint unsigned NOT NULL AUTO_INCREMENT;
ALTER TABLE `yxshop_file_logs` MODIFY COLUMN `id` bigint unsigned NOT NULL AUTO_INCREMENT;
ALTER TABLE `yxshop_integral_log` MODIFY COLUMN `id` bigint unsigned NOT NULL AUTO_INCREMENT;
ALTER TABLE `yxshop_integral_logs` MODIFY COLUMN `id` bigint unsigned NOT NULL AUTO_INCREMENT;
ALTER TABLE `yxshop_item_searches` MODIFY COLUMN `id` bigint unsigned NOT NULL AUTO_INCREMENT;
ALTER TABLE `yxshop_item_spec_price_histories` MODIFY COLUMN `id` bigint unsigned NOT NULL AUTO_INCREMENT;
ALTER TABLE `yxshop_item_spec_sales` MODIFY COLUMN `id` bigint unsigned NOT NULL AUTO_INCREMENT;
ALTER TABLE `yxshop_item_spec_stock_logs` MODIFY COLUMN `id` bigint unsigned NOT NULL AUTO_INCREMENT;
ALTER TABLE `yxshop_item_stock_warnings` MODIFY COLUMN `id` bigint unsigned NOT NULL AUTO_INCREMENT;
ALTER TABLE `yxshop_item_tag_relations` MODIFY COLUMN `id` bigint unsigned NOT NULL AUTO_INCREMENT;
ALTER TABLE `yxshop_item_views` MODIFY COLUMN `id` bigint unsigned NOT NULL AUTO_INCREMENT;
ALTER TABLE `yxshop_notification_blacklists` MODIFY COLUMN `id` bigint unsigned NOT NULL AUTO_INCREMENT;
ALTER TABLE `yxshop_notification_queues` MODIFY COLUMN `id` bigint unsigned NOT NULL AUTO_INCREMENT;
ALTER TABLE `yxshop_notification_scene_templates` MODIFY COLUMN `id` bigint unsigned NOT NULL AUTO_INCREMENT;
ALTER TABLE `yxshop_notification_statistics` MODIFY COLUMN `id` bigint unsigned NOT NULL AUTO_INCREMENT;
ALTER TABLE `yxshop_notification_template_variables` MODIFY COLUMN `id` bigint unsigned NOT NULL AUTO_INCREMENT;
ALTER TABLE `yxshop_notification_variables` MODIFY COLUMN `id` bigint unsigned NOT NULL AUTO_INCREMENT;
ALTER TABLE `yxshop_order_agents` MODIFY COLUMN `id` bigint unsigned NOT NULL AUTO_INCREMENT;
ALTER TABLE `yxshop_order_deliveries` MODIFY COLUMN `id` bigint unsigned NOT NULL AUTO_INCREMENT;
ALTER TABLE `yxshop_point_tasks` MODIFY COLUMN `id` bigint unsigned NOT NULL AUTO_INCREMENT;
ALTER TABLE `yxshop_profit_logs` MODIFY COLUMN `id` bigint unsigned NOT NULL AUTO_INCREMENT;
ALTER TABLE `yxshop_regions` MODIFY COLUMN `id` bigint unsigned NOT NULL AUTO_INCREMENT;
ALTER TABLE `yxshop_schema_migrations` MODIFY COLUMN `id` bigint unsigned NOT NULL AUTO_INCREMENT;
ALTER TABLE `yxshop_tenant_plans` MODIFY COLUMN `id` bigint unsigned NOT NULL AUTO_INCREMENT;
ALTER TABLE `yxshop_user_money_log` MODIFY COLUMN `id` bigint unsigned NOT NULL AUTO_INCREMENT;
ALTER TABLE `yxshop_user_money_logs` MODIFY COLUMN `id` bigint unsigned NOT NULL AUTO_INCREMENT;
ALTER TABLE `yxshop_user_point_tasks` MODIFY COLUMN `id` bigint unsigned NOT NULL AUTO_INCREMENT;
ALTER TABLE `yxshop_user_withdraws` MODIFY COLUMN `id` bigint unsigned NOT NULL AUTO_INCREMENT;

-- 签到表主键
ALTER TABLE `yxshop_signs` MODIFY COLUMN `id` bigint unsigned NOT NULL AUTO_INCREMENT;


-- ============================================================
-- 修复：用户等级字段 + 外键列统一为 bigint（配合雪花 ID）
-- yxshop_user_levels 原表只有 integral/agio/sort 等旧字段，
-- 而模型/后台/小程序使用 level/experience/discount/status/is_default/description；
-- users.level_id 原为 tinyint(2)，雪花等级 ID 无法写入（Out of range）。
-- ============================================================
CREATE TABLE IF NOT EXISTS `yxshop_user_levels` (
  `id` bigint unsigned NOT NULL,
  `level` tinyint unsigned NOT NULL DEFAULT 1 COMMENT '等级序号',
  `experience` int unsigned NOT NULL DEFAULT 0 COMMENT '升级所需经验/积分',
  `discount` decimal(5,2) NOT NULL DEFAULT 10.00 COMMENT '折扣（10=不打折）',
  `status` tinyint unsigned NOT NULL DEFAULT 1 COMMENT '1启用 0禁用',
  `is_default` tinyint unsigned NOT NULL DEFAULT 0 COMMENT '是否默认等级',
  `description` varchar(255) NOT NULL DEFAULT '' COMMENT '等级说明',
  `integral` int unsigned NOT NULL DEFAULT 0,
  `app_id` bigint unsigned NOT NULL DEFAULT 0,
  `created_at` int unsigned NOT NULL DEFAULT 0,
  `updated_at` int unsigned NOT NULL DEFAULT 0,
  `key` varchar(100) NOT NULL DEFAULT '',
  `values` varchar(100) DEFAULT NULL,
  `agio` decimal(5,2) DEFAULT 10.00 COMMENT '折扣（旧字段，与 discount 同步）',
  `name` varchar(100) DEFAULT NULL,
  `sort` int DEFAULT NULL,
  `desc` varchar(255) DEFAULT NULL,
  `shop_money` int NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_app_level` (`app_id`, `level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='等级表';

CALL yxshop_add_column_if_missing('yxshop_user_levels', 'level', "tinyint unsigned NOT NULL DEFAULT 1 COMMENT '等级序号'");
CALL yxshop_add_column_if_missing('yxshop_user_levels', 'experience', "int unsigned NOT NULL DEFAULT 0 COMMENT '升级所需经验/积分'");
CALL yxshop_add_column_if_missing('yxshop_user_levels', 'discount', "decimal(5,2) NOT NULL DEFAULT 10.00 COMMENT '折扣（10=不打折）'");
CALL yxshop_add_column_if_missing('yxshop_user_levels', 'status', "tinyint unsigned NOT NULL DEFAULT 1 COMMENT '1启用 0禁用'");
CALL yxshop_add_column_if_missing('yxshop_user_levels', 'is_default', "tinyint unsigned NOT NULL DEFAULT 0 COMMENT '是否默认等级'");
CALL yxshop_add_column_if_missing('yxshop_user_levels', 'description', "varchar(255) NOT NULL DEFAULT '' COMMENT '等级说明'");
CALL yxshop_add_index_if_missing('yxshop_user_levels', 'idx_app_level', "`app_id`, `level`");
ALTER TABLE `yxshop_user_levels` MODIFY COLUMN `key` varchar(100) NOT NULL DEFAULT '';
ALTER TABLE `yxshop_user_levels` MODIFY COLUMN `shop_money` int NOT NULL DEFAULT 0;

-- 旧数据回填（只处理未配置过的行，避免覆盖后台已调整的数据）
SET @lv := 0;
UPDATE `yxshop_user_levels` SET `level` = (@lv := @lv + 1) WHERE `description` = '' ORDER BY `sort` DESC;
UPDATE `yxshop_user_levels` SET `experience` = `integral` WHERE `experience` = 0 AND `integral` > 0;
UPDATE `yxshop_user_levels` SET `discount` = `agio` WHERE `discount` = 10.00 AND `agio` IS NOT NULL AND `agio` <> 10.00;
UPDATE `yxshop_user_levels` SET `description` = COALESCE(`desc`, '') WHERE `description` = '' AND `desc` IS NOT NULL AND `desc` <> '';
UPDATE `yxshop_user_levels` SET `is_default` = 1 WHERE `level` = 1 AND `is_default` = 0;

-- 外键列统一 bigint unsigned（雪花 ID 超出 int 范围）
ALTER TABLE `yxshop_admins` MODIFY COLUMN `pid` bigint unsigned NULL DEFAULT 0;
ALTER TABLE `yxshop_coupons` MODIFY COLUMN `member_level_id` bigint unsigned NOT NULL DEFAULT 1;
ALTER TABLE `yxshop_item_attributes` MODIFY COLUMN `type_id` bigint unsigned NOT NULL DEFAULT 0;
ALTER TABLE `yxshop_item_attrs` MODIFY COLUMN `goods_id` bigint unsigned NOT NULL DEFAULT 0;
ALTER TABLE `yxshop_item_attrs` MODIFY COLUMN `attr_id` bigint unsigned NOT NULL DEFAULT 0;
ALTER TABLE `yxshop_items` MODIFY COLUMN `type_id` bigint unsigned NOT NULL DEFAULT 0;
ALTER TABLE `yxshop_items` MODIFY COLUMN `member_level_id` bigint unsigned NOT NULL DEFAULT 1;
ALTER TABLE `yxshop_mini_page_templates` MODIFY COLUMN `app_id` bigint unsigned NOT NULL DEFAULT 0;
ALTER TABLE `yxshop_mini_page_versions` MODIFY COLUMN `app_id` bigint unsigned NOT NULL DEFAULT 0;
ALTER TABLE `yxshop_mini_pages` MODIFY COLUMN `app_id` bigint unsigned NOT NULL DEFAULT 0;
ALTER TABLE `yxshop_mini_tab_bars` MODIFY COLUMN `app_id` bigint unsigned NOT NULL DEFAULT 0;
ALTER TABLE `yxshop_mini_themes` MODIFY COLUMN `app_id` bigint unsigned NOT NULL DEFAULT 0;
ALTER TABLE `yxshop_point_tasks` MODIFY COLUMN `app_id` bigint unsigned NOT NULL DEFAULT 0;
ALTER TABLE `yxshop_regions` MODIFY COLUMN `pid` bigint unsigned NULL;
ALTER TABLE `yxshop_tenant_subscriptions` MODIFY COLUMN `plan_id` bigint unsigned NOT NULL DEFAULT 0;
ALTER TABLE `yxshop_tenants` MODIFY COLUMN `plan_id` bigint unsigned NOT NULL DEFAULT 0;
ALTER TABLE `yxshop_tenants` MODIFY COLUMN `app_id` bigint unsigned NOT NULL DEFAULT 0;
ALTER TABLE `yxshop_user_coupons` MODIFY COLUMN `level_id` bigint unsigned NOT NULL DEFAULT 1;
ALTER TABLE `yxshop_user_point_tasks` MODIFY COLUMN `user_id` bigint unsigned NOT NULL DEFAULT 0;
ALTER TABLE `yxshop_user_point_tasks` MODIFY COLUMN `task_id` bigint unsigned NOT NULL DEFAULT 0;
ALTER TABLE `yxshop_user_point_tasks` MODIFY COLUMN `app_id` bigint unsigned NOT NULL DEFAULT 0;
ALTER TABLE `yxshop_user_withdraws` MODIFY COLUMN `user_id` bigint unsigned NOT NULL DEFAULT 0;
ALTER TABLE `yxshop_user_withdraws` MODIFY COLUMN `app_id` bigint unsigned NOT NULL DEFAULT 0;
ALTER TABLE `yxshop_users` MODIFY COLUMN `pid` bigint unsigned NOT NULL DEFAULT 0;
ALTER TABLE `yxshop_users` MODIFY COLUMN `level_id` bigint unsigned NULL DEFAULT 1;

-- 清理工具存储过程
DROP PROCEDURE IF EXISTS `yxshop_add_column_if_missing`;
DROP PROCEDURE IF EXISTS `yxshop_add_index_if_missing`;

-- 记录版本
INSERT IGNORE INTO `yxshop_schema_migrations`(`version`, `name`, `executed_at`)
VALUES ('20260622_01', 'charset_softdelete', UNIX_TIMESTAMP());

-- ---- 迁移: migration_02_field_alignment.sql ----
-- ============================================================
-- Migration 20260622_02: field_alignment
-- 表字段与代码对齐。原则：表已上线有数据，优先补列；代码侧另改。
-- 幂等可重复执行（MySQL 5.7/8.0 兼容）
-- ============================================================

DROP PROCEDURE IF EXISTS `yxshop_add_column_if_missing`;
DELIMITER //
CREATE PROCEDURE `yxshop_add_column_if_missing`(
  IN p_table VARCHAR(100),
  IN p_column VARCHAR(100),
  IN p_definition TEXT
)
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table AND COLUMN_NAME = p_column
  ) THEN
    SET @ddl = CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN `', p_column, '` ', p_definition);
    PREPARE s FROM @ddl; EXECUTE s; DEALLOCATE PREPARE s;
  END IF;
END//
DELIMITER ;

-- ------------------------------------------------------------
-- 1. yxshop_users 补列（UserAuthService::wxLogin 写 unionid/email）
-- ------------------------------------------------------------
CALL yxshop_add_column_if_missing('yxshop_users', 'unionid', "varchar(128) NOT NULL DEFAULT '' COMMENT '微信unionid(多应用唯一)' AFTER `openid`");
CALL yxshop_add_column_if_missing('yxshop_users', 'email', "varchar(128) NULL DEFAULT NULL COMMENT '邮箱' AFTER `phone`");

-- password_hash 输出 60 字符，原 varchar(32) 不够；改 varchar(255) 兼容未来更长 hash
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_users` MODIFY COLUMN `password` VARCHAR(255) NULL DEFAULT NULL COMMENT '密码哈希(bcrypt/argon2)';

-- openid 改为允许 NULL：账号密码注册用户没有微信 openid，原 NOT NULL DEFAULT ''
-- 导致第二个本地用户因唯一索引冲突无法注册
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_users` MODIFY COLUMN `openid` VARCHAR(128) NULL DEFAULT NULL COMMENT '微信openid(账号密码注册用户为NULL)';

-- yxshop_item_favorites / notification_sends 补 updated_at（BaseModel timestamps=true 默认会写这个字段）
CALL yxshop_add_column_if_missing('yxshop_item_favorites', 'updated_at', "int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间' AFTER `created_at`");
CALL yxshop_add_column_if_missing('yxshop_notification_sends', 'updated_at', "int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间' AFTER `created_at`");

-- unionid 辅助索引（同 unionid 跨应用查用户）
DROP PROCEDURE IF EXISTS `yxshop_add_index_if_missing`;
DELIMITER //
CREATE PROCEDURE `yxshop_add_index_if_missing`(
  IN p_table VARCHAR(100), IN p_index VARCHAR(100), IN p_columns TEXT
)
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table AND INDEX_NAME = p_index
  ) THEN
    SET @ddl = CONCAT('ALTER TABLE `', p_table, '` ADD INDEX `', p_index, '` (', p_columns, ')');
    PREPARE s FROM @ddl; EXECUTE s; DEALLOCATE PREPARE s;
  END IF;
END//
DELIMITER ;
CALL yxshop_add_index_if_missing('yxshop_users', 'idx_unionid', '`app_id`, `unionid`');

-- ------------------------------------------------------------
-- 2. yxshop_user_coupons 补列
--    代码 UserCoupon 模型用 status(0未用1已用2过期)/order_id/use_time
--    表只有 is_use/is_expire。补 status/order_id/use_time，
--    并做一次性数据迁移：is_use=1 -> status=1
-- ------------------------------------------------------------
CALL yxshop_add_column_if_missing('yxshop_user_coupons', 'status', "tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态(0未使用 1已使用 2已过期)' AFTER `is_expire`");
CALL yxshop_add_column_if_missing('yxshop_user_coupons', 'order_id', "bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '使用的订单ID' AFTER `status`");
CALL yxshop_add_column_if_missing('yxshop_user_coupons', 'use_time', "int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '使用时间' AFTER `order_id`");

-- 数据迁移：is_use=1 的记录回填 status=1
UPDATE `yxshop_user_coupons` SET `status` = 1 WHERE `is_use` = 1 AND `status` = 0;
UPDATE `yxshop_user_coupons` SET `status` = 2 WHERE `is_expire` = 1 AND `status` = 0;
CALL yxshop_add_index_if_missing('yxshop_user_coupons', 'idx_coupon_id', '`coupon_id`');
CALL yxshop_add_index_if_missing('yxshop_user_coupons', 'idx_order_id', '`order_id`');

-- ------------------------------------------------------------
-- 3. yxshop_integral_log 字段对齐
--    表用 amount/before_balance/after_balance（语义清晰，保留）
--    代码 IntegralService 写 give_integral —— 改代码对齐表（见 app/service/IntegralService.php）
--    表侧：amount 类型统一为 int（积分通常整数），加 note 索引
-- ------------------------------------------------------------
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_integral_log` MODIFY COLUMN `amount` int(11) NOT NULL DEFAULT 0 COMMENT '积分变动数量(正获得负消费)';
CALL yxshop_add_index_if_missing('yxshop_integral_log', 'idx_user_type', '`user_id`, `type`');

-- ------------------------------------------------------------
-- 4. yxshop_orders 补退款金额独立列（现退款金额混在 payments，无法支持部分退款）
-- ------------------------------------------------------------
CALL yxshop_add_column_if_missing('yxshop_orders', 'refund_amount', "decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '已退款金额(累计)' AFTER `refund_status`");
CALL yxshop_add_index_if_missing('yxshop_orders', 'idx_refund_status', '`refund_status`');

-- ------------------------------------------------------------
-- 5. yxshop_user_addresses —— 采用 B 方案
--    表已是 name/phone/province_id/city_id/district_id/detail，符合 B 方案。
--    仅补 deleted_at（M1 已补）+ user_id 索引（M4 补）。
--    代码侧 UserAddressService/Controller 改对齐（见代码迁移）。
-- ------------------------------------------------------------

-- yxshop_orders 历史营销字段原本 NOT NULL 无默认，普通下单不传会 1364 报错
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_orders` MODIFY COLUMN `prom_type` INT(2) NOT NULL DEFAULT 0 COMMENT '订单分类：0普通 1拼团 2秒杀 3抢购';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_orders` MODIFY COLUMN `prom_num` INT(2) NOT NULL DEFAULT 0 COMMENT '参团人数';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_orders` MODIFY COLUMN `prom_statis` INT(2) NOT NULL DEFAULT 0 COMMENT '拼团状态';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_orders` MODIFY COLUMN `prom_time` INT(3) NOT NULL DEFAULT 0 COMMENT '拼团限时';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_orders` MODIFY COLUMN `item_id` BIGINT(20) NOT NULL DEFAULT 0 COMMENT '兼容字段(多商品订单不用)';

DROP PROCEDURE IF EXISTS `yxshop_add_column_if_missing`;
DROP PROCEDURE IF EXISTS `yxshop_add_index_if_missing`;

INSERT IGNORE INTO `yxshop_schema_migrations`(`version`, `name`, `executed_at`)
VALUES ('20260622_02', 'field_alignment', UNIX_TIMESTAMP());

-- ---- 迁移: migration_03_missing_tables.sql ----
-- ============================================================
-- Migration 20260622_03: missing_tables
-- 新建代码引用但 DB 缺失的表，以及企业级电商缺失的关键表。
-- 全部 utf8mb4，主键 bigint（与 Snowflake 体系一致），软删除字段。
-- 幂等：CREATE TABLE IF NOT EXISTS
-- ============================================================

-- ------------------------------------------------------------
-- 1. yxshop_carts 购物车（Cart Model 引用，表缺失，核心功能）
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `yxshop_carts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `item_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '商品ID',
  `item_name` varchar(255) NOT NULL DEFAULT '' COMMENT '商品名称快照',
  `item_image` varchar(500) NOT NULL DEFAULT '' COMMENT '商品主图快照',
  `spec_key` varchar(100) NOT NULL DEFAULT '' COMMENT '规格组合key',
  `spec_key_name` varchar(255) NOT NULL DEFAULT '' COMMENT '规格名称快照',
  `price` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '加入时单价',
  `quantity` int(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT '数量',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `uk_user_item_spec`(`user_id`, `item_id`, `spec_key`),
  INDEX `idx_user_id`(`user_id`),
  INDEX `idx_app_user`(`app_id`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='购物车表';

-- ------------------------------------------------------------
-- 2. yxshop_card 虚拟商品卡密（Card Model 引用，表缺失）
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `yxshop_card` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '卡密名称',
  `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '所属用户(0未发放)',
  `card` varchar(255) NOT NULL DEFAULT '' COMMENT '卡号',
  `key` varchar(255) NOT NULL DEFAULT '' COMMENT '卡密',
  `nickName` varchar(100) NOT NULL DEFAULT '' COMMENT '持有人昵称',
  `card_type` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '卡密类型(10卡密 20链接)',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态(0未用 1已用 2已禁用)',
  `balance` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '余额',
  `discount` decimal(5,2) NOT NULL DEFAULT 10.00 COMMENT '折扣',
  `item_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联商品',
  `used_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '使用时间',
  `expire_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '过期时间',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  INDEX `idx_user_id`(`user_id`),
  INDEX `idx_item_id`(`item_id`),
  INDEX `idx_status`(`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='虚拟商品卡密表';

-- ------------------------------------------------------------
-- 3. yxshop_packages 套餐（Package Model 引用，表缺失）
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `yxshop_packages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL DEFAULT '' COMMENT '套餐名称',
  `type` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '类型(1固定套餐 2自定义)',
  `items` mediumtext NULL COMMENT '包含商品JSON [{item_id, quantity}]',
  `price` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '套餐价',
  `original_price` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '原价',
  `start_time` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `end_time` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态(0下架 1上架)',
  `sort` int(11) NOT NULL DEFAULT 50,
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  INDEX `idx_app_status`(`app_id`, `status`),
  INDEX `idx_sort`(`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='商品套餐表';

-- ------------------------------------------------------------
-- 4. yxshop_user_integral_accounts 积分账户表
--    与 user_money_log + users.money 对称，独立积分账户
--    （可用/冻结/累计获得/累计消耗）
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `yxshop_user_integral_accounts` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `available` int(11) NOT NULL DEFAULT 0 COMMENT '可用积分',
  `frozen` int(11) NOT NULL DEFAULT 0 COMMENT '冻结积分',
  `total_earned` int(11) NOT NULL DEFAULT 0 COMMENT '累计获得',
  `total_consumed` int(11) NOT NULL DEFAULT 0 COMMENT '累计消耗',
  `version` int(11) NOT NULL DEFAULT 0 COMMENT '乐观锁版本号',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `uk_user_app`(`user_id`, `app_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='用户积分账户表';

-- 从 users.integral 初始化账户（一次性数据迁移）
INSERT IGNORE INTO `yxshop_user_integral_accounts`(`user_id`, `app_id`, `available`, `total_earned`, `created_at`, `updated_at`)
SELECT `id`, `app_id`, COALESCE(CAST(`integral` AS UNSIGNED), 0), COALESCE(CAST(`integral` AS UNSIGNED), 0), `created_at`, `updated_at`
FROM `yxshop_users`
WHERE `is_delete` = 0 OR `deleted_at` = 0;

-- ------------------------------------------------------------
-- 5. yxshop_refund_records 退款记录表
--    支持部分退款、多次退款（现退款信息散在 orders/payments）
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `yxshop_refund_records` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `refund_no` varchar(32) NOT NULL DEFAULT '' COMMENT '退款单号',
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `order_no` varchar(32) NOT NULL DEFAULT '',
  `payment_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '原支付单ID',
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '退款金额',
  `reason` varchar(500) NOT NULL DEFAULT '' COMMENT '退款原因',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '状态(10申请中 20已退款 30已拒绝 40已取消)',
  `refund_type` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '退款类型(10仅退款 20退货退款)',
  `transaction_id` varchar(64) NOT NULL DEFAULT '' COMMENT '第三方退款单号',
  `operator_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `operator_name` varchar(100) NOT NULL DEFAULT '',
  `handled_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '处理时间',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `uk_refund_no`(`refund_no`),
  INDEX `idx_order_id`(`order_id`),
  INDEX `idx_payment_id`(`payment_id`),
  INDEX `idx_user_id`(`user_id`),
  INDEX `idx_status`(`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='退款记录表';

-- ------------------------------------------------------------
-- 6. yxshop_settlements 结算单表（分销/商户分账）
--    OrderAgent::settle 现只改状态加钱，无结算单留痕
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `yxshop_settlements` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `settlement_no` varchar(32) NOT NULL DEFAULT '' COMMENT '结算单号',
  `user_id` bigint(20) UNSIGNED NOT NULL COMMENT '被结算人(分销商/商户)',
  `order_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `order_agent_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联分销记录',
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '结算金额',
  `type` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '结算类型(10分销佣金 20商户分账)',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '状态(10待结算 20已结算 30已撤销)',
  `settled_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `uk_settlement_no`(`settlement_no`),
  INDEX `idx_user_id`(`user_id`),
  INDEX `idx_order_id`(`order_id`),
  INDEX `idx_status`(`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='结算单表';

-- ------------------------------------------------------------
-- 7. yxshop_delivery_tracks 物流轨迹表（DeliveryTrackService 引用，表缺失）
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `yxshop_delivery_tracks` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `order_delivery_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `express_code` varchar(50) NOT NULL DEFAULT '' COMMENT '快递公司编码',
  `express_no` varchar(64) NOT NULL DEFAULT '' COMMENT '快递单号',
  `content` text NULL COMMENT '轨迹内容JSON',
  `location` varchar(255) NOT NULL DEFAULT '' COMMENT '所在位置',
  `event_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '轨迹事件时间',
  `is_last` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否最新轨迹',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  INDEX `idx_order_id`(`order_id`),
  INDEX `idx_express_no`(`express_no`),
  INDEX `idx_event_time`(`event_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='物流轨迹表';

-- ------------------------------------------------------------
-- 8. yxshop_coupon_receive_logs 领券记录（防超领审计）
--    现只有 user_coupons 结果表，无领取流水
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `yxshop_coupon_receive_logs` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `coupon_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `user_coupon_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '生成的user_coupon记录',
  `source` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT '领取来源(10主动 20后台发放 30活动)',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  INDEX `idx_coupon_user`(`coupon_id`, `user_id`),
  INDEX `idx_user_id`(`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='优惠券领取流水';

-- ------------------------------------------------------------
-- 9. yxshop_services + 两张关联子表整体重命名
--    原表名 "services" 含义模糊，实际承载的是售后申请（sub_status/sub_type/refund_id/express_no），
--    与 ServiceService（服务层基类）重名严重，引发架构混乱。
--    关联表：
--      yxshop_service_images   (图片附件，外键 service_id)
--      yxshop_service_details  (处理细节/沟通记录，外键 service_id)
--    一并重命名为 after_sales_* 体系，外键 service_id 同步改名 after_sales_id。
--    直接 RENAME（而非视图）：避免「读视图/写表」的双逻辑，外键索引保留。
--    幂等：仅当源表存在且目标表不存在时执行。
-- ------------------------------------------------------------

-- 主表：yxshop_services -> yxshop_after_sales
SET @rename_after_sales = (
  SELECT CASE
    WHEN EXISTS (SELECT 1 FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'yxshop_services'
                   AND TABLE_TYPE = 'BASE TABLE')
     AND NOT EXISTS (SELECT 1 FROM information_schema.TABLES
                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'yxshop_after_sales')
    THEN 'RENAME TABLE `yxshop_services` TO `yxshop_after_sales`'
    ELSE 'SELECT "yxshop_after_sales already renamed or services missing"'
  END
);
PREPARE s FROM @rename_after_sales; EXECUTE s; DEALLOCATE PREPARE s;

-- 图片表：yxshop_service_images -> yxshop_after_sales_images
SET @rename_images = (
  SELECT CASE
    WHEN EXISTS (SELECT 1 FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'yxshop_service_images'
                   AND TABLE_TYPE = 'BASE TABLE')
     AND NOT EXISTS (SELECT 1 FROM information_schema.TABLES
                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'yxshop_after_sales_images')
    THEN 'RENAME TABLE `yxshop_service_images` TO `yxshop_after_sales_images`'
    ELSE 'SELECT "yxshop_after_sales_images already renamed or source missing"'
  END
);
PREPARE s FROM @rename_images; EXECUTE s; DEALLOCATE PREPARE s;

-- 详情表：yxshop_service_details -> yxshop_after_sales_details
SET @rename_details = (
  SELECT CASE
    WHEN EXISTS (SELECT 1 FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'yxshop_service_details'
                   AND TABLE_TYPE = 'BASE TABLE')
     AND NOT EXISTS (SELECT 1 FROM information_schema.TABLES
                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'yxshop_after_sales_details')
    THEN 'RENAME TABLE `yxshop_service_details` TO `yxshop_after_sales_details`'
    ELSE 'SELECT "yxshop_after_sales_details already renamed or source missing"'
  END
);
PREPARE s FROM @rename_details; EXECUTE s; DEALLOCATE PREPARE s;

-- 外键列：service_id -> after_sales_id（两张子表都改）
DROP PROCEDURE IF EXISTS `yxshop_rename_service_id`;
DELIMITER //
CREATE PROCEDURE `yxshop_rename_service_id`(IN p_table VARCHAR(100))
BEGIN
  IF EXISTS (SELECT 1 FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table)
     AND EXISTS (SELECT 1 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table
                   AND COLUMN_NAME = 'service_id')
     AND NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table
                       AND COLUMN_NAME = 'after_sales_id') THEN
    SET @ddl = CONCAT('ALTER TABLE `', p_table, '` CHANGE COLUMN `service_id` `after_sales_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT ''关联售后主表ID''');
    PREPARE s FROM @ddl; EXECUTE s; DEALLOCATE PREPARE s;
  END IF;
END//
DELIMITER ;

CALL yxshop_rename_service_id('yxshop_after_sales_images');
CALL yxshop_rename_service_id('yxshop_after_sales_details');
DROP PROCEDURE `yxshop_rename_service_id`;

-- 子表补外键索引（原表无 service_id 索引，高频 JOIN 必备）
DROP PROCEDURE IF EXISTS `yxshop_add_idx_after_sales_id`;
DELIMITER //
CREATE PROCEDURE `yxshop_add_idx_after_sales_id`(IN p_table VARCHAR(100))
BEGIN
  IF EXISTS (SELECT 1 FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table)
     AND NOT EXISTS (SELECT 1 FROM information_schema.STATISTICS
                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table
                       AND INDEX_NAME = 'idx_after_sales_id') THEN
    SET @ddl = CONCAT('ALTER TABLE `', p_table, '` ADD INDEX `idx_after_sales_id` (`after_sales_id`)');
    PREPARE s FROM @ddl; EXECUTE s; DEALLOCATE PREPARE s;
  END IF;
END//
DELIMITER ;

CALL yxshop_add_idx_after_sales_id('yxshop_after_sales_images');
CALL yxshop_add_idx_after_sales_id('yxshop_after_sales_details');
DROP PROCEDURE `yxshop_add_idx_after_sales_id`;

-- ------------------------------------------------------------
-- 10. yxshop_idempotency_records 幂等表（支付回调/下单防重复提交）
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `yxshop_idempotency_records` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `key` varchar(128) NOT NULL COMMENT '幂等key(如支付回调out_trade_no)',
  `scope` varchar(50) NOT NULL DEFAULT '' COMMENT '作用域(payment/order/...)',
  `response` mediumtext NULL COMMENT '首次响应缓存',
  `expires_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '过期时间',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `uk_key`(`key`),
  INDEX `idx_expires_at`(`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='幂等记录表';

INSERT IGNORE INTO `yxshop_schema_migrations`(`version`, `name`, `executed_at`)
VALUES ('20260622_03', 'missing_tables', UNIX_TIMESTAMP());

-- ---- 迁移: migration_04_indexes_types_cleanup.sql ----
-- ============================================================
-- Migration 20260622_04: indexes_types_cleanup
-- 1) 补外键索引（高频 JOIN 慢查询）
-- 2) float 资金字段改 decimal
-- 3) 日志类表主键改 AUTO_INCREMENT（Snowflake 无必要）
-- 幂等
-- ============================================================

DROP PROCEDURE IF EXISTS `yxshop_add_index_if_missing`;
DELIMITER //
CREATE PROCEDURE `yxshop_add_index_if_missing`(
  IN p_table VARCHAR(100), IN p_index VARCHAR(100), IN p_columns TEXT
)
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table AND INDEX_NAME = p_index
  ) THEN
    SET @ddl = CONCAT('ALTER TABLE `', p_table, '` ADD INDEX `', p_index, '` (', p_columns, ')');
    PREPARE s FROM @ddl; EXECUTE s; DEALLOCATE PREPARE s;
  END IF;
END//
DELIMITER ;

-- ------------------------------------------------------------
-- 1. 补外键索引
-- ------------------------------------------------------------
-- order_deliveries: order_id 无索引
CALL yxshop_add_index_if_missing('yxshop_order_deliveries', 'idx_order_id', '`order_id`');
-- user_addresses: user_id / app_id 无索引
CALL yxshop_add_index_if_missing('yxshop_user_addresses', 'idx_user_id', '`user_id`');
CALL yxshop_add_index_if_missing('yxshop_user_addresses', 'idx_app_user', '`app_id`, `user_id`');
-- user_coupons: coupon_id 无索引（M2 已补 idx_coupon_id，此处幂等跳过）
CALL yxshop_add_index_if_missing('yxshop_user_coupons', 'idx_user_coupon', '`user_id`, `coupon_id`');
-- order_goods: order_id 无索引（只有 item_id）
CALL yxshop_add_index_if_missing('yxshop_order_goods', 'idx_order_id', '`order_id`');
-- order_goods: user_id 单列（现只有 app+user 复合）
CALL yxshop_add_index_if_missing('yxshop_order_goods', 'idx_user_id', '`user_id`');
-- integral_log: 已补 user+type（M2）
-- user_money_log: type 索引已有，补 app
CALL yxshop_add_index_if_missing('yxshop_user_money_log', 'idx_app_user', '`app_id`, `user_id`');

-- ------------------------------------------------------------
-- 2. float 资金字段改 decimal（精度安全）
-- ------------------------------------------------------------
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_items` MODIFY COLUMN `discount` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT '折扣';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_orders` MODIFY COLUMN `rebate` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT '折扣';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_order_agents` MODIFY COLUMN `profit_money` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '分成收益';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_profit_logs` MODIFY COLUMN `profit_money` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '分成收益';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_prom_orders` MODIFY COLUMN `money` decimal(10,2) NULL DEFAULT 0.00 COMMENT '最小金额';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_users` MODIFY COLUMN `agio` decimal(5,2) NOT NULL DEFAULT 10.00 COMMENT '折扣';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_user_levels` MODIFY COLUMN `agio` decimal(5,2) NULL DEFAULT 10.00 COMMENT '扣折';
-- users.integral 类型统一为 int（积分通常整数，与 integral_log 对齐）
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_users` MODIFY COLUMN `integral` int(11) NOT NULL DEFAULT 0 COMMENT '用户积分(余额见 user_integral_accounts)';
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_users` MODIFY COLUMN `freeze_integral` int(11) NOT NULL DEFAULT 0 COMMENT '冻结积分';

-- ------------------------------------------------------------
-- 3. 日志类表主键改 AUTO_INCREMENT（原 Snowflake bigint 无必要，增加复杂度）
--    仅对纯日志表（无外键引用、高写入）操作。
--    注意：这些表 id 原本就无 AUTO_INCREMENT，且无数据引用，可安全改。
--    若已有数据，需先确保 id 无重复再改（此处假设可安全执行）。
-- ------------------------------------------------------------
-- yxshop_admin_logs 已是 AUTO_INCREMENT，跳过
-- yxshop_user_logs 已是 AUTO_INCREMENT，跳过
-- yxshop_order_logs 已是 AUTO_INCREMENT，跳过
-- yxshop_payment_logs 已是 AUTO_INCREMENT，跳过
-- yxshop_login_logs / sms_logs / email_logs / system_logs / data_change_logs 检查并改：
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_login_logs` MODIFY COLUMN `log_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_sms_logs` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_email_logs` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_system_logs` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_data_change_logs` MODIFY COLUMN `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

DROP PROCEDURE IF EXISTS `yxshop_add_index_if_missing`;

INSERT IGNORE INTO `yxshop_schema_migrations`(`version`, `name`, `executed_at`)
VALUES ('20260622_04', 'indexes_types_cleanup', UNIX_TIMESTAMP());

-- ---- 迁移: migration_05_add_missing_app_id.sql ----
-- ============================================================
-- Migration 20260624_05: add_missing_app_id_columns
--
-- 缘起：Sprint 4 落地后联调发现 yxshop_advertisements / yxshop_app_navbars
--      代码假设有 app_id 字段（多租户隔离），但表里没有 → SQL 报错。
--
-- 处理：业务多租户表补 app_id；纯字典表（currencies/languages/regions）保留
--      无 app_id 设计，由 Service 端约定调用 `paginatedByApp(0)` 不过滤。
-- 幂等：可重复执行
-- ============================================================

-- 工具存储过程：补字段（与 migration_01 同款）
DROP PROCEDURE IF EXISTS `yxshop_add_column_if_missing`;
DELIMITER //
CREATE PROCEDURE `yxshop_add_column_if_missing`(
  IN p_table VARCHAR(100),
  IN p_column VARCHAR(100),
  IN p_definition TEXT
)
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table AND COLUMN_NAME = p_column
  ) THEN
    SET @ddl = CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN `', p_column, '` ', p_definition);
    PREPARE s FROM @ddl; EXECUTE s; DEALLOCATE PREPARE s;
  END IF;
END//
DELIMITER ;

-- 工具存储过程：补索引
DROP PROCEDURE IF EXISTS `yxshop_add_index_if_missing`;
DELIMITER //
CREATE PROCEDURE `yxshop_add_index_if_missing`(
  IN p_table VARCHAR(100), IN p_index VARCHAR(100), IN p_columns TEXT
)
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table AND INDEX_NAME = p_index
  ) THEN
    SET @ddl = CONCAT('ALTER TABLE `', p_table, '` ADD INDEX `', p_index, '` (', p_columns, ')');
    PREPARE s FROM @ddl; EXECUTE s; DEALLOCATE PREPARE s;
  END IF;
END//
DELIMITER ;

-- ------------------------------------------------------------
-- 1. 业务多租户表补 app_id（默认 0 = 全局）
-- ------------------------------------------------------------
CALL yxshop_add_column_if_missing('yxshop_advertisements', 'app_id', "bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '应用ID(0=全局)'");
CALL yxshop_add_column_if_missing('yxshop_app_navbars',    'app_id', "bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '应用ID(0=全局)'");

CALL yxshop_add_index_if_missing('yxshop_advertisements', 'idx_app_id', '`app_id`');
CALL yxshop_add_index_if_missing('yxshop_app_navbars',    'idx_app_id', '`app_id`');

-- ------------------------------------------------------------
-- 2. 字典表（currencies/languages/regions）保留无 app_id 设计
--    由 Service 端调用 paginatedByApp(0) 时跳过过滤；不补字段
-- ------------------------------------------------------------

-- 清理工具
DROP PROCEDURE IF EXISTS `yxshop_add_column_if_missing`;
DROP PROCEDURE IF EXISTS `yxshop_add_index_if_missing`;

-- 记录版本
INSERT IGNORE INTO `yxshop_schema_migrations`(`version`, `name`, `executed_at`)
VALUES ('20260624_05', 'add_missing_app_id_columns', UNIX_TIMESTAMP());

-- ---- 迁移: migration_20260822_01_ai_module.sql ----
-- ============================================================
-- 迁移编号: 20260822_01
-- 名称: ai_module
-- 说明: AI 能力模块（使用记录表 + 算力包表）
-- ============================================================

-- ----------------------------
-- AI 使用记录表（算力消耗日志）
-- ----------------------------
CREATE TABLE IF NOT EXISTS `yxshop_ai_usage_logs` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT '雪花ID',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '应用ID',
  `admin_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '操作管理员ID',
  `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID（C端调用时）',
  `feature` varchar(50) NOT NULL DEFAULT '' COMMENT 'AI功能类型: copywriting|customer_service|analytics',
  `provider` varchar(20) NOT NULL DEFAULT '' COMMENT 'AI服务商: openai|anthropic',
  `model` varchar(100) NOT NULL DEFAULT '' COMMENT '调用的模型名称',
  `prompt` text NULL COMMENT '用户输入的提示词',
  `response` text NULL COMMENT 'AI返回的内容',
  `input_tokens` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '输入token数',
  `output_tokens` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '输出token数',
  `cost_calls` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '消耗算力次数',
  `cost_amount` decimal(10,4) NOT NULL DEFAULT 0.0000 COMMENT '消耗金额（USD估算）',
  `duration_ms` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '请求耗时（毫秒）',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态: 0=失败 1=成功',
  `error_message` text NULL COMMENT '失败时的错误信息',
  `context` json NULL COMMENT '附加上下文（商品ID、订单ID等）',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_app_feature` (`app_id`, `feature`) USING BTREE,
  INDEX `idx_admin_id` (`admin_id`) USING BTREE,
  INDEX `idx_created_at` (`created_at`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = 'AI使用记录表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- AI 算力包表（商家购买的算力额度）
-- ----------------------------
CREATE TABLE IF NOT EXISTS `yxshop_ai_credit_packages` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT '雪花ID',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '应用ID',
  `admin_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '购买管理员ID',
  `package_name` varchar(50) NOT NULL DEFAULT '' COMMENT '套餐名称',
  `total_calls` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '套餐总次数',
  `used_calls` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '已使用次数',
  `remaining_calls` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '剩余次数',
  `price` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '购买价格',
  `payment_status` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '支付状态: 0=待支付 1=已支付 2=已取消',
  `payment_id` bigint(20) UNSIGNED NULL DEFAULT NULL COMMENT '支付订单ID',
  `expire_at` int(11) UNSIGNED NULL DEFAULT NULL COMMENT '过期时间戳（null=永不过期）',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态: 0=已禁用 1=有效 2=已用完 3=已过期',
  `remark` varchar(255) NULL DEFAULT '' COMMENT '备注',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_app_id` (`app_id`) USING BTREE,
  INDEX `idx_admin_id` (`admin_id`) USING BTREE,
  INDEX `idx_status` (`status`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = 'AI算力包表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- AI 智能客服会话表
-- ----------------------------
CREATE TABLE IF NOT EXISTS `yxshop_ai_chat_sessions` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT '雪花ID',
  `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '应用ID',
  `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
  `session_id` varchar(64) NOT NULL DEFAULT '' COMMENT '会话唯一标识',
  `messages` longtext NULL COMMENT '对话历史（JSON数组）',
  `context` json NULL COMMENT '会话上下文（当前浏览商品等）',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态: 0=已关闭 1=活跃',
  `last_message_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '最后消息时间',
  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `deleted_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_user_id` (`user_id`) USING BTREE,
  INDEX `idx_session_id` (`session_id`) USING BTREE,
  INDEX `idx_app_status` (`app_id`, `status`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = 'AI智能客服会话表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- 插入 AI 默认配置到 yxshop_settings
-- ----------------------------
INSERT IGNORE INTO `yxshop_settings` (`key`, `value`, `type`, `group`, `description`, `is_encrypted`, `app_id`, `created_at`, `updated_at`) VALUES
('ai_enabled', '1', 'boolean', 'ai', 'AI功能总开关', 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('ai_default_provider', 'openai', 'string', 'ai', '默认AI服务商(openai/anthropic)', 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('ai_openai_api_key', '', 'string', 'ai', 'OpenAI API Key', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('ai_openai_base_url', 'https://api.openai.com/v1', 'string', 'ai', 'OpenAI接口地址', 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('ai_openai_model', 'gpt-4o', 'string', 'ai', 'OpenAI默认模型', 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('ai_anthropic_api_key', '', 'string', 'ai', 'Anthropic API Key', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('ai_anthropic_base_url', 'https://api.anthropic.com', 'string', 'ai', 'Anthropic接口地址', 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('ai_anthropic_model', 'claude-sonnet-4-20250514', 'string', 'ai', 'Anthropic默认模型', 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('ai_anthropic_api_version', '2023-06-01', 'string', 'ai', 'Anthropic API版本', 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('ai_billing_enabled', '1', 'boolean', 'ai', '是否启用算力计费', 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('ai_free_quota', '50', 'integer', 'ai', '新商家默认赠送算力额度', 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())
ON DUPLICATE KEY UPDATE `updated_at` = UNIX_TIMESTAMP();

-- ---- 迁移: p0_core_trade.sql ----
-- =====================================================
-- P0 核心交易链路 数据库变更
-- 执行方式：手动在数据库中执行以下 SQL
-- =====================================================

-- 1. 购物车表增加选中状态字段
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_carts` ADD COLUMN `is_selected` tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否选中 0否 1是' AFTER `quantity`;

-- 2. 购物车表增加店铺分组字段（多商户/多店铺场景）
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_carts` ADD COLUMN `store_id` bigint unsigned NOT NULL DEFAULT 0 COMMENT '店铺ID(0=自营)' AFTER `app_id`;

-- 3. 商品浏览记录表增加浏览次数字段（同商品去重累计）
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_item_views` ADD COLUMN `view_count` int NOT NULL DEFAULT 1 COMMENT '浏览次数' AFTER `item_id`;

-- ---- 迁移: p1_user_center.sql ----
-- P1: 用户中心增强 — 数据库变更
-- 执行方式：手动在数据库中运行，或通过 admin 后台 SQL 工具执行

-- D1: 订单表增加 deleted_at 字段（软删除，C端用户删除订单）
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_orders` ADD COLUMN `deleted_at` int UNSIGNED DEFAULT 0 COMMENT '软删除时间 0=未删除' AFTER `auto_confirm_time`;

-- D2: 商品浏览记录表已有 view_count 字段，确认存在（如果不存在则添加）
-- ALTER TABLE `yxshop_item_views` ADD COLUMN `view_count` int UNSIGNED DEFAULT 1 COMMENT '浏览次数' AFTER `item_id`;

-- D3: 订单日志表确认 change_type=90 用于催发货记录（无需建表，复用现有 yxshop_order_logs）
-- change_type 90 = 用户催促发货

-- 说明：
-- 1. Order 模型已添加 deleted_at 到 fillable
-- 2. ItemView 模型已包含 view_count 字段（P0 已变更）
-- 3. OrderLog 模型已包含所有必要字段，change_type=90 为催发货

-- ---- 迁移: p2_marketing.sql ----
-- P2: 营销与会员 — 数据库变更
-- 执行方式：手动在数据库中运行，或通过 admin 后台 SQL 工具执行

-- 说明：
-- P2 阶段大部分功能复用已有表结构，无需新增表或字段。
-- 以下为确认项：

-- 1. 优惠券领取：复用 yxshop_coupons + yxshop_user_coupons + yxshop_coupon_use_logs
--    CouponService::receiveCoupon 已实现原子扣减库存+风控

-- 2. 签到：复用 yxshop_signs 表
--    SignService::userSign 已实现连续签到+积分计算

-- 3. 积分商城：复用 yxshop_items.points_exchange 字段 + yxshop_orders.is_integral 标记
--    IntegralService::reduceIntegral 已实现原子扣减+余额守卫

-- 4. 余额：复用 yxshop_user_money_logs 表
--    UserMoneyService 已实现原子增减+事务+流水

-- 5. 充值：复用 yxshop_recharge_packages + yxshop_recharge_orders 表
--    RechargePackageService + RechargeOrderService 已完整实现

-- 6. 用户反馈：复用 yxshop_user_feedbacks 表
--    UserFeedbackService 已实现

-- 无需新增表或字段，全部基于现有 schema 实现。

-- ---- 迁移: p3_system_optimization.sql ----
-- P3: 系统与优化 — 数据库变更
-- 执行方式：手动在数据库中运行，或通过 admin 后台 SQL 工具执行

-- D4: 积分任务表
CREATE TABLE IF NOT EXISTS `yxshop_point_tasks` (
    `id` int UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
    `name` varchar(100) NOT NULL DEFAULT '' COMMENT '任务名称',
    `code` varchar(50) NOT NULL DEFAULT '' COMMENT '任务编码（唯一标识）',
    `type` tinyint NOT NULL DEFAULT 1 COMMENT '任务类型 1=每日 2=一次性 3=每周',
    `reward_points` int NOT NULL DEFAULT 0 COMMENT '奖励积分',
    `condition_desc` varchar(255) NOT NULL DEFAULT '' COMMENT '完成条件描述',
    `icon` varchar(255) NOT NULL DEFAULT '' COMMENT '任务图标',
    `sort` int NOT NULL DEFAULT 0 COMMENT '排序（越大越靠前）',
    `status` tinyint NOT NULL DEFAULT 1 COMMENT '状态 0=禁用 1=启用',
    `app_id` int NOT NULL DEFAULT 0 COMMENT '应用ID',
    `created_at` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `updated_at` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    INDEX `idx_code` (`code`),
    INDEX `idx_app_status` (`app_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='积分任务表';

-- 初始化默认任务数据
INSERT IGNORE INTO `yxshop_point_tasks` (`name`, `code`, `type`, `reward_points`, `condition_desc`, `icon`, `sort`, `status`, `app_id`, `created_at`, `updated_at`) VALUES
('每日签到', 'sign', 1, 1, '每天签到获取积分', 'sign-in-alt', 100, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('完善资料', 'complete_profile', 2, 10, '完善昵称、头像、手机号', 'user-edit', 90, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('首次下单', 'first_order', 2, 20, '完成首次购物', 'shopping-bag', 80, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('首次评价', 'first_review', 2, 5, '完成首次商品评价', 'star', 70, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('分享商品', 'share_product', 1, 2, '分享商品给好友', 'share-alt', 60, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- D5: 用户任务完成记录表
CREATE TABLE IF NOT EXISTS `yxshop_user_point_tasks` (
    `id` int UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
    `user_id` int NOT NULL DEFAULT 0 COMMENT '用户ID',
    `task_id` int NOT NULL DEFAULT 0 COMMENT '任务ID',
    `status` tinyint NOT NULL DEFAULT 1 COMMENT '状态 1=已完成 2=已领取奖励',
    `completed_at` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '完成时间',
    `claimed_at` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '领取奖励时间',
    `app_id` int NOT NULL DEFAULT 0 COMMENT '应用ID',
    `created_at` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    PRIMARY KEY (`id`),
    INDEX `idx_user_task` (`user_id`, `task_id`),
    INDEX `idx_user_date` (`user_id`, `completed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户积分任务完成记录';

-- D7: 用户提现申请表（C端，区别于 yxshop_agent_withdraws）
CREATE TABLE IF NOT EXISTS `yxshop_user_withdraws` (
    `id` int UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
    `user_id` int NOT NULL DEFAULT 0 COMMENT '用户ID',
    `amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '提现金额',
    `fee` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '手续费',
    `actual_amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '实际到账金额',
    `withdraw_type` tinyint NOT NULL DEFAULT 3 COMMENT '提现方式 1=银行卡 2=支付宝 3=微信',
    `account` varchar(100) NOT NULL DEFAULT '' COMMENT '提现账号',
    `account_name` varchar(50) NOT NULL DEFAULT '' COMMENT '账户姓名',
    `bank_name` varchar(100) NOT NULL DEFAULT '' COMMENT '银行名称',
    `bank_branch` varchar(100) NOT NULL DEFAULT '' COMMENT '开户支行',
    `status` tinyint NOT NULL DEFAULT 0 COMMENT '状态 0=审核中 1=已通过 2=已拒绝 3=提现成功 4=提现失败',
    `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
    `process_desc` varchar(255) NOT NULL DEFAULT '' COMMENT '处理说明',
    `process_time` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '处理时间',
    `app_id` int NOT NULL DEFAULT 0 COMMENT '应用ID',
    `created_at` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `updated_at` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    INDEX `idx_user_status` (`user_id`, `status`),
    INDEX `idx_app_status` (`app_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户提现申请';

-- ---- 迁移: snowflake_id_migration.sql ----
-- ============================================================
-- Snowflake ID Migration
-- 将所有业务表主键从 int AUTO_INCREMENT 改为 bigint(20)
-- 将所有外键字段从 int 改为 bigint(20)
-- 执行前请备份数据库！
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- 第一步：修改主键字段
-- ============================================================

-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_users` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (`id`);
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_order` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (`id`);
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_items` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (`id`);
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_category` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (`id`);
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_brand` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (`id`);
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_coupon` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (`id`);
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_app` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (`id`);
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_after_sales` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (`id`);
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_payment` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (`id`);
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_express` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (`id`);
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_notification_template` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (`id`);
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_notification_send` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (`id`);
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_dealer` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (`id`);
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_service` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (`id`);
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_prom_order` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (`id`);
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_upload_file` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (`id`);
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_user_address` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (`id`);
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_user_coupon` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (`id`);
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_integral` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (`id`);
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_item_favorite` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (`id`);
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_order_goods` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (`id`);
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_order_address` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (`id`);
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_admin` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (`id`);
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_role` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (`id`);
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_permission` MODIFY COLUMN `id` bigint(20) UNSIGNED NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (`id`);

-- ============================================================
-- 第二步：修改外键字段
-- ============================================================

-- yxshop_order
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_order` MODIFY COLUMN `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_order` MODIFY COLUMN `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0;

-- yxshop_payment
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_payment` MODIFY COLUMN `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_payment` MODIFY COLUMN `order_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_payment` MODIFY COLUMN `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0;

-- yxshop_user_address
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_user_address` MODIFY COLUMN `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_user_address` MODIFY COLUMN `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0;

-- yxshop_user_coupon
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_user_coupon` MODIFY COLUMN `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_user_coupon` MODIFY COLUMN `coupon_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0;

-- yxshop_integral
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_integral` MODIFY COLUMN `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_integral` MODIFY COLUMN `order_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0;

-- yxshop_item_favorite
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_item_favorite` MODIFY COLUMN `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_item_favorite` MODIFY COLUMN `item_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0;

-- yxshop_item_view
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_item_view` MODIFY COLUMN `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_item_view` MODIFY COLUMN `item_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0;

-- yxshop_item_search
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_item_search` MODIFY COLUMN `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0;

-- yxshop_dealer
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_dealer` MODIFY COLUMN `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_dealer` MODIFY COLUMN `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0;

-- yxshop_after_sales
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_after_sales` MODIFY COLUMN `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_after_sales` MODIFY COLUMN `order_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0;

-- yxshop_notification_send
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_notification_send` MODIFY COLUMN `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_notification_send` MODIFY COLUMN `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0;

-- yxshop_comment
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_comment` MODIFY COLUMN `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_comment` MODIFY COLUMN `item_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_comment` MODIFY COLUMN `order_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0;

-- yxshop_user_money_log
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_user_money_log` MODIFY COLUMN `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0;

-- yxshop_cart
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_cart` MODIFY COLUMN `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_cart` MODIFY COLUMN `item_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_cart` MODIFY COLUMN `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0;

-- yxshop_items
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_items` MODIFY COLUMN `category_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_items` MODIFY COLUMN `brand_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_items` MODIFY COLUMN `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0;

-- yxshop_order_goods
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_order_goods` MODIFY COLUMN `order_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_order_goods` MODIFY COLUMN `item_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0;

-- yxshop_order_address
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_order_address` MODIFY COLUMN `order_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0;

-- yxshop_prom_order
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_prom_order` MODIFY COLUMN `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0;

-- yxshop_upload_file
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_upload_file` MODIFY COLUMN `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0;

-- yxshop_service
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_service` MODIFY COLUMN `order_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_service` MODIFY COLUMN `app_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0;

-- yxshop_express
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_express` MODIFY COLUMN `order_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0;

-- yxshop_admin_role
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_admin_role` MODIFY COLUMN `admin_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_admin_role` MODIFY COLUMN `role_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0;

-- yxshop_role_permission
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_role_permission` MODIFY COLUMN `role_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0;
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_role_permission` MODIFY COLUMN `permission_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0;

-- yxshop_login_log
-- [DISABLED-REDUNDANT-MIGRATION] ALTER TABLE `yxshop_login_log` MODIFY COLUMN `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- 三、默认种子数据
-- ============================================================

SET @NOW := UNIX_TIMESTAMP();
SET @APP_ID := 0;

-- 默认超级管理员（初始密码由部署者在 .env/部署记录中保管，首次登录后请立即修改）
INSERT IGNORE INTO `yxshop_admins` (`id`, `username`, `password`, `app_id`, `nickname`, `is_super_admin`, `role_id`, `status`, `password_changed_at`, `force_password_change`, `created_at`, `updated_at`) VALUES
(7100000000001, 'admin', '$2y$12$s1VYr75YJ3ZA689DwYW8HODIkrbVy15Q4pXLVV7Dr.suwPVhhX9XW', 0, '超级管理员', 1, 0, 1, @NOW, 0, @NOW, @NOW)
ON DUPLICATE KEY UPDATE `password` = VALUES(`password`), `password_changed_at` = VALUES(`password_changed_at`), `force_password_change` = 0, `status` = 1, `updated_at` = @NOW;

-- 默认角色
INSERT IGNORE INTO `yxshop_admin_roles` (`id`, `role_name`, `role_desc`, `app_id`, `created_at`, `updated_at`) VALUES
(1, '超级管理员', '拥有所有权限', 0, @NOW, @NOW),
(2, '运营', '商品和订单管理', 0, @NOW, @NOW),
(3, '客服', '仅查看订单和用户', 0, @NOW, @NOW)
ON DUPLICATE KEY UPDATE `updated_at` = @NOW;

-- 默认会员等级
INSERT IGNORE INTO `yxshop_user_levels` (`id`, `key`, `name`, `agio`, `integral`, `shop_money`, `sort`, `desc`, `app_id`, `created_at`, `updated_at`) VALUES
(7100000000001, 'one', '普通会员', 10.00, 0, 0, 100, '注册即成为普通会员', @APP_ID, @NOW, @NOW),
(7100000000002, 'two', '白银会员', 9.50, 1000, 99, 80, '累计消费满1000元', @APP_ID, @NOW, @NOW),
(7100000000003, 'three', '黄金会员', 9.00, 5000, 299, 60, '累计消费满5000元', @APP_ID, @NOW, @NOW),
(7100000000004, 'four', '铂金会员', 8.50, 20000, 999, 40, '累计消费满20000元', @APP_ID, @NOW, @NOW),
(7100000000005, 'five', '钻石会员', 8.00, 50000, 2999, 20, '累计消费满50000元', @APP_ID, @NOW, @NOW)
ON DUPLICATE KEY UPDATE `updated_at` = @NOW;

-- 默认商品分类
INSERT IGNORE INTO `yxshop_categories` (`id`, `name`, `parent_id`, `sort`, `is_visible`, `level`, `status`, `app_id`, `created_at`, `updated_at`) VALUES
(7200000000001, '手机数码', 0, 100, 1, '1', 1, @APP_ID, @NOW, @NOW),
(7200000000002, '家用电器', 0, 90, 1, '1', 1, @APP_ID, @NOW, @NOW),
(7200000000003, '服饰鞋包', 0, 80, 1, '1', 1, @APP_ID, @NOW, @NOW),
(7200000000004, '食品生鲜', 0, 70, 1, '1', 1, @APP_ID, @NOW, @NOW),
(7200000000005, '美妆护肤', 0, 60, 1, '1', 1, @APP_ID, @NOW, @NOW)
ON DUPLICATE KEY UPDATE `updated_at` = @NOW;

-- 默认系统设置
INSERT IGNORE INTO `yxshop_settings` (`key`, `value`, `type`, `group`, `description`, `app_id`, `created_at`, `updated_at`) VALUES
('site_name', 'YXShop商城', 'string', 'general', '站点名称', 0, @NOW, @NOW),
('site_logo', '', 'string', 'general', '站点Logo', 0, @NOW, @NOW),
('site_description', 'YXShop开源商城系统', 'string', 'general', '站点描述', 0, @NOW, @NOW),
('site_keywords', '商城,电商,开店', 'string', 'general', '站点关键词', 0, @NOW, @NOW),
('site_url', 'http://localhost:8777', 'string', 'general', '站点URL', 0, @NOW, @NOW),
('icp_number', '', 'string', 'general', 'ICP备案号', 0, @NOW, @NOW),
('contact_phone', '', 'string', 'general', '联系电话', 0, @NOW, @NOW),
('contact_email', '', 'string', 'general', '联系邮箱', 0, @NOW, @NOW),
('contact_address', '', 'string', 'general', '联系地址', 0, @NOW, @NOW),
('currency', 'CNY', 'string', 'general', '默认货币', 0, @NOW, @NOW),
('language', 'zh_CN', 'string', 'general', '默认语言', 0, @NOW, @NOW),
('timezone', 'Asia/Shanghai', 'string', 'general', '时区', 0, @NOW, @NOW),
('admin_login_attempts', 5, 'int', 'security', '登录失败锁定次数', 0, @NOW, @NOW),
('admin_lock_duration', 30, 'int', 'security', '锁定时长(分钟)', 0, @NOW, @NOW),
('admin_session_timeout', 7200, 'int', 'security', '会话超时(秒)', 0, @NOW, @NOW),
('admin_password_expiry', 90, 'int', 'security', '密码有效期(天)', 0, @NOW, @NOW),
('admin_password_history', 5, 'int', 'security', '密码历史记录数', 0, @NOW, @NOW),
('dos_prevent', 1, 'bool', 'security', '防CC攻击', 0, @NOW, @NOW),
('dos_attempts', 60, 'int', 'security', 'CC攻击阈值', 0, @NOW, @NOW),
('order_auto_cancel', 30, 'int', 'trade', '订单自动取消(分钟)', 0, @NOW, @NOW),
('order_auto_receive', 7, 'int', 'trade', '订单自动收货(天)', 0, @NOW, @NOW),
('order_refund_days', 7, 'int', 'trade', '退款期限(天)', 0, @NOW, @NOW),
('free_shipping_threshold', 0, 'float', 'trade', '免邮门槛', 0, @NOW, @NOW),
('default_shipping_fee', 0, 'float', 'trade', '默认运费', 0, @NOW, @NOW)
ON DUPLICATE KEY UPDATE `updated_at` = @NOW;

-- 默认后台菜单
INSERT IGNORE INTO `yxshop_admin_menus` (`id`, `parent_id`, `name`, `model`, `url`, `icon`, `sort`, `is_show`, `permission`, `method`, `app_id`, `created_at`, `updated_at`) VALUES
(7300000000001, 0, '仪表盘', 'dashboard', '/admin/dashboard', 'fas fa-tachometer-alt', 10, 1, 'admin/dashboard', 'GET', 0, @NOW, @NOW),
(7300000000002, 0, '商品管理', 'item', NULL, 'fas fa-cubes text-primary', 20, 1, '', 'GET', 0, @NOW, @NOW),
(7300000000003, 7300000000002, '商品列表', 'item', '/admin/item', 'fas fa-boxes', 10, 1, 'admin/item', 'GET', 0, @NOW, @NOW),
(7300000000004, 7300000000002, '添加商品', 'item', '/admin/item/create', 'fas fa-plus-square', 20, 1, 'admin/item/create', 'GET', 0, @NOW, @NOW),
(7300000000005, 7300000000002, '商品分类', 'item', '/admin/category', 'fas fa-bullseye', 30, 1, 'admin/category', 'GET', 0, @NOW, @NOW),
(7300000000006, 7300000000002, '品牌管理', 'item', '/admin/brand', 'fas fa-tag', 40, 1, 'admin/brand', 'GET', 0, @NOW, @NOW),
(7300000000007, 0, '订单管理', 'order', NULL, 'fas fa-shopping-cart text-warning', 30, 1, '', 'GET', 0, @NOW, @NOW),
(7300000000008, 7300000000007, '订单列表', 'order', '/admin/order', 'fas fa-boxes', 10, 1, 'admin/order', 'GET', 0, @NOW, @NOW),
(7300000000009, 7300000000007, '售后管理', 'order', '/admin/after-sales', 'fas fa-undo', 20, 1, 'admin/after-sales', 'GET', 0, @NOW, @NOW),
(7300000000010, 0, '用户管理', 'user', NULL, 'fa fa-user text-success', 40, 1, '', 'GET', 0, @NOW, @NOW),
(7300000000011, 7300000000010, '用户列表', 'user', '/admin/user', 'fas fa-users', 10, 1, 'admin/user', 'GET', 0, @NOW, @NOW),
(7300000000012, 7300000000010, '会员等级', 'user', '/admin/user/levels', 'fas fa-medal', 20, 1, 'admin/user/levels', 'GET', 0, @NOW, @NOW),
(7300000000013, 0, '营销管理', 'marketing', NULL, 'fas fa-chart-line text-info', 50, 1, '', 'GET', 0, @NOW, @NOW),
(7300000000014, 7300000000013, '优惠券', 'marketing', '/admin/coupon', 'fa fa-puzzle-piece', 10, 1, 'admin/coupon', 'GET', 0, @NOW, @NOW),
(7300000000015, 7300000000013, '促销活动', 'marketing', '/admin/promotion', 'fas fa-gift', 20, 1, 'admin/promotion', 'GET', 0, @NOW, @NOW),
(7300000000016, 7300000000013, '广告管理', 'marketing', '/admin/advertisement', 'fas fa-image', 30, 1, 'admin/advertisement', 'GET', 0, @NOW, @NOW),
(7300000000017, 0, '内容管理', 'content', NULL, 'fas fa-newspaper text-secondary', 60, 1, '', 'GET', 0, @NOW, @NOW),
(7300000000018, 7300000000017, '文章管理', 'content', '/admin/article', 'fas fa-file-alt', 10, 1, 'admin/article', 'GET', 0, @NOW, @NOW),
(7300000000019, 7300000000017, '文章分类', 'content', '/admin/article-category', 'fas fa-folder', 20, 1, 'admin/article-category', 'GET', 0, @NOW, @NOW),
(7300000000020, 0, '系统设置', 'setting', NULL, 'fa fa-wrench text-primary', 70, 1, '', 'GET', 0, @NOW, @NOW),
(7300000000021, 7300000000020, '基本设置', 'setting', '/admin/setting', 'fas fa-store', 10, 1, 'admin/setting', 'GET', 0, @NOW, @NOW),
(7300000000022, 7300000000020, '支付设置', 'setting', '/admin/setting/payment', 'fas fa-credit-card', 20, 1, 'admin/setting/payment', 'GET', 0, @NOW, @NOW),
(7300000000023, 7300000000020, '配送设置', 'setting', '/admin/setting/shipping', 'fas fa-shipping-fast', 30, 1, 'admin/setting/shipping', 'GET', 0, @NOW, @NOW),
(7300000000024, 7300000000020, '管理员管理', 'setting', '/admin/admin', 'fas fa-user-shield', 40, 1, 'admin/admin', 'GET', 0, @NOW, @NOW),
(7300000000025, 7300000000020, '角色权限', 'setting', '/admin/role', 'fas fa-key', 50, 1, 'admin/role', 'GET', 0, @NOW, @NOW),
(7300000000026, 7300000000020, '安全设置', 'setting', '/admin/setting/security', 'fas fa-shield-alt', 60, 1, 'admin/setting/security', 'GET', 0, @NOW, @NOW),
(7300000000027, 0, '小程序装修', 'miniprogram', NULL, 'fas fa-mobile-alt text-info', 80, 1, '', 'GET', 0, @NOW, @NOW),
(7300000000028, 7300000000027, '页面管理', 'miniprogram', '/admin/mini-page', 'fas fa-layout', 10, 1, 'admin/mini-page', 'GET', 0, @NOW, @NOW),
(7300000000029, 7300000000027, '底部导航', 'miniprogram', '/admin/mini-tab-bar', 'fas fa-bars', 20, 1, 'admin/mini-tab-bar', 'GET', 0, @NOW, @NOW),
(7300000000030, 7300000000027, '主题设置', 'miniprogram', '/admin/mini-theme', 'fas fa-palette', 30, 1, 'admin/mini-theme', 'GET', 0, @NOW, @NOW)
ON DUPLICATE KEY UPDATE `updated_at` = @NOW;

-- 迁移记录表
CREATE TABLE IF NOT EXISTS `yxshop_schema_migrations` (
  `version` VARCHAR(30) NOT NULL DEFAULT '',
  `name` VARCHAR(100) NOT NULL DEFAULT '',
  `executed_at` INT(11) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='迁移记录表';

-- 标记所有迁移为已执行
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('2026_01_drop_non_prefixed_tables', '2026_01_drop_non_prefixed_tables', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('2026_02_unify_time_fields', '2026_02_unify_time_fields', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('2026_03_unify_primary_keys', '2026_03_unify_primary_keys', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('2026_04_unify_foreign_keys', '2026_04_unify_foreign_keys', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('2026_06_fix_brands_table', '2026_06_fix_brands_table', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('2026_07_03_000001_create_mini_page_tables', '2026_07_03_000001_create_mini_page_tables', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('2026_07_03_000002_mini_page_menus', '2026_07_03_000002_mini_page_menus', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('2026_07_03_000003_mini_theme_nav_text_color_hex', '2026_07_03_000003_mini_theme_nav_text_color_hex', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('2026_07_03_000004_user_list_menu', '2026_07_03_000004_user_list_menu', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('2026_07_03_000005_user_levels_add_columns', '2026_07_03_000005_user_levels_add_columns', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('2026_07_fix_expresses_table', '2026_07_fix_expresses_table', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('2026_08_fix_comments_table', '2026_08_fix_comments_table', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('2026_09_table_comments', '2026_09_table_comments', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('2026_10_add_indexes_and_charset', '2026_10_add_indexes_and_charset', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('2026_11_create_role_auths_pivot', '2026_11_create_role_auths_pivot', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('2026_12_rebuild_menus_tree', '2026_12_rebuild_menus_tree', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('2026_13_fix_menu_permissions', '2026_13_fix_menu_permissions', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('2026_14_create_missing_tables', '2026_14_create_missing_tables', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('2026_15_fix_upload_tables', '2026_15_fix_upload_tables', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('2026_16_normalize_all_tables', '2026_16_normalize_all_tables', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('2026_17_enterprise_refactor', '2026_17_enterprise_refactor', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('2026_18_enterprise_menus', '2026_18_enterprise_menus', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('2026_18_fix_schema_review', '2026_18_fix_schema_review', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('2026_19_drop_admin_auths_merge_app_helps', '2026_19_drop_admin_auths_merge_app_helps', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('2026_20_rename_goods_to_item', '2026_20_rename_goods_to_item', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('2026_21_rename_remaining_goods_fields', '2026_21_rename_remaining_goods_fields', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('2026_22_supply_chain_enterprise', '2026_22_supply_chain_enterprise', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('2026_23_after_sales_enterprise', '2026_23_after_sales_enterprise', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('2026_24_article_enterprise', '2026_24_article_enterprise', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('2026_25_file_enterprise', '2026_25_file_enterprise', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('2026_26_coupon_finance', '2026_26_coupon_finance', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('2026_27_user_risk_tag', '2026_27_user_risk_tag', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('2026_28_card_recharge', '2026_28_card_recharge', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('2026_29_finance_account', '2026_29_finance_account', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('2026_30_dealer_enterprise', '2026_30_dealer_enterprise', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('2026_31_warehouse_supplier_enterprise', '2026_31_warehouse_supplier_enterprise', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('2026_32_add_sort_to_item_types', '2026_32_add_sort_to_item_types', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('2026_33_order_enhance', '2026_33_order_enhance', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('2026_34_community_elderly', '2026_34_community_elderly', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('2026_34_create_care_reviews_table', '2026_34_create_care_reviews_table', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('2026_34_setting_encrypt', '2026_34_setting_encrypt', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('2026_35_community_service_refactor', '2026_35_community_service_refactor', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('2026_35_data_change_log_enhance', '2026_35_data_change_log_enhance', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('2026_35_referral_system_fix', '2026_35_referral_system_fix', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('2026_36_distribution_tasks', '2026_36_distribution_tasks', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('2026_36_order_agent_menu', '2026_36_order_agent_menu', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('2026_36_promotion_enhance', '2026_36_promotion_enhance', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('2026_37_login_security', '2026_37_login_security', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('2026_38_wechat_login', '2026_38_wechat_login', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('2026_39_live_stream', '2026_39_live_stream', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('2026_40_multi_shop', '2026_40_multi_shop', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('2026_41_supply_chain_enhance', '2026_41_supply_chain_enhance', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('2026_41_supply_chain_menus', '2026_41_supply_chain_menus', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('2026_42_audit_trail_enhance', '2026_42_audit_trail_enhance', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('2026_43_phase44_45_46_enterprise', '2026_43_phase44_45_46_enterprise', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('2026_44_phase47_agent_profit', '2026_44_phase47_agent_profit', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('create_currencies_table', 'create_currencies_table', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('create_languages_table', 'create_languages_table', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('create_user_contracts', 'create_user_contracts', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('migration_01_charset_softdelete', 'migration_01_charset_softdelete', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('migration_02_field_alignment', 'migration_02_field_alignment', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('migration_03_missing_tables', 'migration_03_missing_tables', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('migration_04_indexes_types_cleanup', 'migration_04_indexes_types_cleanup', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('migration_05_add_missing_app_id', 'migration_05_add_missing_app_id', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('migration_20260822_01_ai_module', 'migration_20260822_01_ai_module', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('p0_core_trade', 'p0_core_trade', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('p1_user_center', 'p1_user_center', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('p2_marketing', 'p2_marketing', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('p3_system_optimization', 'p3_system_optimization', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
-- ============================================================
-- Phase 5: SaaS 托管平台 - 多租户架构表结构
-- ============================================================

-- SaaS 套餐表
CREATE TABLE IF NOT EXISTS `yxshop_tenant_plans` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '套餐ID',
  `plan_code`       VARCHAR(32) NOT NULL DEFAULT '' COMMENT '套餐编码: trial/standard/pro/flagship',
  `plan_name`       VARCHAR(50) NOT NULL DEFAULT '' COMMENT '套餐名称',
  `description`     VARCHAR(255) NOT NULL DEFAULT '' COMMENT '套餐描述',
  `edition`         VARCHAR(20) NOT NULL DEFAULT 'commercial' COMMENT '对应版本: open_source/commercial/enterprise',
  `price_monthly`   DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '月费(元)',
  `price_yearly`    DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '年费(元)',
  `trial_days`      INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '试用天数(0=无试用)',
  `max_admins`      INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '最大管理员数',
  `max_users`       INT UNSIGNED NOT NULL DEFAULT 1000 COMMENT '最大用户数',
  `max_items`       INT UNSIGNED NOT NULL DEFAULT 100 COMMENT '最大商品数',
  `max_orders`      INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '月最大订单数(0=不限)',
  `max_storage_mb`  INT UNSIGNED NOT NULL DEFAULT 500 COMMENT '存储空间(MB)',
  `max_bandwidth_mb` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '月流量(MB, 0=不限)',
  `features`        JSON DEFAULT NULL COMMENT '功能模块列表',
  `sort`            INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序',
  `status`          TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态: 0=下架 1=上架',
  `is_recommended`  TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否推荐',
  `created_at`      INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at`      INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_plan_code` (`plan_code`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='SaaS套餐表';

-- SaaS 租户表
CREATE TABLE IF NOT EXISTS `yxshop_tenants` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '租户ID',
  `tenant_code`     VARCHAR(32) NOT NULL DEFAULT '' COMMENT '租户编码(唯一)',
  `tenant_name`     VARCHAR(100) NOT NULL DEFAULT '' COMMENT '租户名称',
  `contact_name`    VARCHAR(50) NOT NULL DEFAULT '' COMMENT '联系人',
  `contact_phone`   VARCHAR(20) NOT NULL DEFAULT '' COMMENT '联系电话',
  `contact_email`   VARCHAR(100) NOT NULL DEFAULT '' COMMENT '联系邮箱',
  `plan_id`         INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '当前套餐ID',
  `app_id`          INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '对应应用ID',
  `status`          TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态: 0=待激活 1=正常 2=暂停 3=已过期 4=已注销',
  `edition`         VARCHAR(20) NOT NULL DEFAULT 'commercial' COMMENT '当前版本',
  `activated_at`    INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '激活时间',
  `expired_at`      INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '到期时间(0=永久)',
  `suspended_at`    INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '暂停时间',
  `suspended_reason` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '暂停原因',
  `remark`          VARCHAR(500) NOT NULL DEFAULT '' COMMENT '备注',
  `deleted_at`      INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '软删除',
  `created_at`      INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at`      INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tenant_code` (`tenant_code`),
  KEY `idx_app_id` (`app_id`),
  KEY `idx_status` (`status`),
  KEY `idx_plan_id` (`plan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='SaaS租户表';

-- 租户订阅记录表
CREATE TABLE IF NOT EXISTS `yxshop_tenant_subscriptions` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '订阅ID',
  `tenant_id`       BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '租户ID',
  `plan_id`         INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '套餐ID',
  `plan_name`       VARCHAR(50) NOT NULL DEFAULT '' COMMENT '套餐名称(快照)',
  `plan_code`       VARCHAR(32) NOT NULL DEFAULT '' COMMENT '套餐编码(快照)',
  `billing_cycle`   VARCHAR(10) NOT NULL DEFAULT 'monthly' COMMENT '计费周期: monthly/yearly',
  `unit_price`      DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '单价',
  `quantity`        INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '数量',
  `total_amount`    DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '总金额',
  `paid_amount`     DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '已付金额',
  `start_at`        INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '订阅开始时间',
  `end_at`          INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '订阅结束时间',
  `pay_status`      TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '支付状态: 0=待支付 1=已支付 2=已退款',
  `pay_method`      VARCHAR(20) NOT NULL DEFAULT '' COMMENT '支付方式',
  `pay_time`        INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '支付时间',
  `trade_no`        VARCHAR(64) NOT NULL DEFAULT '' COMMENT '交易号',
  `status`          TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态: 0=已取消 1=有效 2=已过期 3=已续费',
  `auto_renew`      TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否自动续费',
  `remark`          VARCHAR(255) NOT NULL DEFAULT '' COMMENT '备注',
  `created_at`      INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at`      INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_status` (`status`),
  KEY `idx_end_at` (`end_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='租户订阅记录表';

-- 租户用量计量表
CREATE TABLE IF NOT EXISTS `yxshop_tenant_usage_metrics` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '计量ID',
  `tenant_id`       BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '租户ID',
  `metric_date`     DATE NOT NULL COMMENT '计量日期',
  `admin_count`     INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '管理员数',
  `user_count`      INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户数',
  `item_count`      INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '商品数',
  `order_count`     INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '当日订单数',
  `storage_used_mb` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '已用存储(MB)',
  `bandwidth_used_mb` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '当日流量(MB)',
  `over_limit`      TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否超限',
  `over_limit_details` JSON DEFAULT NULL COMMENT '超限详情',
  `plan_code`       VARCHAR(32) NOT NULL DEFAULT '' COMMENT '当时套餐编码',
  `created_at`      INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tenant_date` (`tenant_id`, `metric_date`),
  KEY `idx_metric_date` (`metric_date`),
  KEY `idx_over_limit` (`over_limit`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='租户用量计量表';

-- 租户域名映射表
CREATE TABLE IF NOT EXISTS `yxshop_tenant_domains` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '域名ID',
  `tenant_id`       BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '租户ID',
  `domain`          VARCHAR(200) NOT NULL DEFAULT '' COMMENT '域名/子域名',
  `domain_type`     TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '类型: 1=子域名 2=自定义域名',
  `is_primary`      TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否主域名',
  `ssl_status`      TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'SSL状态: 0=未配置 1=已配置 2=自动签发中',
  `verify_status`   TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '验证状态: 0=待验证 1=已验证 2=验证失败',
  `verified_at`     INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '验证时间',
  `status`          TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态: 0=禁用 1=启用',
  `created_at`      INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at`      INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_domain` (`domain`),
  KEY `idx_tenant_id` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='租户域名映射表';

-- 租户账单表
CREATE TABLE IF NOT EXISTS `yxshop_tenant_invoices` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '账单ID',
  `invoice_no`      VARCHAR(32) NOT NULL DEFAULT '' COMMENT '账单号',
  `tenant_id`       BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '租户ID',
  `subscription_id` BIGINT UNSIGNED DEFAULT NULL COMMENT '关联订阅ID',
  `amount`          DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '账单金额',
  `paid_amount`     DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '已付金额',
  `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '优惠金额',
  `billing_start`   INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '计费开始时间',
  `billing_end`     INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '计费结束时间',
  `due_at`          INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '到期应付时间',
  `status`          TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态: 0=待支付 1=已支付 2=已逾期 3=已取消 4=已退款',
  `pay_method`      VARCHAR(20) NOT NULL DEFAULT '' COMMENT '支付方式',
  `pay_time`        INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '支付时间',
  `trade_no`        VARCHAR(64) NOT NULL DEFAULT '' COMMENT '交易号',
  `invoice_type`    TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '发票类型: 0=无 1=电子普票 2=专票',
  `invoice_status`  TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '发票状态: 0=未开 1=已开 2=开票中',
  `remark`          VARCHAR(500) NOT NULL DEFAULT '' COMMENT '备注',
  `created_at`      INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at`      INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_invoice_no` (`invoice_no`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='租户账单表';

-- 租户操作日志表
CREATE TABLE IF NOT EXISTS `yxshop_tenant_operation_logs` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '日志ID',
  `tenant_id`       BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '租户ID',
  `operator_id`     BIGINT UNSIGNED DEFAULT NULL COMMENT '操作人ID',
  `operator_name`   VARCHAR(50) NOT NULL DEFAULT '' COMMENT '操作人姓名',
  `action`          VARCHAR(30) NOT NULL DEFAULT '' COMMENT '操作类型',
  `target_type`     VARCHAR(20) NOT NULL DEFAULT '' COMMENT '操作对象类型',
  `target_id`       BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '操作对象ID',
  `before_value`    VARCHAR(255) NOT NULL DEFAULT '' COMMENT '变更前值',
  `after_value`     VARCHAR(255) NOT NULL DEFAULT '' COMMENT '变更后值',
  `remark`          VARCHAR(500) NOT NULL DEFAULT '' COMMENT '备注',
  `ip`              VARCHAR(45) NOT NULL DEFAULT '' COMMENT 'IP地址',
  `created_at`      INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '操作时间',
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='租户操作日志表';

-- 默认套餐数据
INSERT IGNORE INTO `yxshop_tenant_plans` (`plan_code`, `plan_name`, `description`, `edition`, `price_monthly`, `price_yearly`, `trial_days`, `max_admins`, `max_users`, `max_items`, `max_orders`, `max_storage_mb`, `max_bandwidth_mb`, `features`, `sort`, `status`, `is_recommended`, `created_at`, `updated_at`) VALUES
('trial', '体验版', '免费体验14天，适合个人和小团队试用', 'commercial', 0.00, 0.00, 14, 1, 100, 20, 50, 100, 1000, JSON_ARRAY('distribution','seckill_pro'), 100, 1, 0, @NOW, @NOW),
('standard', '标准版', '适合中小商家，包含核心商业版功能', 'commercial', 99.00, 999.00, 0, 3, 5000, 500, 5000, 1000, 10000, JSON_ARRAY('distribution','seckill_pro','membership_card','group_buy','gift_activity'), 80, 1, 1, @NOW, @NOW),
('pro', '专业版', '适合成长型企业，包含全部商业版+部分企业版功能', 'enterprise', 299.00, 2999.00, 0, 10, 50000, 5000, 0, 5000, 0, JSON_ARRAY('distribution','seckill_pro','membership_card','group_buy','gift_activity','live_stream','community_care','multi_shop'), 60, 1, 0, @NOW, @NOW),
('flagship', '旗舰版', '适合大型企业，包含全部功能+独立资源', 'enterprise', 999.00, 9999.00, 0, 50, 0, 0, 0, 50000, 0, JSON_ARRAY('distribution','seckill_pro','membership_card','group_buy','gift_activity','live_stream','community_care','multi_shop','supply_chain','audit_trail','fine_grained_auth','item_audit','analytics_pro','agent_profit'), 40, 1, 0, @NOW, @NOW)
ON DUPLICATE KEY UPDATE `updated_at` = @NOW;

-- ============================================================
-- 迁移记录
-- ============================================================

INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('snowflake_id_migration', 'snowflake_id_migration', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;

-- Phase 4 企业版迁移记录
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('2026_40_phase4_enterprise_base', '2026_40_phase4_enterprise_base', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('2026_41_phase4_supply_chain', '2026_41_phase4_supply_chain', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('2026_42_phase4_audit_trail', '2026_42_phase4_audit_trail', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('2026_43_phase4_fine_grained_auth', '2026_43_phase4_fine_grained_auth', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('2026_44_phase4_analytics', '2026_44_phase4_analytics', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;

-- Phase 4.7 代理分润迁移记录
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('2026_441_agent_levels', '2026_441_agent_levels', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('2026_442_agent_profit_rules', '2026_442_agent_profit_rules', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('2026_443_agent_withdraw_audits', '2026_443_agent_withdraw_audits', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('2026_444_agent_ranking_snapshots', '2026_444_agent_ranking_snapshots', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;

-- Phase 5 SaaS 托管平台迁移记录
INSERT IGNORE INTO `yxshop_schema_migrations` (`version`, `name`, `executed_at`) VALUES ('2026_45_phase5_saas_platform', '2026_45_phase5_saas_platform', @NOW) ON DUPLICATE KEY UPDATE `executed_at` = @NOW;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- 初始化完成！
-- 默认管理员: admin / admin123
-- 请在首次登录后立即修改密码！
-- ============================================================


-- 清理工具存储过程
DROP PROCEDURE IF EXISTS `yxshop_add_column_if_missing`;
DROP PROCEDURE IF EXISTS `yxshop_add_index_if_missing`;

-- ============================================================
-- 收尾：等级种子数据回填（种子 INSERT 在本文件后半段，故在此执行）
-- ============================================================
SET @lv := 0;
UPDATE `yxshop_user_levels` SET `level` = (@lv := @lv + 1)
 WHERE `description` = '' OR `description` IS NULL
 ORDER BY `sort` DESC;
UPDATE `yxshop_user_levels` SET `experience` = `integral`
 WHERE `experience` = 0 AND `integral` > 0;
UPDATE `yxshop_user_levels` SET `discount` = `agio`
 WHERE `discount` = 10.00 AND `agio` IS NOT NULL AND `agio` <> 10.00;
UPDATE `yxshop_user_levels` SET `description` = COALESCE(`desc`, '')
 WHERE (`description` = '' OR `description` IS NULL) AND `desc` IS NOT NULL AND `desc` <> '';
UPDATE `yxshop_user_levels` SET `is_default` = 1 WHERE `level` = 1 AND `is_default` = 0;

-- ============================================================
-- 收尾：地址省市名称快照 + 用户登录账号（2026-11-06）
-- 说明：yxshop_regions 基础数据可能为空，仅靠 region_id 会让收货地址只剩门牌号，
--       故地址表额外保存省/市/区名称；users 增加 username 以支持账号密码注册登录。
-- ============================================================
SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'yxshop_user_addresses' AND column_name = 'province') = 0,
  'ALTER TABLE `yxshop_user_addresses`
     ADD COLUMN `province` varchar(50) NOT NULL DEFAULT '''' COMMENT ''省名称'' AFTER `district_id`,
     ADD COLUMN `city` varchar(50) NOT NULL DEFAULT '''' COMMENT ''市名称'' AFTER `province`,
     ADD COLUMN `district` varchar(50) NOT NULL DEFAULT '''' COMMENT ''区县名称'' AFTER `city`',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'yxshop_order_addresses' AND column_name = 'province') = 0,
  'ALTER TABLE `yxshop_order_addresses`
     ADD COLUMN `province` varchar(50) NOT NULL DEFAULT '''' COMMENT ''省名称'' AFTER `city_id`,
     ADD COLUMN `city` varchar(50) NOT NULL DEFAULT '''' COMMENT ''市名称'' AFTER `province`,
     ADD COLUMN `district` varchar(50) NOT NULL DEFAULT '''' COMMENT ''区县名称'' AFTER `city`',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'yxshop_users' AND column_name = 'username') = 0,
  'ALTER TABLE `yxshop_users` ADD COLUMN `username` varchar(64) NULL COMMENT ''登录账号'' AFTER `id`,
     ADD UNIQUE KEY `uk_username` (`username`)',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 单租户商城统一 app_id = 0
UPDATE `yxshop_users` SET `app_id` = 0 WHERE `app_id` <> 0;

-- ============================================================
-- 收尾：订单表补配送模板列（2026-11-06）
-- 说明：结算页让用户选择配送方式（对应管理端「物流配送 → 配送模板」），
--       下单时需要把所选模板记到订单上，并据此按计费规则算运费。
-- ============================================================
SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'yxshop_orders' AND column_name = 'delivery_id') = 0,
  'ALTER TABLE `yxshop_orders`
     ADD COLUMN `delivery_id` bigint NOT NULL DEFAULT 0 COMMENT ''配送模板ID(0=默认)'' AFTER `express_price`',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================================
-- 收尾：促销商品表补列（2026-11-06）
-- 说明：PromItem 模型 / PromItemRepository / 管理端「商品促销」页都依赖
--       prom_id、prom_price、prom_stock、prom_sales、prom_status、sort 等列，
--       但基线建表（沿用早期 yxshop_prom_goods 结构）里没有这些列，
--       导致商品促销相关接口直接 500（Unknown column 'prom_status'）。
-- ============================================================
SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'yxshop_prom_items' AND column_name = 'prom_id') = 0,
  'ALTER TABLE `yxshop_prom_items`
     ADD COLUMN `prom_id` bigint NOT NULL DEFAULT 0 COMMENT ''所属促销活动ID'' AFTER `id`,
     ADD COLUMN `prom_price` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT ''促销价'' AFTER `prom_id`,
     ADD COLUMN `prom_stock` int NOT NULL DEFAULT 0 COMMENT ''促销库存'' AFTER `prom_price`,
     ADD COLUMN `prom_sales` int NOT NULL DEFAULT 0 COMMENT ''促销销量'' AFTER `prom_stock`,
     ADD COLUMN `prom_status` tinyint NOT NULL DEFAULT 1 COMMENT ''状态 0禁用 1启用 2售罄'' AFTER `prom_sales`,
     ADD COLUMN `sort` int NOT NULL DEFAULT 0 COMMENT ''排序'' AFTER `prom_status`,
     ADD COLUMN `created_at` int NOT NULL DEFAULT 0 COMMENT ''创建时间'' AFTER `sort`,
     ADD COLUMN `updated_at` int NOT NULL DEFAULT 0 COMMENT ''更新时间'' AFTER `created_at`,
     ADD KEY `idx_prom_id` (`prom_id`),
     ADD KEY `idx_item_id` (`item_id`)',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================================
-- 收尾：后台菜单图标规范化（2026-11-06）
-- 菜单 icon 会被前端当作 Vue 组件名解析，拼写错误会渲染成空白标签。
-- ============================================================
UPDATE `yxshop_admin_menus` SET `icon`='icon-thunderbolt'   WHERE `icon`='icon-thunder';
UPDATE `yxshop_admin_menus` SET `icon`='icon-file'          WHERE `icon`='icon-file-text';
UPDATE `yxshop_admin_menus` SET `icon`='icon-trophy'        WHERE `icon`='icon-crown';
UPDATE `yxshop_admin_menus` SET `icon`='icon-gift'          WHERE `icon`='icon-percentage';
UPDATE `yxshop_admin_menus` SET `icon`='icon-bar-chart'     WHERE `icon`='icon-money-collect';
UPDATE `yxshop_admin_menus` SET `icon`='icon-idcard'        WHERE `icon`='icon-credit-card';
UPDATE `yxshop_admin_menus` SET `icon`='icon-alipay-circle' WHERE `icon`='icon-pay-circle';
UPDATE `yxshop_admin_menus` SET `icon`='icon-email'         WHERE `icon`='icon-mail';
UPDATE `yxshop_admin_menus` SET `icon`='icon-settings'      WHERE `icon`='icon-setting';
UPDATE `yxshop_admin_menus` SET `icon`='icon-launch'        WHERE `icon`='icon-rocket';
UPDATE `yxshop_admin_menus` SET `icon`='icon-nav'           WHERE `icon`='icon-deliver';
UPDATE `yxshop_admin_menus` SET `icon`='icon-public'        WHERE `icon`='icon-dollar';
UPDATE `yxshop_admin_menus` SET `icon`='icon-location'      WHERE `icon`='icon-global';
UPDATE `yxshop_admin_menus` SET `icon`='icon-clock-circle'  WHERE `icon`='icon-clock';
UPDATE `yxshop_admin_menus` SET `icon`='icon-safe'          WHERE `icon`='icon-wallet';
UPDATE `yxshop_admin_menus` SET `icon`='icon-user-group'    WHERE `icon`='icon-team';
UPDATE `yxshop_admin_menus` SET `icon`='icon-import'        WHERE `icon`='icon-login';
-- 单个菜单用更贴切的图标
UPDATE `yxshop_admin_menus` SET `icon`='icon-file-pdf' WHERE `id`=81 AND `name`='发票管理';
UPDATE `yxshop_admin_menus` SET `icon`='icon-search'   WHERE `id`=91 AND `name`='页面SEO';
-- 仪表盘由前端静态路由提供，删除数据库里的重复项
DELETE FROM `yxshop_admin_role_auths` WHERE `auth_id` IN (SELECT `id` FROM `yxshop_admin_menus` WHERE `url`='/dashboard');
DELETE FROM `yxshop_admin_menus` WHERE `url`='/dashboard';
