# YXShop 统一Webman架构说明

## 🏗️ 项目架构

### 📦 项目结构
```
yxshop/
├── yxshop-php/          # 后端API (Webman框架)
├── yxshop-admin/        # 后台管理 (Webman框架 + Webman Admin)
├── yxshop-h5/           # H5端 (Webman框架 + 移动端页面)
├── yxshop-miniprogram/  # 小程序端 (微信小程序)
└── yxshop-shared/       # 共享组件 (模型、服务、工具类)
```

### 🎯 技术栈统一
- **后端框架**: Webman (所有后端服务)
- **数据库**: MySQL 8.0
- **缓存**: Redis
- **队列**: Redis Queue
- **前端**: Vue.js + Element Plus (后台管理)
- **移动端**: Vue.js + Vant (H5端)
- **小程序**: 原生微信小程序

## 🚀 快速开始

### 1. 创建项目
```bash
# 运行项目创建脚本
php scripts/create_projects.php
```

### 2. 安装依赖
```bash
# 安装yxshop-php依赖
cd yxshop-php
composer install

# 安装yxshop-admin依赖
cd ../yxshop-admin
composer install

# 安装yxshop-h5依赖
cd ../yxshop-h5
composer install

# 安装yxshop-shared依赖
cd ../yxshop-shared
composer install
```

### 3. 配置环境
```bash
# 复制环境配置文件
cp yxshop-php/.env.example yxshop-php/.env
cp yxshop-admin/.env.example yxshop-admin/.env
cp yxshop-h5/.env.example yxshop-h5/.env

# 编辑配置文件，设置数据库连接等
```

### 4. 启动服务

#### Windows
```bash
# 启动所有服务
scripts\start_all.bat

# 停止所有服务
scripts\stop_all.bat

# 查看服务状态
scripts\status_all.bat
```

#### Linux/Mac
```bash
# 启动所有服务
./scripts/start_all.sh

# 停止所有服务
./scripts/stop_all.sh

# 查看服务状态
./scripts/status_all.sh
```

### 5. 访问服务
- **API服务**: http://127.0.0.1:8787
- **后台管理**: http://127.0.0.1:8788
- **H5移动端**: http://127.0.0.1:8789

## 📋 开发指南

### 1. 项目间通信

#### API调用
```php
// 在yxshop-admin中调用yxshop-php的API
class UserController
{
    public function index()
    {
        $apiClient = new ApiClient();
        $users = $apiClient->call('user', 'getList');
        return view('user.index', compact('users'));
    }
}
```

#### 共享服务调用
```php
// 在yxshop-admin中调用共享服务
class UserController
{
    public function index()
    {
        $userService = new \YxshopShared\Service\UserService();
        $users = $userService->getList();
        return view('user.index', compact('users'));
    }
}
```

### 2. 数据库配置

所有项目共享同一个数据库配置：

```php
// config/database.php
return [
    'default' => 'mysql',
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', 3306),
            'database' => env('DB_DATABASE', 'yxshop'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
        ]
    ]
];
```

### 3. 缓存共享

使用Redis作为共享缓存：

```php
// 在任意项目中
use support\Redis;

// 设置缓存
Redis::set('key', 'value', 3600);

// 获取缓存
$value = Redis::get('key');
```

### 4. 版本控制

#### 功能权限检查
```php
// 检查用户是否有功能权限
$versionService = new VersionService();
if (!$versionService->hasFeature($userId, 'advanced_coupon')) {
    return json(['code' => 403, 'msg' => '功能需要升级到进阶版本']);
}
```

#### 版本升级
```php
// 升级用户版本
$upgradeService = new UpgradeService();
$result = $upgradeService->upgrade($userId, 'professional');
```

## 🔧 部署指南

### 1. 生产环境配置

#### 修改端口配置
```php
// config/server.php
return [
    'listen' => '0.0.0.0:8787',
    'context' => [],
    'processes' => 4,
    'reloadable' => true,
];
```

#### 配置Nginx反向代理
```nginx
# yxshop-php API
server {
    listen 80;
    server_name api.yxshop.com;
    location / {
        proxy_pass http://127.0.0.1:8787;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}

# yxshop-admin 后台管理
server {
    listen 80;
    server_name admin.yxshop.com;
    location / {
        proxy_pass http://127.0.0.1:8788;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}

# yxshop-h5 H5端
server {
    listen 80;
    server_name h5.yxshop.com;
    location / {
        proxy_pass http://127.0.0.1:8789;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
```

### 2. 数据库迁移

```bash
# 运行数据库迁移
php yxshop-php/start.php migrate

# 或者使用命令行工具
php yxshop-php/app/command/MigrateCommand.php
```

### 3. 缓存预热

```bash
# 预热缓存
php yxshop-php/start.php cache:warmup
```

## 📊 监控和维护

### 1. 日志查看

```bash
# 查看API服务日志
tail -f yxshop-php/runtime/logs/*.log

# 查看后台管理日志
tail -f yxshop-admin/runtime/logs/*.log

# 查看H5端日志
tail -f yxshop-h5/runtime/logs/*.log
```

### 2. 性能监控

```bash
# 查看进程状态
ps aux | grep php

# 查看端口占用
netstat -tlnp | grep :878

# 查看内存使用
free -h
```

### 3. 服务管理

```bash
# 重启单个服务
cd yxshop-php
php start.php restart

# 停止单个服务
cd yxshop-php
php start.php stop

# 查看服务状态
cd yxshop-php
php start.php status
```

## 🎯 版本功能

### 开源版本 (Community Edition)
- 基础电商功能
- 用户管理
- 商品管理
- 订单管理
- 库存管理
- 支付管理

### 进阶版本 (Professional Edition)
- 开源版本所有功能
- 高级营销功能
- 数据分析
- 通知系统
- 库存预警

### 高级版本 (Enterprise Edition)
- 进阶版本所有功能
- 分销系统
- 多仓库管理
- 高级分析
- 企业级功能

## 🆘 常见问题

### 1. 服务启动失败
- 检查PHP版本是否>=8.0
- 检查端口是否被占用
- 检查数据库连接是否正常
- 查看错误日志

### 2. 数据库连接失败
- 检查数据库服务是否启动
- 检查配置文件中的数据库信息
- 检查数据库用户权限

### 3. 缓存问题
- 检查Redis服务是否启动
- 检查Redis连接配置
- 清除缓存重新启动

### 4. 权限问题
- 检查文件权限
- 检查目录权限
- 检查用户权限

## 📞 技术支持

- **文档**: [项目文档地址]
- **问题反馈**: [GitHub Issues]
- **技术交流**: [技术交流群]
- **商业支持**: [商业支持邮箱]

## 📄 许可证

本项目采用 [许可证类型] 许可证，详情请查看 [LICENSE](LICENSE) 文件。
