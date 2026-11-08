# YXShop

YXShop 的统一项目仓库，包含 PHP/Webman 后端、微信小程序、PC 官网与 H5 商城。

## 项目目录

| 目录 | 内容 | 入口文档 |
|---|---|---|
| `yxshop-php/` | Webman API、管理后台静态资源、数据库初始化脚本 | [README](yxshop-php/README.md) |
| `yxshop-miniprogram/` | 原生微信小程序前端 | [README](yxshop-miniprogram/README.md) |
| `yxshop-web/` | PC 品牌官网和 H5 商城 | [README](yxshop-web/README.md) |

各端通过后端 `/api/v1/*` API 共用商品、订单、用户和库存数据。

## 本地运行

### 1. 启动后端

需要 PHP 8.1+、MySQL 和 Redis。首次运行时：

```bash
cd yxshop-php
composer install
cp .env.example .env
```

在 `.env` 配置数据库、Redis、JWT 和需要的微信服务参数，然后启动：

```bash
php start.php start
```

后端默认监听 `http://127.0.0.1:8777`。数据库初始化说明见后端 README 和 `database/`。

### 2. 启动 PC/H5 网站

另开终端：

```bash
cd yxshop-web
npm start
```

默认访问 `http://127.0.0.1:8080/`；H5 商城地址为 `http://127.0.0.1:8080/h5.html`。

### 3. 打开微信小程序

在微信开发者工具中导入 `yxshop-miniprogram/`。本地专属配置文件 `project.private.config.json` 不纳入版本控制；需要时在本机创建。

## 仓库约定

- 后端依赖、运行日志、上传文件、环境变量和本机小程序配置不提交到 Git。
- 三个子项目保留各自原有 README、目录结构和 Git 提交历史。
- 历史拆分仓库继续保留为独立备份；统一仓库的后续开发以本仓库为准。
