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

/**
 * 日志配置
 *
 * ⚠️ 历史事故：2026-11-06 当天日志曾膨胀到 331MB / 44 万行。
 * 根因不是轮转配置，而是「日志清理任务失败 → Logger::error → 入队 → 消费失败 → 再 Logger::error」
 * 的自激循环（已修复）。但本文件的两个设置是那次能撑到 331MB 的前提，一并加固：
 *
 *   1. 级别从 DEBUG 提到 WARNING —— webman/log 会在 INFO 级别记录**每一个请求**
 *      （`127.0.0.1 GET /api/...`），正常业务量下这就是日志体积的主要来源。
 *      访问日志另有 ApiAccessLogMiddleware 落库到 yxshop_system_logs，不会丢。
 *   2. RotatingFileHandler 只按「天」轮转、**没有单文件大小上限**。
 *      保留 7 天是上限保护，单日仍需靠控制写入量兜底。
 *
 * 需要排查详细请求链路时，临时把 WARNING 改回 DEBUG，排查完改回来。
 */

return [
    'default' => [
        'handlers' => [
            [
                'class' => Monolog\Handler\RotatingFileHandler::class,
                'constructor' => [
                    runtime_path() . '/logs/webman.log',
                    7, // 保留 7 天
                    Monolog\Logger::WARNING, // 只记 warning 及以上，避免每个请求都落盘
                ],
                'formatter' => [
                    'class' => Monolog\Formatter\LineFormatter::class,
                    'constructor' => [null, 'Y-m-d H:i:s', true],
                ],
            ]
        ],
    ],
];
