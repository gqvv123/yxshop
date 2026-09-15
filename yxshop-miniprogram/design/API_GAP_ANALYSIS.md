# 小程序设计稿落地 — 后端接口与模块差距清单

> 对照 8 个设计稿（profile / cart / category / item-detail / order-list / coupon / point-exchange / balance）
> 盘点现有后端能力，标记 **✅ 已有 / ⚠️ 需增强 / ❌ 需新建**。

## ✅ P0 核心交易链路 — 已完成落地

| 编号 | 接口 | 方法 | 路由 | 状态 |
|------|------|------|------|------|
| 1.2  | 订单列表 tab 过滤 | GET | `/api/v1/order/list?tab=pending_payment` | ✅ 已完成 |
| 2.1  | 购物车选中/取消 | POST | `/api/v1/cart/select` | ✅ 已完成 |
| 2.2  | 购物车全选/取消全选 | POST | `/api/v1/cart/select-all` | ✅ 已完成 |
| 2.3  | 购物车数量徽标 | GET | `/api/v1/cart/count` | ✅ 已完成 |
| 2.4  | 购物车结算预览 | GET | `/api/v1/cart/checkout-preview` | ✅ 已完成 |
| 2.5  | 购物车列表增强（店铺分组+选中金额） | GET | `/api/v1/cart/list` | ✅ 已完成 |
| 3.1  | 分类树（三级） | GET | `/api/v1/category/tree` | ✅ 已完成 |
| 3.2  | 分类下商品列表 | GET | `/api/v1/category/items` | ✅ 已完成 |
| 4.1  | 商品详情增强（SKU+规格+评价摘要） | GET | `/api/v1/item/detail` | ✅ 已完成 |
| 4.3  | 商品评价列表（分页+过滤） | GET | `/api/v1/item/reviews` | ✅ 已完成 |
| 4.7  | 相关推荐商品 | GET | `/api/v1/item/related` | ✅ 已完成 |
| 5.1  | 订单各状态数量（tab 角标） | GET | `/api/v1/order/status-counts` | ✅ 已完成 |

**数据库变更：** `database/migrations/p0_core_trade.sql`
**小程序端 API 层：** `api/cart.js` / `api/item.js` / `api/order.js` / `api/category.js` 已同步更新

---

## ✅ P1 用户中心基础 — 已完成落地

| 编号 | 接口 | 方法 | 路由 | 状态 |
|------|------|------|------|------|
| 1.1  | 用户综合信息（聚合） | GET | `/api/v1/user/profile` | ✅ 已完成 |
| 1.6  | 浏览足迹列表 | GET | `/api/v1/user/footprint` | ✅ 已完成 |
| 1.6b | 删除单条足迹 | POST | `/api/v1/user/footprint-remove` | ✅ 已完成 |
| 1.6c | 清空足迹 | POST | `/api/v1/user/footprint-clear` | ✅ 已完成 |
| 4.3  | 商品咨询/问答列表 | GET | `/api/v1/item/consultations` | ✅ 已完成 |
| 4.4  | 提交商品咨询 | POST | `/api/v1/item/consult` | ✅ 已完成 |
| 5.2  | 再次购买 | POST | `/api/v1/order/reorder` | ✅ 已完成 |
| 5.3  | 订单物流 | GET | `/api/v1/order/logistics` | ✅ 已完成 |
| 5.4  | 提醒发货 | POST | `/api/v1/order/remind-ship` | ✅ 已完成 |
| 5.5  | 删除订单 | POST | `/api/v1/order/delete` | ✅ 已完成 |

**数据库变更：** `database/migrations/p1_user_center.sql`（orders 表增加 `deleted_at` 字段）
**小程序端 API 层：** `api/user.js` / `api/order.js` / `api/item.js` 已同步更新

---

## ✅ P2 营销与会员 — 已完成落地

