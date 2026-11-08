# yxshop-miniprogram · 盈锐小程序商城（前端）

基于原生微信小程序的商城前端，对接 [yxshop-php](../yxshop-php) Webman 后端 `/api/v1/*` 接口。

---

## ✨ 功能矩阵

| 模块 | 页面 | 后端接口 |
|---|---|---|
| 入口 | 首页（装修页组件化）/ 分类 / 搜索 / 商品列表 | `mini-page.home`、`item.*`、`category.*` |
| 商品 | 商品详情、SKU 选择、加购、立即购买、产品参数、评价、咨询 | `item.getDetail`、`item.getReviews`、`item.getConsultations` |
| 品牌 | 品牌专区（网格 + 搜索）、按品牌筛选商品 | `brand.list`、`category.brands`、`item.getList?brand_id=` |
| 标签 | 商品列表标签筛选 | `item.getTags` |
| 购物车 | 列表、勾选、改数、删除、结算预览、tabbar 角标 | `cart.*`、`cart.count` |
| 下单 | 订单确认、优惠券选择、会员折扣、配送方式与运费、提交 | `order.preview`、`order.create`、`delivery.*` |
| 支付 | `wx.requestPayment` 微信支付、余额充值 | `payment.*`、`recharge.*` |
| 订单 | 列表（多状态 tab）、详情、取消 / 确认收货 / 退款 / 再次购买 / 评价 | `order.*` |
| 售后 | 申请退款、售后进度 | `order.refund`、`order.refund-detail` |
| 物流 | 物流轨迹 | `order.logistics`、`delivery.*` |
| 地址 | 列表、新增、编辑、设默认、`chooseAddress` 一键导入 | `address.*` |
| 会员 | 会员中心（等级体系、升级进度、等级权益） | `user.level` |
| 资产 | 钱包（充值套餐 / 提现）、资金记录（充值 / 提现 / 余额流水） | `balance.*`、`withdraw.*` |
| 营销 | 优惠券（领券 + 我的券）、促销活动、积分商城 / 签到 / 任务 | `coupon.*`、`promotion.*`、`point.*`、`sign.*` |
| 内容 | 帮助中心 / 公告（文章分类 + 详情）、用户反馈、关于我们 | `article.*`、`feedback.*` |
| 发票 | 发票抬头管理、开票申请、开票记录 | `invoice.*` |
| 收藏 / 足迹 | 收藏列表、浏览记录 | `favorite.*`、`user.footprint` |
| 通知 | 站内消息、未读数、标记已读 | `notification.*` |
| 个人 | 登录 / 注册 / 退出 / 资料 / 安全设置 / 实名认证 / 合同 | `auth.*`、`user.*`、`security.*`、`verify.*` |
| 系统 | 业务版本检查（启动时）、系统配置 | `version.*`、`config.*` |

---

## 🗂 目录结构

```
yxshop-miniprogram/
├── app.js / app.json / app.wxss      全局入口与样式
├── sitemap.json
├── project.config.json               微信开发者工具项目配置
├── config/                           环境配置（apiBase / appId / 版本）
│   └── index.js
├── utils/
│   ├── request.js                    请求封装：Bearer token、统一错误、上传
│   ├── auth.js                       登录态（token / refreshToken / user）
│   ├── storage.js                    存储封装（带 yxshop: 前缀）
│   └── format.js                     价格 / 日期 / 状态等格式化
├── api/                              12 个业务模块（与 yxshop-php 路由 1:1）
│   ├── auth.js, user.js, item.js, cart.js,
│   ├── order.js, payment.js, address.js, favorite.js,
│   ├── notification.js, upload.js, config.js, version.js
│   └── index.js                      聚合导出
├── components/
│   ├── goods-card/                   商品卡片（grid / list 两种布局）
│   └── empty/                        空状态
└── pages/
    ├── home/, category/, cart/, profile/      tabbar 4 页
    ├── login/, item-detail/, item-list/, search/
    ├── order/{confirm,list,detail}/
    ├── address/{list,edit}/
    ├── favorite/, notification/, webview/
```

---

## 🚀 快速上手

### 1. 准备后端

启动 yxshop-php：

```bash
cd ../yxshop-php
composer install
cp .env.example .env  # 调整数据库 / Redis / JWT_SECRET
php start.php start
```

确认接口可访问：

```bash
curl http://127.0.0.1:8777/health
```

### 2. 配置 apiBase

编辑 [config/index.js](config/index.js)：

```js
development: {
  apiBase: 'http://127.0.0.1:8777',
},
production: {
  apiBase: 'https://api.yxshop.com',
},
```

> 微信开发者工具 → 详情 → 本地设置 → 勾选「**不校验合法域名**」即可使用 HTTP 调试。
> 上线前，请把正式 HTTPS 域名加入「服务器域名」白名单（开发者后台）。

### 3. 用微信开发者工具打开

