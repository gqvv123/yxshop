#!/bin/bash

# YXShop 项目停止脚本
# 用于停止所有项目服务

echo "🛑 停止YXShop所有项目服务..."

# 进入项目目录
cd "$(dirname "$0")/.."

# 停止yxshop-php
echo "📡 停止yxshop-php API服务..."
cd yxshop-php
php start.php stop

# 停止yxshop-admin
echo "🖥️  停止yxshop-admin后台管理..."
cd ../yxshop-admin
php start.php stop

# 停止yxshop-h5
echo "📱 停止yxshop-h5移动端..."
cd ../yxshop-h5
php start.php stop

# 停止Redis
echo "📦 停止Redis服务..."
redis-cli shutdown

echo "✅ 所有服务已停止"