| 编号 | 接口 | 方法 | 路由 | 状态 |
|------|------|------|------|------|
| 6.1  | 可领优惠券列表 | GET | `/api/v1/coupon/available` | ✅ 已完成 |
| 6.2  | 领取优惠券 | POST | `/api/v1/coupon/claim` | ✅ 已完成 |
| 6.3  | 优惠券统计 | GET | `/api/v1/coupon/stats` | ✅ 已完成 |
| 6.4  | 我的优惠券（增强） | GET | `/api/v1/coupon/my-list` | ✅ 已完成 |
| 1.3  | 签到状态 | GET | `/api/v1/sign/status` | ✅ 已完成 |
| 1.4  | 执行签到 | POST | `/api/v1/sign/do` | ✅ 已完成 |
| 1.5  | 签到记录（日历） | GET | `/api/v1/sign/records` | ✅ 已完成 |
| 7.1  | 积分商城首页 | GET | `/api/v1/point/center` | ✅ 已完成 |
| 7.3  | 积分商品列表（增强） | GET | `/api/v1/point-item/list` | ✅ 已完成 |
| 7.4  | 积分兑换 | POST | `/api/v1/point-item/exchange` | ✅ 已完成 |
| 7.5  | 兑换记录 | GET | `/api/v1/point-item/records` | ✅ 已完成 |
| 7.6  | 积分流水 | GET | `/api/v1/user/integral-log` | ✅ 已完成 |
| 7.7  | 积分统计 | GET | `/api/v1/user/integral-stats` | ✅ 已完成 |
| 8.1  | 余额信息 | GET | `/api/v1/user/balance` | ✅ 已完成 |
| 8.2  | 余额流水 | GET | `/api/v1/user/money-log` | ✅ 已完成 |
| 8.3  | 充值套餐 | GET | `/api/v1/recharge/packages` | ✅ 已完成 |
| 8.4  | 创建充值 | POST | `/api/v1/recharge/create` | ✅ 已完成 |
| 8.5b | 充值记录 | GET | `/api/v1/recharge/records` | ✅ 已完成 |
| 8.6  | 收支统计 | GET | `/api/v1/user/money-stats` | ✅ 已完成 |
| 1.7  | 用户反馈提交 | POST | `/api/v1/feedback/submit` | ✅ 已完成 |
| 1.7b | 我的反馈列表 | GET | `/api/v1/feedback/my-list` | ✅ 已完成 |

**数据库变更：** `database/migrations/p2_marketing.sql`（无需新增表/字段，全部复用现有 schema）
**小程序端 API 层：** 新建 `api/coupon.js` / `api/sign.js` / `api/point.js` / `api/balance.js` / `api/feedback.js`
**新增 Controller：** `SignController` / `PointExchangeController` / `BalanceController` / `FeedbackController`，增强 `CouponController`

---

## ✅ P3 系统与优化 — 已完成落地

| 编号 | 接口 | 方法 | 路由 | 状态 |
|------|------|------|------|------|
| 1.8  | 用户等级详情 | GET | `/api/v1/user/level` | ✅ 已完成 |
| 7.2  | 积分任务列表 | GET | `/api/v1/point/tasks` | ✅ 已完成 |
| 7.2b | 领取任务奖励 | POST | `/api/v1/point/tasks/claim` | ✅ 已完成 |
| 8.5  | 提现申请 | POST | `/api/v1/withdraw/apply` | ✅ 已完成 |
| 8.5b | 提现记录 | GET | `/api/v1/withdraw/records` | ✅ 已完成 |
| -    | 搜索历史列表 | GET | `/api/v1/user/search-history` | ✅ 已完成 |
| -    | 清空搜索历史 | POST | `/api/v1/user/search-history-clear` | ✅ 已完成 |
| -    | 删除单条搜索历史 | POST | `/api/v1/user/search-history-remove` | ✅ 已完成 |

**数据库变更：** `database/migrations/p3_system_optimization.sql`（新建 `yxshop_point_tasks` / `yxshop_user_point_tasks` / `yxshop_user_withdraws` 三张表 + 默认任务数据）
**小程序端 API 层：** 新建 `api/withdraw.js` / `api/task.js`，增强 `api/user.js`（搜索历史+等级详情）
**新增 Controller：** `UserLevelController` / `PointTaskController` / `WithdrawController`
**新增 Model：** `PointTask` / `UserPointTask` / `UserWithdraw`

---

