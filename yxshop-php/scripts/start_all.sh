#!/bin/bash

# YXShop 项目启动脚本
# 用于启动所有项目服务

echo "🚀 启动YXShop所有项目服务..."

# 检查PHP版本
php_version=$(php -v | head -n 1 | cut -d " " -f 2 | cut -d "." -f 1,2)
if [ "$(echo "$php_version < 8.0" | bc)" -eq 1 ]; then
    echo "❌ PHP版本需要8.0或更高，当前版本: $php_version"
    exit 1
fi

# 检查Composer
if ! command -v composer &> /dev/null; then
    echo "❌ 请先安装Composer"
    exit 1
fi

# 检查Redis
if ! command -v redis-server &> /dev/null; then
    echo "❌ 请先安装Redis"
    exit 1
fi

# 启动Redis
echo "📦 启动Redis服务..."
redis-server --daemonize yes

# 进入项目目录
cd "$(dirname "$0")/.."

# 安装依赖
echo "📦 安装yxshop-php依赖..."
cd yxshop-php
composer install --no-dev --optimize-autoloader

echo "📦 安装yxshop-admin依赖..."
cd ../yxshop-admin
composer install --no-dev --optimize-autoloader

echo "📦 安装yxshop-h5依赖..."
cd ../yxshop-h5
composer install --no-dev --optimize-autoloader

echo "📦 安装yxshop-shared依赖..."
cd ../yxshop-shared
composer install --no-dev --optimize-autoloader

# 创建日志目录
mkdir -p yxshop-php/runtime/logs
mkdir -p yxshop-admin/runtime/logs
mkdir -p yxshop-h5/runtime/logs

# 设置权限
chmod -R 755 yxshop-php/runtime
chmod -R 755 yxshop-admin/runtime
chmod -R 755 yxshop-h5/runtime

# 启动服务
echo "🚀 启动所有服务..."

# 启动yxshop-php (API服务)
echo "📡 启动yxshop-php API服务 (端口: 8787)..."
cd ../yxshop-php
php start.php start -d

# 启动yxshop-admin (后台管理)
echo "🖥️  启动yxshop-admin后台管理 (端口: 8788)..."
cd ../yxshop-admin
php start.php start -d

# 启动yxshop-h5 (H5端)
echo "📱 启动yxshop-h5移动端 (端口: 8789)..."
cd ../yxshop-h5
php start.php start -d

# 等待服务启动
sleep 3

# 检查服务状态
echo "🔍 检查服务状态..."

# 检查yxshop-php
if curl -s http://127.0.0.1:8787 > /dev/null; then
    echo "✅ yxshop-php API服务运行正常 (http://127.0.0.1:8787)"
else
    echo "❌ yxshop-php API服务启动失败"
fi

# 检查yxshop-admin
if curl -s http://127.0.0.1:8788 > /dev/null; then
    echo "✅ yxshop-admin后台管理运行正常 (http://127.0.0.1:8788)"
else
    echo "❌ yxshop-admin后台管理启动失败"
fi

# 检查yxshop-h5
if curl -s http://127.0.0.1:8789 > /dev/null; then
    echo "✅ yxshop-h5移动端运行正常 (http://127.0.0.1:8789)"
else
    echo "❌ yxshop-h5移动端启动失败"
fi

echo ""
echo "🎉 所有服务启动完成！"
echo ""
echo "📋 服务地址:"
echo "  API服务:     http://127.0.0.1:8787"
echo "  后台管理:    http://127.0.0.1:8788"
echo "  H5移动端:    http://127.0.0.1:8789"
echo ""
echo "📋 管理命令:"
echo "  停止所有服务: ./scripts/stop_all.sh"
echo "  重启所有服务: ./scripts/restart_all.sh"
echo "  查看服务状态: ./scripts/status_all.sh"
echo ""
echo "📋 开发命令:"
echo "  查看日志:     tail -f yxshop-*/runtime/logs/*.log"
echo "  进入项目:     cd yxshop-*"
echo "  停止服务:     php start.php stop"
echo "  重启服务:     php start.php restart"
