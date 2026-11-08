# YXShop 生产环境部署指南

## 一、服务器要求

| 组件 | 最低版本 | 推荐 |
|------|---------|------|
| PHP | 8.1+ | 8.2+ |
| MySQL | 5.7+ | 8.0+ |
| Redis | 5.0+ | 7.0+ |
| Nginx | 1.18+ | 1.24+ |
| Composer | 2.x | 最新 |
| Node.js | 16+ | 18+ (仅构建前端用) |

### PHP 必需扩展
```
php -m | grep -E 'pdo_mysql|redis|bcmath|gd|mbstring|openssl|curl|fileinfo|iconv|json|xml'
```

---

## 二、部署步骤

### 1. 上传代码
```bash
# 克隆或上传项目到服务器
git clone <repo> /wwwroot/yxshop
cd /wwwroot/yxshop/yxshop-php
```

### 2. 安装依赖
```bash
composer install --no-dev --optimize-autoloader
```

### 3. 配置环境变量
```bash
# 复制生产环境模板
cp .env.production .env

# 编辑 .env，逐项填写真实值
vi .env
```

**必须填写的项（标注 `<<填写>>`）：**
- `JWT_SECRET` — 生成方式：`php -r "echo bin2hex(random_bytes(32));"`
- `DB_HOST` / `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD`
- `REDIS_PASSWORD`
- `DOMAIN` — 你的 HTTPS 域名
- `CORS_ORIGINS` — 你的 HTTPS 域名
- `WECHAT_MINI_SECRET` — 小程序 AppSecret
- 支付宝/微信支付凭证（如已有）
- `CURL_CA_BUNDLE` — 下载 cacert.pem：https://curl.se/docs/caextract.html

### 4. 导入数据库
```bash
# 创建数据库
mysql -u root -p -e "CREATE DATABASE yxshop_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 导入基础表结构
mysql -u root -p yxshop_prod < database/yxshop.sql

# 执行迁移脚本（按编号顺序）
for f in database/migrations/*.sql; do mysql -u root -p yxshop_prod < "$f"; done
```

### 5. 构建后台管理前端（如需重新构建）
```bash
cd admin-vue
yarn install
yarn build
# 将 dist 内容复制到 public/yxadmin/
cp -r dist/* ../public/yxadmin/
```

### 6. 配置小程序
1. 编辑 `yxshop-miniprogram/config/index.js`，将 `production.apiBase` 改为你的 HTTPS 域名
2. 用微信开发者工具打开 `yxshop-miniprogram` 目录
3. 在「微信公众平台 → 开发管理 → 开发设置」中配置服务器域名：
   - request 合法域名：`https://your-domain.com`
   - uploadFile 合法域名：`https://your-domain.com`
   - downloadFile 合法域名：`https://your-domain.com`

---

## 三、Nginx 配置参考

```nginx
server {
    listen 443 ssl http2;
    server_name your-domain.com;

    ssl_certificate     /path/to/ssl/cert.pem;
    ssl_certificate_key /path/to/ssl/key.pem;

    root /wwwroot/yxshop/yxshop-php/public;
    index index.html;

    # 后台管理 SPA
    location /yxadmin {
        try_files $uri $uri/ /yxadmin/index.html;
    }

    # 静态资源
    location /assets/ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    # 上传文件
    location /uploads/ {
        expires 7d;
    }

    # 反向代理到 Webman
    location / {
        proxy_pass http://127.0.0.1:8777;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    # WebSocket 推送
    location /wss {
        proxy_pass http://127.0.0.1:3131;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_set_header Host $host;
    }
}

# HTTP 强制跳转 HTTPS
server {
    listen 80;
    server_name your-domain.com;
    return 301 https://$server_name$request_uri;
}
```

---

## 四、启动服务

### 启动 Webman
```bash
cd /wwwroot/yxshop/yxshop-php

# 生产环境启动
php start.php start -d

# 或使用 systemd 管理进程
```

### 启动消息队列消费者
```bash
php webman redis-queue:consumer -d
```

### 启动定时任务（秒杀/拼团调度）
```bash
# 这些进程在 start.php 中已配置，随 Webman 一起启动
```

### systemd 服务配置（推荐）
创建 `/etc/systemd/system/yxshop.service`:
```ini
[Unit]
Description=YXShop Webman Service
After=network.target mysql.service redis.service

[Service]
Type=forking
User=www
Group=www
WorkingDirectory=/wwwroot/yxshop/yxshop-php
ExecStart=/usr/bin/php start.php start -d
ExecStop=/usr/bin/php start.php stop
Restart=on-failure
RestartSec=5

[Install]
WantedBy=multi-user.target
```

```bash
systemctl daemon-reload
systemctl enable yxshop
systemctl start yxshop
```

---

## 五、上线前验证清单

### 功能验证
- [ ] 后台管理可以正常登录
- [ ] 后台管理各页面可以正常加载
- [ ] 小程序可以正常登录（微信一键登录）
- [ ] 小程序首页可以正常加载商品
- [ ] 小程序可以加入购物车
- [ ] 小程序可以创建订单
- [ ] 支付流程正常（如已配置支付）
- [ ] 支付回调正常处理
- [ ] 订单状态流转正常

### 安全验证
- [ ] `.env` 文件不可被外部访问（Nginx 配置了 deny）
- [ ] `APP_DEBUG=false`
- [ ] `APP_MODE=production`
- [ ] HTTPS 证书有效
- [ ] CORS 白名单只包含你的域名
- [ ] Redis 已设置密码
- [ ] 数据库用户权限最小化（不建议使用 root）
- [ ] `CURL_SSL_VERIFY=true`

### 性能验证
- [ ] 健康检查 `https://your-domain.com/health` 返回正常
- [ ] 首页加载时间 < 2s
- [ ] API 响应时间 < 500ms
- [ ] 无 PHP error 日志

---

## 六、禁止外部访问的敏感路径

在 Nginx 中添加：
```nginx
# 禁止访问 .env 文件
location ~ /\.env {
    deny all;
    return 404;
}

# 禁止访问 runtime 目录
location ~ ^/runtime {
    deny all;
    return 404;
}

# 禁止访问 database 目录
location ~ ^/database {
    deny all;
    return 404;
}

# 禁止访问 .git 目录
location ~ /\.git {
    deny all;
    return 404;
}
```

---

## 七、日常运维

### 日志查看
```bash
# Webman 日志
tail -f /wwwroot/yxshop/yxshop-php/runtime/logs/webman-*.log

# Redis 队列日志
tail -f /wwwroot/yxshop/yxshop-php/runtime/logs/redis-queue/queue-*.log
```

### 重启服务
```bash
# 修改代码后平滑重启（不断开连接）
php start.php reload

# 完全重启
php start.php restart -d
```

### 数据库备份
```bash
# 每日备份脚本
mysqldump -u root -p yxshop_prod | gzip > /backup/yxshop_$(date +%Y%m%d).sql.gz

# 保留最近30天
find /backup -name "yxshop_*.sql.gz" -mtime +30 -delete
```