## 📊 全部落地总结

| 优先级 | 名称 | 接口数 | 数据库变更 | 状态 |
|--------|------|--------|-----------|------|
| P0 | 核心交易链路 | 12 | `p0_core_trade.sql` | ✅ 已完成 |
| P1 | 用户中心基础 | 10 | `p1_user_center.sql` | ✅ 已完成 |
| P2 | 营销与会员 | 21 | `p2_marketing.sql` | ✅ 已完成 |
| P3 | 系统与优化 | 8 | `p3_system_optimization.sql` | ✅ 已完成 |
| **合计** | | **51** | **4 个迁移文件** | **✅ 全部完成** |

---

## 一、现有接口一览（已可用）

| 模块 | 路由前缀 | 现有接口 |
|------|---------|---------|
| 鉴权 | `/api/v1/auth` | login, register, logout, refresh-token, forgot-password, reset-password, wx-login, wx-profile, bind-phone |
| 商品 | `/api/v1/item` | list, detail, hot, recommended, new, categories, search |
| 用户 | `/api/v1/user` | info, update-info, update-avatar, change-password, orders, favorites |
| 订单 | `/api/v1/order` | create, list, detail, cancel, confirm, refund, review, refund-detail |
| 支付 | `/api/v1/payment` | create, callback, status, refund |
| 购物车 | `/api/v1/cart` | list, add, update, remove, clear |
| 收藏 | `/api/v1/favorite` | list, add, remove, check |
| 地址 | `/api/v1/address` | list, add, update, delete, set-default |
| 通知 | `/api/v1/notification` | list, mark-read, mark-multiple-read, unread-count |
| 上传 | `/api/v1/upload` | image, file |
| 配置 | `/api/v1/config` | system, theme, payment, search-hot-words |
| 优惠券 | `/api/v1/coupon` | list, my-list |
| 积分商品 | `/api/v1/point-item` | list |
| 拼团 | `/api/v1/group-buy` | list, detail |
| 首页 | `/api/v1/mini-page` | home |

### 已有但未暴露 API 路由的 Service（后端逻辑已存在，只缺 Controller + Route）

| Service | 能力 | 状态 |
|---------|------|------|
| `SignService` | 每日签到、连续签到、签到积分计算 | ❌ 无 API 路由 |
| `UserMoneyService` | 余额增减、余额查询、冻结 | ❌ 无 API 路由 |
| `IntegralService` | 积分增减、积分流水、积分统计 | ❌ 无 API 路由 |
| `UserFeedbackService` | 用户反馈/意见反馈 | ❌ 无 API 路由 |
| `CommentService` | 商品评价管理 | ❌ 无 C 端 API |
| `ItemConsultationService` | 商品咨询/问答 | ❌ 无 C 端 API |
| `RechargePackageService` | 充值套餐管理 | ❌ 无 C 端 API |
| `RechargeOrderService` | 充值订单 | ❌ 无 C 端 API |
| `UserLevelService` | 用户等级体系 | ❌ 无 C 端 API |
| `AfterSalesService` | 售后申请与处理 | ❌ 无 C 端 API（仅 admin） |
| `ExpressService` | 物流追踪 | ❌ 无 C 端 API |

---

## 二、逐页差距分析

### 1. 个人中心（profile-mockup.html）

| # | 接口需求 | 方法 | 状态 | 说明 |
|---|---------|------|------|------|
| 1.1 | 用户综合信息 | `GET /api/v1/user/profile` | ⚠️ 需增强 | 现有 `getInfo` 只返回 User 原始字段；需聚合：等级信息(level_name, level_icon, discount)、余额、积分、优惠券未使用数、订单各状态数量、收藏数、足迹数 |
| 1.2 | 订单状态数量 | `GET /api/v1/order/status-counts` | ❌ 需新建 | 返回各状态订单数：待付款/待发货/待收货/待评价/退款中 |
| 1.3 | 签到状态 | `GET /api/v1/sign/status` | ❌ 需新建 | 今日是否已签到、连续天数、签到积分规则（SignService 已实现） |
| 1.4 | 执行签到 | `POST /api/v1/sign/do` | ❌ 需新建 | 调用 SignService::userSign()，返回获得积分（SignService 已实现） |
| 1.5 | 签到记录 | `GET /api/v1/sign/records` | ❌ 需新建 | 近 30 天签到日历（SignService 已实现） |
| 1.6 | 浏览足迹 | `GET /api/v1/user/footprint` | ❌ 需新建 | 分页返回浏览记录（ItemView 模型已存在，需加 Service + Controller） |
| 1.7 | 用户反馈 | `POST /api/v1/feedback/submit` | ❌ 需新建 | 提交意见反馈（UserFeedbackService 已实现，只缺 API） |
| 1.8 | 用户等级详情 | `GET /api/v1/user/level` | ❌ 需新建 | 当前等级、下一等级、升级进度、专属权益（UserLevelService 已实现） |

