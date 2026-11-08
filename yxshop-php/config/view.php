<?php
/**
 * This file is part of webman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

use support\view\Raw;

// 管理后台已改为 Vue3 SPA 架构，后端纯 API 返回 JSON
// 不再需要 Blade/Twig 模板引擎，使用 Raw（纯字符串/无渲染）
return [
    'handler' => Raw::class
];
