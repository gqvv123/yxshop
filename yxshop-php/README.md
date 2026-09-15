# YXShop 开源版 — 微信小程序商城系统

基于 **Webman** (PHP 8.1+) + **Vue 3** (Arco Design) 的高性能微信小程序商城系统，采用四层架构（Controller → Service → Repository → Model），开箱即用。

## 技术栈

| 层 | 技术 | 说明 |
|----|------|------|
| 后端框架 | Webman 2.x | 常驻内存，高性能 HTTP 服务 |
| ORM | Illuminate/Database (Laravel Eloquent) | 120+ 模型 |
| 认证 | JWT (tinywan/jwt) | 管理端 + C端 双 JWT 体系 |
| 缓存 | Redis | 配置缓存、登录锁、验证码、Session |
| 队列 | Redis Queue | 异步任务、通知发送 |
| WebSocket | webman/push + Gateway Worker | 实时消息推送 |
| 支付 | yansongda/pay (微信支付 V3) | 支付、退款、回调签名验证 |
| 微信 | easywechat 6.x | 小程序登录、消息推送、模板通知 |
| 前端框架 | Vue 3 + Vite + Arco Design | Material 3 风格管理后台 |
| 数据库 | MySQL 5.7+ / 8.x | 单库 120+ 表 |

## 项目结构

```
yxshop-php/
├── app/
│   ├── admin/controller/     # 后台管理控制器 (71个)
│   ├── api/v1/controller/    # C端 API 控制器 (28个)
│   ├── command/              # CLI 命令
│   ├── common/               # 公共类 (Error, StatusEnum等)
│   ├── controller/           # 通用端点 (支付回调等)
│   ├── Enums/                # PHP 8.1 枚举类
│   ├── exception/            # 自定义异常体系
│   ├── middleware/           # 中间件 (14个)
│   ├── model/                # Eloquent 模型 (120+个)
│   ├── process/              # Webman 进程
│   ├── repository/           # 数据仓储层 (110+个)
│   ├── service/              # 业务服务层 (100+个)
│   ├── traits/               # 可复用 Trait
│   ├── utility/              # 工具类
│   ├── validate/             # 数据验证器 (29个)
│   └── websocket/            # WebSocket 处理
├── config/
│   └── route/
│       ├── admin_api.php     # 后台管理 API 路由
│       └── api.php           # C端 API 路由
├── database/
│   └── yxshop_open_source_default.sql  # 默认数据库
├── .env                       # 环境配置
└── start.php                  # 启动入口
```

## 四层架构

系统严格遵循 **Controller → Service → Repository → Model** 四层架构：

- **Controller** — 接收请求、参数校验、返回响应。禁止直接操作 Model
- **Service** — 业务逻辑编排层，调用 Repository 完成业务用例
- **Repository** — 数据访问层，封装 Model 查询
- **Model** — Eloquent 模型，仅定义表结构和关系

## 功能模块

### 商品管理

- 商品 CRUD、批量操作（上下架、调价、库存调整、删除）
- 多规格 SKU（规格-规格值-价格-库存-库存日志）
- 商品分类（树形）、品牌、标签、属性、类型
- 商品收藏、商品搜索记录、商品咨询
- 商品评价管理（回复、审核、导出）
- 页面 SEO 配置

### 订单管理

- 订单全生命周期（待付款→待发货→已发货→已完成→已关闭）
- 订单改价、免运费、发货、审核、退款
- 售后服务管理（退款/退货/换货 + 日志）
- 订单配送、物流追踪
- 订单日志记录

### 用户系统

- 用户管理（查看、地址、余额日志）
- 用户等级（等级规则、自动升级）
- 用户优惠券、用户反馈
- 用户日志、用户标签
- C端：注册登录、微信小程序一键登录、地址管理
- C端：购物车、收藏、签到、积分任务

### 支付系统

