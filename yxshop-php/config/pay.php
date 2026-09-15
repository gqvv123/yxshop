<?php

return [
    'alipay' => [
        'app_id' => env('alipay.app_id'),
        'private_key' => env('alipay.private_key'),
        'public_key' => env('alipay.public_key'),
        'notify_url' => env('alipay.notify_url'),
        'return_url' => env('alipay.return_url'),
    ],
    'wechat' => [
        'app_id' => env('wechat.app_id'),
        'mch_id' => env('wechat.mch_id'),
        'key' => env('wechat.key'),
        'cert_path' => env('wechat.cert_path'),
        'key_path' => env('wechat.key_path'),
    ],
    'pay_type' => env('pay.pay_type', 'alipay'),
]; 