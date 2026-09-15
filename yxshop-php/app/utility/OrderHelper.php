<?php

namespace app\utility;

class OrderHelper
{
    const ORDER_STATUS = [
        -1 => '已取消',
        0 => '待付款',
        10 => '待发货',
        20 => '待收货',
        30 => '已完成',
    ];

    const DELIVERY_STATUS = [
        0 => '未发货',
        10 => '已发货',
        20 => '已收货',
        30 => '已退回',
    ];

    const PAYMENT_STATUS = [
        0 => '未支付',
        1 => '已支付',
        2 => '已退款',
        3 => '部分退款',
    ];

    const PRODUCT_STATUS = [
        0 => '下架',
        1 => '上架',
    ];

    const REWARD_STATUS = [
        0 => '未发放',
        1 => '已发放',
        2 => '已取消',
    ];

    public static function orderStatus(int $status = 0): string
    {
        return self::ORDER_STATUS[$status] ?? '未知状态';
    }

    public static function orderStatusBadge(int $status = 0): array
    {
        $badges = [
            -1 => ['text' => '已取消', 'class' => 'badge badge-danger'],
            0 => ['text' => '待付款', 'class' => 'badge badge-warning'],
            10 => ['text' => '待发货', 'class' => 'badge badge-info'],
            20 => ['text' => '待收货', 'class' => 'badge badge-primary'],
            30 => ['text' => '已完成', 'class' => 'badge badge-success'],
        ];
        return $badges[$status] ?? ['text' => '未知状态', 'class' => 'badge badge-secondary'];
    }

    public static function deliveryStatus(int $status = 0): string
    {
        return self::DELIVERY_STATUS[$status] ?? '未知状态';
    }

    public static function deliveryStatusBadge(int $status = 0): array
    {
        $badges = [
            0 => ['text' => '未发货', 'class' => 'badge badge-secondary'],
            10 => ['text' => '已发货', 'class' => 'badge badge-info'],
            20 => ['text' => '已收货', 'class' => 'badge badge-success'],
            30 => ['text' => '已退回', 'class' => 'badge badge-danger'],
        ];
        return $badges[$status] ?? ['text' => '未知状态', 'class' => 'badge badge-secondary'];
    }

    public static function orderPaymentStatus(int $status = 0): string
    {
        return self::PAYMENT_STATUS[$status] ?? '未知状态';
    }

    public static function productStatus(int $status = 0): string
    {
        return self::PRODUCT_STATUS[$status] ?? '未知';
    }

    public static function rewardStatus(int $status = 0): string
    {
        return self::REWARD_STATUS[$status] ?? '未知';
    }

    public static function showRatings(float $rating): string
    {
        $html = '';
        for ($i = 1; $i <= 5; $i++) {
            if ($rating >= $i) {
                $html .= '<i class="fas fa-star text-warning"></i>';
            } elseif ($rating >= $i - 0.5) {
                $html .= '<i class="fas fa-star-half-alt text-warning"></i>';
            } else {
                $html .= '<i class="far fa-star text-muted"></i>';
            }
        }
        return $html;
    }

    public static function responseStatus(int $status = 0): string
    {
        return $status === 1 ? '成功' : '失败';
    }

    public static function updateStatus(int $status): string
    {
        return $status === 1 ? '启用' : '禁用';
    }

    public static function markStatusUpdate(int $status): string
    {
        return $status === 1 ? '<span class="badge badge-success">启用</span>' : '<span class="badge badge-danger">禁用</span>';
    }

    public static function validateModelStatus(int $status, array $validStatuses = [0, 1]): bool
    {
        return in_array($status, $validStatuses, true);
    }

    public static function negativeValue(mixed $value): string
    {
        if ($value < 0) {
            return '<span class="text-danger">' . $value . '</span>';
        }
        return (string)$value;
    }
}
