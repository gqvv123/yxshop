<?php

namespace app\model;

/**
 * @deprecated 类名 Service 与系统 Service 层重名严重，请改用 AfterSales。
 *             保留此别名仅为兼容历史代码（少数 Controller 直接 use app\model\Service）。
 *             新代码请直接 use app\model\AfterSales。
 *
 * 表已由 yxshop_services 重命名为 yxshop_after_sales（migration_03_missing_tables.sql）。
 */
class Service extends AfterSales
{
    protected $table = 'yxshop_after_sales';
}