1. 打开 [微信开发者工具](https://developers.weixin.qq.com/miniprogram/dev/devtools/download.html)。
2. 「导入项目」→ 选择本目录 `yxshop-miniprogram`（用你本机的实际路径）。
3. AppID 选择「**测试号**」即可调试，正式发布前替换为自己的 AppID（修改 `project.config.json`）。
4. 顶部「编译」即可看到首页。

### 4. 补充资源（可选）

tabBar 图标已内置于 `assets/tabbar/`（10 张 81×81 单色 PNG），如需替换可直接覆盖同名文件。

---

## 🧩 与后端接口的契约

请求层 [utils/request.js](utils/request.js) 已和 yxshop-php 的响应格式对齐：

```json
{ "code": 0, "message": "success", "data": { ... } }
```

- `code === 0` 视为业务成功，`data` 直接被 resolve。
- 任何业务错误：弹 toast 并 reject `Error`（含 `error.code`）。
- HTTP 401：清除本地 token，跳 `/pages/login/login`。
- 全部接口默认带上 `Authorization: Bearer <token>`，登录/注册接口手动加 `skipAuth: true`。

---

## 🪪 鉴权流程

```
Login → 后端返回 { token, refresh_token, expires_in, user }
     ↓
auth.saveAuth() 写入本地 storage
     ↓
后续 API 自动加 Authorization
     ↓
HTTP 401 / token 过期 → 自动清登录态 → 跳登录页
```

如需主动调用刷新 token：

```js
const r = await api.auth.refreshToken(auth.getRefreshToken());
auth.saveAuth(r);
```

### 微信一键登录（推荐）

登录页首选「微信一键登录」，链路如下：

```
wx.login() → code
   ↓ utils/wx-login.js loginWithWx()
POST /api/v1/auth/wx-login { code, app_id }
   ↓ 后端 UserAuthService::wxLogin
   jscode2session → openid/unionid → 查/建 user → 签发 JWT
   ↓
{ token, refresh_token, expires_in, is_new_user, user }
```

- **后端必须先配置**：`yxshop-php/.env` 里的 `WECHAT_MINI_APPID` / `WECHAT_MINI_SECRET`，否则接口返回「小程序未配置 appid/secret」。
- 静默登录即可拿到 token；昵称头像在「我的 → 编辑资料」里用微信新版 `chooseAvatar` + `type="nickname"` input 主动授权，再调 `POST /api/v1/auth/wx-profile` 同步。
- 工具函数：[utils/wx-login.js](utils/wx-login.js) 的 `loginWithWx({ profile?, app_id? })`。

### 微信手机号一键绑定

「我的 → 手机号」用 `button open-type="getPhoneNumber"` 触发，回调拿到 `code`（新版）后调
`POST /api/v1/auth/bind-phone`，后端用小程序 `access_token` 调微信 `getuserphonenumber` 直接换手机号入库。

```
button getPhoneNumber → e.detail.code
   ↓ api.auth.bindPhone({ code })
POST /api/v1/auth/bind-phone（需登录）
   ↓ 后端 getMiniAccessToken() → getuserphonenumber(code)
   → 手机号唯一性校验 → 写入 user.phone
```

- 兼容旧版：若 `e.detail` 只返回 `encryptedData + iv`，前端自动改传该字段，后端用登录时缓存的 `session_key` 解密。
- 该接口需登录态（已挂 `ApiAuthMiddleware`），请先完成微信一键登录。

---

## 💳 微信支付集成

`pages/order/confirm/confirm.js` 提交订单后会自动调用 `api.payment.create`。后端返回的支付参数若包含
`timeStamp / nonceStr / package / paySign`，前端调用 `wx.requestPayment` 拉起。

后端 [PayService::handleNotify](../yxshop-php/app/service/PayService.php) 接收回调并验签后更新订单状态，前端不需要额外处理。

---

## 🛠 常见问题

**❓ 启动后接口报 `请求过于频繁`？**
后端 ApiRateLimitMiddleware 默认对 `/api/v1/auth/*` 限流到 5 次/分钟。开发期可调大或临时关闭：
[yxshop-php/app/middleware/ApiRateLimitMiddleware.php](../yxshop-php/app/middleware/ApiRateLimitMiddleware.php)。

**❓ 报错「未提供认证令牌」？**
当前页所在路由要求登录但本地 token 已被清，回到 `/pages/login/login` 重新登录即可。

**❓ 商品列表 / 分类返回空？**
检查后端是否已完整导入 `yxshop-php/database/yxshop_open_source_default.sql`（含种子数据）。该仓库不提供独立的 seed_*.sql 脚本。

**❓ 想换主题色？**
在 [app.wxss](app.wxss) 顶部的 CSS 变量统一改：

```css
--primary: #ff5043;   /* 主色 */
--primary-light: #ff8b81;
--price: #ff3030;
```

---

## 📜 LICENSE

MIT License（与后端项目一致）。
