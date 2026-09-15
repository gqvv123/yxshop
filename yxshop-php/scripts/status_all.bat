@echo off
chcp 65001 >nul
echo 🔍 检查YXShop所有项目服务状态...
echo.

REM 检查Webman服务
echo 🚀 Webman服务:

REM 检查yxshop-php
tasklist /FI "IMAGENAME eq php.exe" /FI "WINDOWTITLE eq yxshop-php*" >nul 2>&1
if errorlevel 1 (
    echo   ❌ yxshop-php API服务未运行
) else (
    echo   ✅ yxshop-php API服务运行中
    curl -s http://127.0.0.1:8787 >nul 2>&1
    if errorlevel 1 (
        echo     ⚠️  服务不可访问
    ) else (
        echo     🌐 服务可访问: http://127.0.0.1:8787
    )
)

REM 检查yxshop-admin
tasklist /FI "IMAGENAME eq php.exe" /FI "WINDOWTITLE eq yxshop-admin*" >nul 2>&1
if errorlevel 1 (
    echo   ❌ yxshop-admin后台管理未运行
) else (
    echo   ✅ yxshop-admin后台管理运行中
    curl -s http://127.0.0.1:8788 >nul 2>&1
    if errorlevel 1 (
        echo     ⚠️  服务不可访问
    ) else (
        echo     🌐 服务可访问: http://127.0.0.1:8788
    )
)

REM 检查yxshop-h5
tasklist /FI "IMAGENAME eq php.exe" /FI "WINDOWTITLE eq yxshop-h5*" >nul 2>&1
if errorlevel 1 (
    echo   ❌ yxshop-h5移动端未运行
) else (
    echo   ✅ yxshop-h5移动端运行中
    curl -s http://127.0.0.1:8789 >nul 2>&1
    if errorlevel 1 (
        echo     ⚠️  服务不可访问
    ) else (
        echo     🌐 服务可访问: http://127.0.0.1:8789
    )
)

echo.

REM 检查端口占用
echo 🔌 端口占用情况:
netstat -an | findstr ":8787" >nul 2>&1
if errorlevel 1 (
    echo   ❌ 端口 8787 未被占用
) else (
    echo   ✅ 端口 8787 被占用
)

netstat -an | findstr ":8788" >nul 2>&1
if errorlevel 1 (
    echo   ❌ 端口 8788 未被占用
) else (
    echo   ✅ 端口 8788 被占用
)

netstat -an | findstr ":8789" >nul 2>&1
if errorlevel 1 (
    echo   ❌ 端口 8789 未被占用
) else (
    echo   ✅ 端口 8789 被占用
)

echo.

REM 检查日志文件
echo 📋 日志文件状态:
if exist "yxshop-php\runtime\logs" (
    dir /b "yxshop-php\runtime\logs\*.log" 2>nul | find /c /v "" >nul
    if errorlevel 1 (
        echo   ✅ yxshop-php\runtime\logs: 0 个日志文件
    ) else (
        for /f %%i in ('dir /b "yxshop-php\runtime\logs\*.log" 2^>nul ^| find /c /v ""') do echo   ✅ yxshop-php\runtime\logs: %%i 个日志文件
    )
) else (
    echo   ❌ yxshop-php\runtime\logs: 目录不存在
)

if exist "yxshop-admin\runtime\logs" (
    dir /b "yxshop-admin\runtime\logs\*.log" 2>nul | find /c /v "" >nul
    if errorlevel 1 (
        echo   ✅ yxshop-admin\runtime\logs: 0 个日志文件
    ) else (
        for /f %%i in ('dir /b "yxshop-admin\runtime\logs\*.log" 2^>nul ^| find /c /v ""') do echo   ✅ yxshop-admin\runtime\logs: %%i 个日志文件
    )
) else (
    echo   ❌ yxshop-admin\runtime\logs: 目录不存在
)

if exist "yxshop-h5\runtime\logs" (
    dir /b "yxshop-h5\runtime\logs\*.log" 2>nul | find /c /v "" >nul
    if errorlevel 1 (
        echo   ✅ yxshop-h5\runtime\logs: 0 个日志文件
    ) else (
        for /f %%i in ('dir /b "yxshop-h5\runtime\logs\*.log" 2^>nul ^| find /c /v ""') do echo   ✅ yxshop-h5\runtime\logs: %%i 个日志文件
    )
) else (
    echo   ❌ yxshop-h5\runtime\logs: 目录不存在
)

echo.
echo 📋 管理命令:
echo   启动所有服务: scripts\start_all.bat
echo   停止所有服务: scripts\stop_all.bat
echo   重启所有服务: scripts\restart_all.bat
echo.
pause
