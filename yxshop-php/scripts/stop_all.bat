@echo off
chcp 65001 >nul
echo 🛑 停止YXShop所有项目服务...

REM 进入项目目录
cd /d "%~dp0.."

REM 停止yxshop-php
echo 📡 停止yxshop-php API服务...
cd yxshop-php
php start.php stop

REM 停止yxshop-admin
echo 🖥️  停止yxshop-admin后台管理...
cd ..\yxshop-admin
php start.php stop

REM 停止yxshop-h5
echo 📱 停止yxshop-h5移动端...
cd ..\yxshop-h5
php start.php stop

echo ✅ 所有服务已停止
pause