### 2. 购物车（cart-mockup.html）

| # | 接口需求 | 方法 | 状态 | 说明 |
|---|---------|------|------|------|
| 2.1 | 购物车列表（增强） | `GET /api/v1/cart/list` | ⚠️ 需增强 | 需返回：店铺分组、选中状态、小计金额、优惠提示、库存状态；现有只返回平铺列表 |
| 2.2 | 购物车数量徽标 | `GET /api/v1/cart/count` | ❌ 需新建 | 轻量接口，仅返回总件数（用于 tabbar 红点） |
| 2.3 | 选中/取消选中 | `POST /api/v1/cart/select` | ❌ 需新建 | 单个/批量选中切换（Cart 表需加 `is_selected` 字段） |
| 2.4 | 全选/取消全选 | `POST /api/v1/cart/select-all` | ❌ 需新建 | 一键全选 |
| 2.5 | 购物车结算预览 | `POST /api/v1/cart/checkout-preview` | ❌ 需新建 | 返回选中商品总价、可用优惠券、运费预估 |

### 3. 分类页（category-mockup.html）

| # | 接口需求 | 方法 | 状态 | 说明 |
|---|---------|------|------|------|
| 3.1 | 分类树 | `GET /api/v1/category/tree` | ❌ 需新建 | 返回三级分类树（一级+二级+三级），含图标和-banner（CategoryService 已实现 admin 端） |
| 3.2 | 分类下商品 | `GET /api/v1/category/items` | ❌ 需新建 | 按 category_id 分页查询商品，支持排序（综合/销量/价格）、价格区间筛选 |
| 3.3 | 分类推荐品牌 | `GET /api/v1/category/brands` | ❌ 需新建 | 该分类下品牌列表（可选，设计稿有展示） |

### 4. 商品详情（item-detail-mockup.html）

| # | 接口需求 | 方法 | 状态 | 说明 |
|---|---------|------|------|------|
| 4.1 | 商品详情（增强） | `GET /api/v1/item/detail` | ⚠️ 需增强 | 需补充：SKU 规格树、规格价格、库存、销量、限购信息、促销标签 |
| 4.2 | 商品评价列表 | `GET /api/v1/item/reviews` | ❌ 需新建 | 分页评价，含：评分分布（5/4/3/2/1 星各占比）、带图评价、追评（CommentService 已实现） |
| 4.3 | 商品咨询/问答 | `GET /api/v1/item/consultations` | ❌ 需新建 | 商品问答列表（ItemConsultationService 已实现） |
| 4.4 | 提交咨询 | `POST /api/v1/item/consult` | ❌ 需新建 | 用户提问（ItemConsultationService 已实现） |
| 4.5 | 相关推荐 | `GET /api/v1/item/related` | ❌ 需新建 | 同分类/同品牌相关商品推荐 |
| 4.6 | 记录浏览 | `POST /api/v1/item/view` | ❌ 需新建 | 记录用户浏览足迹（ItemView 模型已存在） |
| 4.7 | 商品规格价格 | `GET /api/v1/item/specs` | ❌ 需新建 | 独立接口返回 SKU 列表+价格+库存（可选合并到 detail） |

### 5. 订单列表（order-list-mockup.html）

