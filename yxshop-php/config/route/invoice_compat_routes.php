<?php

/**
 * 开源版兼容路由：发票模块。
 *
 * 由 config/route/admin_api.php 在「需鉴权」的路由组内 require，
 * 因此这些接口同样受 License / AdminAuth / ApiAccessLog / OperationLog 中间件保护。
 *
 * 采用通配单路由 + 控制器内白名单分派，避免为十余个逆向出的路径各写一条路由。
 * 表名与列名在控制器内白名单化，path 只用于匹配预设资源。
 */

use app\admin\controller\InvoiceCompatController;
use support\Request;
use Webman\Route;

Route::any('/invoice/{path:.+}', static function (Request $request, $path) {
    return (new InvoiceCompatController())->handle($request, (string) $path);
});
