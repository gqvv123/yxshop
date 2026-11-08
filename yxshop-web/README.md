# 盈锐商贸 · PC 品牌官网 + H5 移动商城

一套零依赖的纯静态前端，直连项目现有的 Webman 后端（`/api/v1/*`）。
PC 端做品牌展示与小程序引导，移动端 H5 提供完整购物流程；两者与微信小程序、管理后台共用同一份真实数据。

## 目录

```
yxshop-web/
├── package.json              # 启动脚本（无第三方依赖）
├── server.js                 # 静态服务 + /api/v1 同源代理
├── README.md
└── public/
    ├── index.html            # PC 品牌官网
    ├── h5.html               # H5 移动商城（Hash SPA）
    └── assets/
        ├── base.css          # 设计变量与共用组件
        ├── site.css          # 官网样式
        ├── h5.css            # H5 样式
        ├── api.js            # 统一请求封装 / token / 接口清单
        ├── icons.js          # 内联 SVG 图标（无 emoji、无外链）
        ├── site.js           # 官网逻辑
        ├── h5.js             # H5 路由、页面与交互
        └── img/*.svg         # 全部本地视觉资产
```

## 启动

后端需先运行（项目根目录 `yxshop-php`）：

```bash
cd yxshop-php && php start.php start -d      # 监听 127.0.0.1:8777
```

再启动站点：

```bash
cd yxshop-web && npm start                    # 等价于 node server.js，监听 8080
```

- PC 官网：<http://127.0.0.1:8080/>
- H5 商城：<http://127.0.0.1:8080/h5.html>

`server.js` 把 `/api/v1/*` 代理到后端，浏览器始终同源，不依赖 CORS。
其它端口或后端地址：

```bash
PORT=8081 YXSHOP_API_ORIGIN=http://192.168.1.10:8777 node server.js
```

前端也支持运行时覆盖 API 地址（用于指向公网后端）：

- 地址栏加 `?api=https://your-api-host`
- 或 `localStorage.setItem('yxshop_api_base', 'https://your-api-host')`

## 页面

| 端 | 页面 | 说明 |
|---|---|---|
| PC | 首页 | 首屏、精选商品、平台能力、多端引导、关于 |
| H5 | `#/home` | 搜索、分类入口、推荐商品（真实库存与价格） |
| H5 | `#/category` | 分类树 + 分类商品 |
| H5 | `#/detail?id=` | 商品详情、数量、加购、立即购买 |
| H5 | `#/cart` | 勾选、改数量、删除、结算 |
| H5 | `#/checkout` | 地址、费用明细、提交订单 |
| H5 | `#/orders` / `#/order-detail?id=` | 订单列表与详情 |
| H5 | `#/address` | 收货地址列表与新增 |
| H5 | `#/login` | 登录 / 注册（自动写入 token） |
| H5 | `#/me` | 用户中心、订单与地址入口、退出登录 |

所有列表都有 **加载骨架 / 空状态 / 错误状态与重试**；接口失败不会白屏。

## 用到的真实接口

`/api/v1/item/list`、`/item/detail`、`/category/tree`、`/category/items`、
`/auth/login`、`/auth/register`、`/user/info`、
`/cart/list`、`/cart/count`、`/cart/add`、`/cart/update`、`/cart/remove`、`/cart/select`、`/cart/checkout-preview`、
`/address/list`、`/address/add`、
`/order/create`、`/order/list`、`/order/detail`。

商品、库存、价格、运费、订单与地址全部以后端返回为准，前端不做任何模拟数据兜底。

## 演示账号

不预置账号。在 H5 `#/login` 切到「注册」创建即可，注册成功会自动登录；
账号密码走真实 `/api/v1/auth/register` 与 `/api/v1/auth/login`。

## 视觉与依赖

- 纯 HTML / CSS / 原生 JS，**无构建步骤、无 npm 依赖、无 CDN、无外链图片**。
- 所有插图为项目内 `assets/img/*.svg`；商品无图时按序号回落到本地占位 SVG。
- 移动端容器最大宽度 480px，桌面访问自动居中；使用 `100dvh` 与 `env(safe-area-inset-bottom)`。
- 已处理 `prefers-reduced-motion`；图标为内联 SVG，不使用 emoji。

## 在线预览说明

发布后的在线地址用于查看界面与交互。由于后端 Webman 运行在本地 `127.0.0.1:8777`，
在线环境无法直接访问该地址，页面会显示「无法连接后端服务」的状态提示。
需要在线完整体验时，把后端部署到公网并用 `?api=https://your-api-host` 指向它，
或直接使用上面启动的本地地址。