- 微信支付 V3（JSAPI 支付、退款、回调验签）
- 支付日志管理
- 充值套餐
- 后台支付配置（证书上传、连接测试）

### 营销系统

- 优惠券管理（通用券、商品券、分类券）
- 促销活动管理（商品促销、订单促销）
- 营销数据概览（统计、渠道、洞察）
- 充值套餐

### 通知系统

- 通知模板（多渠道：微信模板消息、短信、邮件）
- 通知场景（场景绑定模板）
- 通知变量（模板变量管理）
- 通知发送记录
- 通知黑名单
- 通知配置

### 内容管理

- 文章管理 + 文章分类
- 内容页面管理
- 广告位管理
- 友情链接
- 页面 SEO

### 小程序装修

- 可视化页面装修（拖拽式组件编辑器）
- 内置组件：基础组件、电商组件、营销组件
- 页面模板、页面版本管理（回滚）
- 底部 TabBar 配置
- 主题管理

### 系统管理

- 管理员管理、角色权限（RBAC）
- 菜单管理（动态菜单树）
- 文件管理（上传、分组）
- 定时任务管理
- 多语言管理
- 货币管理、地区管理
- 系统设置（基础信息、交易规则、支付配置、区域设置、登录设置）
- 日志管理（登录日志、操作日志、用户日志、系统日志、API 日志、邮件日志、短信日志、文件日志）
- 财务统计、数据报表

### AI 能力模块

系统内置 AI 能力，支持 OpenAI 和 Anthropic (Claude) 两种服务商：

| 功能 | 说明 |
|------|------|
| AI 商品文案生成 | 一键生成商品标题、描述、卖点文案 |
| AI 数据分析 | 分析销售数据，生成运营建议 |
| 算力管理 | 查看算力余额、购买算力包 |

配置方式：在管理后台「AI 设置」页面填写，或通过 `.env` 环境变量配置。

### 登录设置

- 会话超时时间（15~1440 分钟）
- 登录验证码类型（数字字母 / 拼图滑块）

## 快速开始

### 环境要求

- PHP >= 8.1
- MySQL >= 5.7
- Redis >= 5.0
- Composer 2.x
- Node.js >= 18（前端构建）

### 后端部署

1. **配置环境**

   ```bash
   cp .env.example .env
   ```

   编辑 `.env`，配置数据库、Redis、微信支付等参数。

2. **安装依赖**

   ```bash
   composer install
   ```

3. **导入数据库**

   ```bash
   mysql -u root -p yxshop_free < database/yxshop_open_source_default.sql
   ```

4. **启动服务**

   ```bash
   # Linux / macOS
   php start.php start

   # Windows
   start.bat
   ```

   默认端口 `8777`，WebSocket 端口 `3131`，API 端口 `3232`。

### 前端部署

```bash
cd yxshop-admin
npm install
npm run dev    # 开发模式
npm run build  # 生产构建
```

开发模式默认运行在 `http://localhost:5173`。

### 默认账号

- 管理后台：`admin` / `Yxsh0p@2024!Adm1n`
- ⚠️ **部署后请立即修改默认密码**

## 核心配置

### 环境变量（.env）

```env
APP_MODE=demo              # demo / production
APP_DEBUG=false            # 生产环境必须 false
APP_EDITION=open_source    # 开源版固定为 open_source

# 数据库
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=yxshop_free
DB_USERNAME=root
DB_PASSWORD=

# Redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# JWT
JWT_SECRET=your_jwt_secret

# CORS
CORS_ORIGINS=http://localhost:5173,http://localhost:8777

# 微信支付
WECHAT_APP_ID=your_app_id
WECHAT_MCH_ID=your_mch_id
WECHAT_KEY=your_key
WECHAT_V3_KEY=your_v3_key
WECHAT_SERIAL_NO=your_serial_no

# 微信小程序
WECHAT_MINI_APPID=your_mini_appid
WECHAT_MINI_SECRET=your_mini_secret

# AI 配置
AI_DEFAULT_PROVIDER=openai
AI_OPENAI_API_KEY=sk-xxx
AI_OPENAI_MODEL=gpt-4o
```