| # | 接口需求 | 方法 | 状态 | 说明 |
|---|---------|------|------|------|
| 5.1 | 订单列表（增强） | `GET /api/v1/order/list` | ⚠️ 需增强 | 需支持 tab 状态过滤（all/pending_payment/pending_shipment/pending_receipt/pending_review/refunding），返回商品缩略图、可操作按钮列表 |
| 5.2 | 再次购买 | `POST /api/v1/order/reorder` | ❌ 需新建 | 将历史订单商品加入购物车 |
| 5.3 | 订单物流 | `GET /api/v1/order/logistics` | ❌ 需新建 | 物流公司+运单号+轨迹（ExpressService 已实现） |
| 5.4 | 提醒发货 | `POST /api/v1/order/remind-ship` | ❌ 需新建 | 记录提醒时间，防频繁催促 |
| 5.5 | 删除订单 | `POST /api/v1/order/delete` | ❌ 需新建 | 已完成/已关闭订单的删除（软删除） |

### 6. 优惠券（coupon-mockup.html）

| # | 接口需求 | 方法 | 状态 | 说明 |
|---|---------|------|------|------|
| 6.1 | 可领优惠券列表 | `GET /api/v1/coupon/available` | ❌ 需新建 | 可领取的优惠券列表，含领取状态、剩余数量 |
| 6.2 | 领取优惠券 | `POST /api/v1/coupon/claim` | ❌ 需新建 | 领取一张优惠券（UserCouponService 已实现） |
| 6.3 | 优惠券统计 | `GET /api/v1/coupon/stats` | ❌ 需新建 | 未使用/已使用/已过期 各数量 |
| 6.4 | 我的优惠券（增强） | `GET /api/v1/coupon/my-list` | ⚠️ 需增强 | 现有已实现，但需补充：使用门槛说明、适用商品范围、有效期格式化 |

### 7. 积分商城（point-exchange-mockup.html）

| # | 接口需求 | 方法 | 状态 | 说明 |
|---|---------|------|------|------|
| 7.1 | 积分商城首页 | `GET /api/v1/point/center` | ❌ 需新建 | 聚合：积分余额、签到状态、积分任务列表、分类商品 |
| 7.2 | 积分任务列表 | `GET /api/v1/point/tasks` | ❌ 需新建 | 任务列表：签到/完善资料/首次下单/评价商品等，含完成状态和奖励积分 |
| 7.3 | 积分兑换商品列表 | `GET /api/v1/point-item/list` | ⚠️ 需增强 | 现有已实现，需补充：分类筛选、兑换方式（纯积分/积分+金额）、已兑换数 |
| 7.4 | 积分兑换 | `POST /api/v1/point-item/exchange` | ❌ 需新建 | 兑换商品（扣积分 + 创建兑换订单），IntegralService 已实现扣减 |
| 7.5 | 兑换记录 | `GET /api/v1/point-item/records` | ❌ 需新建 | 用户兑换记录列表 |
| 7.6 | 积分流水 | `GET /api/v1/user/integral-log` | ❌ 需新建 | 积分明细：获得/消耗/过期（IntegralService::getIntegralLog 已实现） |
| 7.7 | 积分统计 | `GET /api/v1/user/integral-stats` | ❌ 需新建 | 总获得/总消耗/本月获得（IntegralService::getIntegralStats 已实现） |

### 8. 用户余额（balance-mockup.html）

| # | 接口需求 | 方法 | 状态 | 说明 |
|---|---------|------|------|------|
| 8.1 | 余额信息 | `GET /api/v1/user/balance` | ❌ 需新建 | 余额、冻结金额、可用余额（UserMoneyService::getBalance 已实现） |
| 8.2 | 余额流水 | `GET /api/v1/user/money-log` | ❌ 需新建 | 收入/支出流水分页（UserMoneyLogService 已实现） |
| 8.3 | 充值套餐 | `GET /api/v1/recharge/packages` | ❌ 需新建 | 充值套餐列表（RechargePackageService 已实现） |
| 8.4 | 创建充值 | `POST /api/v1/recharge/create` | ❌ 需新建 | 选择套餐创建充值订单→调起支付（RechargeOrderService 已实现） |
| 8.5 | 提现申请 | `POST /api/v1/withdraw/apply` | ❌ 需新建 | 用户提现申请（AgentWithdrawService 有类似逻辑可复用） |
| 8.6 | 收支统计 | `GET /api/v1/user/money-stats` | ❌ 需新建 | 本月收入/支出/累计收入统计 |

