<?php

namespace App\Enums\Settings;

class GlobalConfig
{
    /**
     * 国家列表
     */
    const COUNTRIES = [
        ['code' => 'CN', 'name' => '中国'],
        ['code' => 'US', 'name' => '美国'],
        ['code' => 'GB', 'name' => '英国'],
        ['code' => 'JP', 'name' => '日本'],
        ['code' => 'KR', 'name' => '韩国'],
        ['code' => 'DE', 'name' => '德国'],
        ['code' => 'FR', 'name' => '法国'],
        ['code' => 'IT', 'name' => '意大利'],
        ['code' => 'ES', 'name' => '西班牙'],
        ['code' => 'AU', 'name' => '澳大利亚'],
        ['code' => 'CA', 'name' => '加拿大'],
        ['code' => 'RU', 'name' => '俄罗斯'],
        ['code' => 'IN', 'name' => '印度'],
        ['code' => 'BR', 'name' => '巴西'],
        ['code' => 'MX', 'name' => '墨西哥'],
        ['code' => 'SG', 'name' => '新加坡'],
        ['code' => 'MY', 'name' => '马来西亚'],
        ['code' => 'TH', 'name' => '泰国'],
        ['code' => 'VN', 'name' => '越南'],
        ['code' => 'ID', 'name' => '印度尼西亚']
    ];

    /**
     * 订单变量
     */
    const ORDER_VARIABLE = [
        '{{order_id}}' => '订单ID',
        '{{order_no}}' => '订单编号',
        '{{order_amount}}' => '订单金额',
        '{{order_status}}' => '订单状态',
        '{{customer_name}}' => '客户姓名',
        '{{customer_email}}' => '客户邮箱',
        '{{customer_phone}}' => '客户电话',
        '{{order_date}}' => '下单日期',
        '{{payment_method}}' => '支付方式',
        '{{shipping_address}}' => '配送地址'
    ];

    /**
     * 商品变量
     */
    const ITEM_VARIABLE = [
        '{{item_name}}' => '商品名称',
        '{{item_price}}' => '商品价格',
        '{{item_sku}}' => '商品SKU',
        '{{item_category}}' => '商品分类',
        '{{item_brand}}' => '商品品牌',
        '{{item_description}}' => '商品描述',
        '{{item_image}}' => '商品图片',
        '{{item_url}}' => '商品链接'
    ];

    /**
     * WP订单相关
     */
    const WP_ORDER = [
        '{{wp_order_id}}' => 'WP订单ID',
        '{{wp_order_no}}' => 'WP订单编号',
        '{{wp_customer_name}}' => 'WP客户姓名',
        '{{wp_customer_email}}' => 'WP客户邮箱',
        '{{wp_order_total}}' => 'WP订单总额',
        '{{wp_order_status}}' => 'WP订单状态',
        '{{wp_order_date}}' => 'WP下单日期'
    ];

    /**
     * 电话区号
     */
    const TELEPHONE_CODES = [
        ['code' => '+86', 'name' => '中国 (+86)'],
        ['code' => '+1', 'name' => '美国 (+1)'],
        ['code' => '+44', 'name' => '英国 (+44)'],
        ['code' => '+81', 'name' => '日本 (+81)'],
        ['code' => '+82', 'name' => '韩国 (+82)'],
        ['code' => '+49', 'name' => '德国 (+49)'],
        ['code' => '+33', 'name' => '法国 (+33)'],
        ['code' => '+39', 'name' => '意大利 (+39)'],
        ['code' => '+34', 'name' => '西班牙 (+34)'],
        ['code' => '+61', 'name' => '澳大利亚 (+61)'],
        ['code' => '+1', 'name' => '加拿大 (+1)'],
        ['code' => '+7', 'name' => '俄罗斯 (+7)'],
        ['code' => '+91', 'name' => '印度 (+91)'],
        ['code' => '+55', 'name' => '巴西 (+55)'],
        ['code' => '+52', 'name' => '墨西哥 (+52)'],
        ['code' => '+65', 'name' => '新加坡 (+65)'],
        ['code' => '+60', 'name' => '马来西亚 (+60)'],
        ['code' => '+66', 'name' => '泰国 (+66)'],
        ['code' => '+84', 'name' => '越南 (+84)'],
        ['code' => '+62', 'name' => '印度尼西亚 (+62)']
    ];
}