## 中间件架构

| 中间件 | 作用 |
|--------|------|
| LicenseMiddleware | 授权校验（开源版基础校验） |
| AdminAuthMiddleware | 后台 JWT 认证 |
| AdminCorsMiddleware | 后台 CORS |
| ApiAuthMiddleware | C端 JWT 认证 |
| ApiCorsMiddleware | C端 CORS |
| ApiRateLimitMiddleware | C端限流 |
| OperationLogMiddleware | 操作日志记录 |
| ApiAccessLogMiddleware | API 访问日志 |
| AdminPermissionMiddleware | 后台权限校验 |

## 版本体系

系统采用 **开源版 / 商业版 / 企业版** 三层架构：

| 版本 | 定位 | 适用场景 |
|------|------|----------|
| **开源版** (当前) | 单店微商城 | 个人创业者、小型团队 |
| 商业版 | 增值营销模块 | 品牌商家、成长型电商 |
| 企业版 | 多租户 SaaS + 供应链 | 大型企业、平台运营商 |

开源版基于 Apache 2.0 协议开源。商业版和企业版通过条件加载目录扩展，升级时复制对应目录即可激活，无需修改开源代码。

### 商业版模块（增值功能）

| 模块 | 功能 |
|------|------|
| 分销裂变 | 多层级分销、推荐返佣、佣金结算 |
| 限时秒杀 Pro | 秒杀场次、Redis 队列预扣防超卖 |
| 高级会员卡 | 会员卡管理、充值规则、权益配置 |
| 社区养老 | 服务对象、食堂订餐、健康档案 |
| 赠品活动 | 赠品管理、赠品商品配置 |
| 拼团 | 拼团活动、成员管理 |
| 营销游戏 | 营销游戏、奖品配置 |

### 企业版模块

| 模块 | 功能 |
|------|------|
| 多店铺系统 | 商家入驻、店铺管理、分账结算 |
| 供应链管理 | 供应商、采购、调拨、库存大盘 |
| 企业审计追踪 | 全表数据变更追踪、合规报告 |
| 细粒度权限 | RBAC 权限矩阵、数据权限、字段级权限 |
| 商品审核流程 | 多级审核、规则引擎、驳回记录 |
| 数据分析增强 | 营销 ROI、转化漏斗、多维报表 |
| 代理分润 | 代理等级、分润规则、提现审核 |

> 以上模块在开源版中不可用。如需升级，请联系获取商业版/企业版代码包，放入 `commercial/` 和 `enterprise/` 目录即可自动激活。

## 目录说明

```
yxshop/                         # 项目根目录
├── yxshop-php/                 # 后端 (PHP / Webman)
├── yxshop-admin/               # 管理后台前端 (Vue 3 + Arco Design)
├── COMMERCIAL_VS_OPENSOURCE_DIFF.md  # 商业版与开源版差异对比
└── .env                        # 环境配置
```

## 技术特性

- **常驻内存**：Webman 常驻进程，无 FCGI 开销，QPS 数倍于传统 PHP-FPM 框架
- **四层架构**：严格分层，Controller 零业务逻辑，Service 可独立测试
- **120+ Eloquent 模型**：完整的关系定义，支持预加载优化
- **JWT 双认证体系**：管理端与 C端 独立 JWT，互不干扰
- **Redis 全栈**：缓存、队列、Session、验证码、登录锁
- **WebSocket 推送**：webman/push 实时消息推送
- **定时任务**：workerman/crontab 进程内定时器，无需系统 cron
- **国际化**：管理后台 i18n 支持（zh_CN / zh_TW / en）
- **Material 3 设计**：管理后台采用 Material Design 3 风格

## 许可证

[Apache License 2.0](LICENSE)

Copyright 2024 YXShop