---

## 三、数据库变更

| # | 表 | 变更 | 说明 |
|---|-----|------|------|
| D1 | `yxshop_carts` | ADD COLUMN `is_selected` tinyint(1) DEFAULT 1 | 购物车选中状态 |
| D2 | `yxshop_carts` | ADD COLUMN `store_id` bigint DEFAULT 0 | 店铺分组（多商户场景） |
| D3 | `yxshop_item_views` | ADD COLUMN `view_count` int DEFAULT 1 | 同一商品重复浏览累计 |
| D4 | 新建 `yxshop_point_tasks` | 积分任务表 | 任务名/类型/奖励积分/完成条件/排序/状态 |
| D5 | 新建 `yxshop_user_point_tasks` | 用户任务完成记录 | user_id/task_id/status/completed_at |
| D6 | 新建 `yxshop_point_exchange_orders` | 积分兑换订单 | user_id/item_id/spec_key/points_cost/money_cost/status |
| D7 | 新建 `yxshop_withdraws` | 用户提现申请 | user_id/amount/bank_info/status/audit_at |
| D8 | 新建 `yxshop_order_reminds` | 催发货记录 | order_id/user_id/last_remind_at |

> 注：D4-D8 中部分表可能已有对应 admin 端表（如 `yxshop_agent_withdraws`），需确认是否复用。

---

## 四、新增 Controller / 路由清单

| Controller | 路由组 | 新建方法数 |
|-----------|--------|-----------|
| `SignController` | `/api/v1/sign` | 3 (status, do, records) |
| `FootprintController` | `/api/v1/user/footprint` | 2 (list, clear) |
| `FeedbackController` | `/api/v1/feedback` | 2 (submit, my-list) |
| `CategoryController` | `/api/v1/category` | 3 (tree, items, brands) |
| `ItemReviewController` | `/api/v1/item/reviews` | 2 (list, summary) |
| `ItemConsultationController` | `/api/v1/item/consultations` | 2 (list, create) |
| `RechargeController` | `/api/v1/recharge` | 3 (packages, create, records) |
| `WithdrawController` | `/api/v1/withdraw` | 2 (apply, records) |
| `PointTaskController` | `/api/v1/point/tasks` | 2 (list, claim) |
| `PointExchangeController` | `/api/v1/point-item` | 2 (exchange, records) |
| 现有增强 | - | `UserController` +4, `OrderController` +4, `CartController` +4, `CouponController` +3, `ItemController` +3, `PointItemController` +1 |

**合计：约 10 个新 Controller + 25 个新接口 + 15 个现有接口增强 + 8 项数据库变更**

---

## 五、建议实施优先级（按前端落地顺序）

### P0 — 核心交易链路（必须先做）
> 没有这些接口，购物车→下单→支付 的主流程跑不通

1. 购物车增强：选中状态 + 结算预览 + 数量徽标（2.1-2.5）
2. 分类树 + 分类商品（3.1-3.2）
3. 商品详情增强：SKU 规格 + 库存（4.1, 4.7）
4. 订单列表增强：tab 过滤 + 状态计数（1.2, 5.1）

### P1 — 用户中心基础
> 个人中心页面能渲染完整数据

5. 用户综合信息 profile（1.1）
6. 浏览足迹（1.6）
7. 订单操作：再次购买 + 物流 + 删除（5.2-5.5）
8. 商品评价 + 咨询 + 相关推荐（4.2-4.6）

### P2 — 营销与会员
> 优惠券、积分、签到、余额

9. 优惠券领取 + 统计（6.1-6.4）
10. 签到功能（1.3-1.5）
11. 积分商城完整链路（7.1-7.7）
12. 余额：流水 + 充值 + 提现（8.1-8.6）

### P3 — 锦上添花
13. 用户等级详情（1.8）
14. 用户反馈（1.7）
15. 积分任务体系（D4-D5 表 + 7.2）
