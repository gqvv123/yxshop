<?php

namespace app\bootstrap;

use Webman\Bootstrap;
use Workerman\Worker;
use app\model\Order;
use app\model\OrderItem;
use app\model\Item;
use app\model\User;
use app\model\AfterSales;
use app\model\Promotion;
use app\model\Setting;
use app\model\Admin;
use app\observer\OrderObserver;
use app\observer\OrderItemObserver;
use app\observer\ItemObserver;
use app\observer\UserObserver;
use app\observer\AfterSalesObserver;
use app\observer\PromotionObserver;
use app\observer\SettingObserver;
use app\observer\AdminObserver;

class ObserverRegistrar implements Bootstrap
{
    public static function start(?Worker $worker = null): void
    {
        Order::observe(OrderObserver::class);
        OrderItem::observe(OrderItemObserver::class);
        Item::observe(ItemObserver::class);
        User::observe(UserObserver::class);
        AfterSales::observe(AfterSalesObserver::class);
        Promotion::observe(PromotionObserver::class);
        Setting::observe(SettingObserver::class);
        Admin::observe(AdminObserver::class);

        // 企业版审计追踪 Observer 注册已移至商业版
        // 合并商业版后，enterprise/route/admin.php 或 ModuleRegistry 会自动注册
    }
}