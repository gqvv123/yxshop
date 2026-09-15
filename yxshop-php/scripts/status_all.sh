#!/bin/bash

# YXShop 项目状态检查脚本
# 用于检查所有项目服务状态

echo "🔍 检查YXShop所有项目服务状态..."
echo ""

# 检查Redis
echo "📦 Redis服务:"
if pgrep -x "redis-server" > /dev/null; then
    echo "  ✅ Redis运行中 (PID: $(pgrep redis-server))"
else
    echo "  ❌ Redis未运行"
fi

echo ""

# 检查Webman服务
echo "🚀 Webman服务:"

# 检查yxshop-php
if pgrep -f "yxshop-php" > /dev/null; then
    echo "  ✅ yxshop-php API服务运行中 (PID: $(pgrep -f yxshop-php))"
    if curl -s http://127.0.0.1:8787 > /dev/null; then
        echo "    🌐 服务可访问: http://127.0.0.1:8787"
    else
        echo "    ⚠️  服务不可访问"
    fi
else
    echo "  ❌ yxshop-php API服务未运行"
fi

# 检查yxshop-admin
if pgrep -f "yxshop-admin" > /dev/null; then
    echo "  ✅ yxshop-admin后台管理运行中 (PID: $(pgrep -f yxshop-admin))"
    if curl -s http://127.0.0.1:8788 > /dev/null; then
        echo "    🌐 服务可访问: http://127.0.0.1:8788"
    else
        echo "    ⚠️  服务不可访问"
    fi
else
    echo "  ❌ yxshop-admin后台管理未运行"
fi

# 检查yxshop-h5
if pgrep -f "yxshop-h5" > /dev/null; then
    echo "  ✅ yxshop-h5移动端运行中 (PID: $(pgrep -f yxshop-h5))"
    if curl -s http://127.0.0.1:8789 > /dev/null; then
        echo "    🌐 服务可访问: http://127.0.0.1:8789"
    else
        echo "    ⚠️  服务不可访问"
    fi
else
    echo "  ❌ yxshop-h5移动端未运行"
fi

echo ""

# 检查端口占用
echo "🔌 端口占用情况:"
ports=(8787 8788 8789 6379)
for port in "${ports[@]}"; do
    if lsof -i :$port > /dev/null 2>&1; then
        process=$(lsof -i :$port | tail -n 1 | awk '{print $1}')
        echo "  ✅ 端口 $port 被 $process 占用"
    else
        echo "  ❌ 端口 $port 未被占用"
    fi
done

echo ""

# 检查日志文件
echo "📋 日志文件状态:"
log_dirs=("yxshop-php/runtime/logs" "yxshop-admin/runtime/logs" "yxshop-h5/runtime/logs")
for log_dir in "${log_dirs[@]}"; do
    if [ -d "$log_dir" ]; then
        log_count=$(find "$log_dir" -name "*.log" | wc -l)
        echo "  ✅ $log_dir: $log_count 个日志文件"
    else
        echo "  ❌ $log_dir: 目录不存在"
    fi
done

echo ""

# 检查数据库连接
echo "🗄️  数据库连接:"
if command -v mysql &> /dev/null; then
    if mysql -e "SELECT 1;" > /dev/null 2>&1; then
        echo "  ✅ MySQL连接正常"
    else
        echo "  ❌ MySQL连接失败"
    fi
else
    echo "  ⚠️  MySQL客户端未安装"
fi

echo ""
echo "📋 管理命令:"
echo "  启动所有服务: ./scripts/start_all.sh"
echo "  停止所有服务: ./scripts/stop_all.sh"
echo "  重启所有服务: ./scripts/restart_all.sh"
