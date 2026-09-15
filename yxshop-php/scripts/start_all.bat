@echo off
chcp 65001 >nul
echo 🚀 启动YXShop所有项目服务...

REM 检查PHP版本
php -v >nul 2>&1
if errorlevel 1 (
    echo ❌ 请先安装PHP
    pause
    exit /b 1
)

REM 检查Composer
composer --version >nul 2>&1
if errorlevel 1 (
    echo ❌ 请先安装Composer
    pause
    exit /b 1
)

REM 进入项目目录
cd /d "%~dp0.."

REM 安装依赖
echo 📦 安装yxshop-php依赖...
cd yxshop-php
composer install --no-dev --optimize-autoloader

echo 📦 安装yxshop-admin依赖...
cd ..\yxshop-admin
composer install --no-dev --optimize-autoloader

echo 📦 安装yxshop-h5依赖...
cd ..\yxshop-h5
composer install --no-dev --optimize-autoloader

echo 📦 安装yxshop-shared依赖...
cd ..\yxshop-shared
composer install --no-dev --optimize-autoloader

REM 创建日志目录
if not exist "yxshop-php\runtime\logs" mkdir "yxshop-php\runtime\logs"
if not exist "yxshop-admin\runtime\logs" mkdir "yxshop-admin\runtime\logs"
if not exist "yxshop-h5\runtime\logs" mkdir "yxshop-h5\runtime\logs"

REM 启动服务
echo 🚀 启动所有服务...

REM 启动yxshop-php (API服务)
echo 📡 启动yxshop-php API服务 (端口: 8787)...
cd ..\yxshop-php
start "yxshop-php" php start.php start

REM 启动yxshop-admin (后台管理)
echo 🖥️  启动yxshop-admin后台管理 (端口: 8788)...
cd ..\yxshop-admin
start "yxshop-admin" php start.php start

REM 启动yxshop-h5 (H5端)
echo 📱 启动yxshop-h5移动端 (端口: 8789)...
cd ..\yxshop-h5
start "yxshop-h5" php start.php start

REM 等待服务启动
timeout /t 3 /nobreak >nul

echo.
echo 🎉 所有服务启动完成！
echo.
echo 📋 服务地址:
echo   API服务:     http://127.0.0.1:8787
echo   后台管理:    http://127.0.0.1:8788
echo   H5移动端:    http://127.0.0.1:8789
echo.
echo 📋 管理命令:
echo   停止所有服务: scripts\stop_all.bat
echo   重启所有服务: scripts\restart_all.bat
echo   查看服务状态: scripts\status_all.bat
echo.
echo 📋 开发命令:
echo   查看日志:     type yxshop-*\runtime\logs\*.log
echo   进入项目:     cd yxshop-*
echo   停止服务:     php start.php stop
echo   重启服务:     php start.php restart
echo.
pause
