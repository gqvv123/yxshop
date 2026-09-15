<?php

namespace app\model;

/**
 * 通知场景模型
 */
class NotificationScene extends BaseModel
{
    protected $table = 'yxshop_notification_scenes';

    protected $fillable = [
        'scene_name',
        'scene_code',
        'scene_desc',
        'is_active',
        'sort',
        'app_id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'is_active' => 'integer',
        'sort' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer',
    ];

    // 场景代码常量
    const CODE_USER_REGISTER = 'user_register';         // 用户注册
    const CODE_USER_LOGIN = 'user_login';               // 用户登录
    const CODE_ORDER_CREATE = 'order_create';           // 订单创建
    const CODE_ORDER_PAY = 'order_pay';                 // 订单支付
    const CODE_ORDER_SHIP = 'order_ship';               // 订单发货
    const CODE_ORDER_RECEIVE = 'order_receive';         // 订单收货
    const CODE_ORDER_CANCEL = 'order_cancel';           // 订单取消
    const CODE_PASSWORD_RESET = 'password_reset';       // 密码重置
    const CODE_VERIFY_CODE = 'verify_code';             // 验证码
    const CODE_COUPON_EXPIRE = 'coupon_expire';         // 优惠券过期

    /**
     * 获取场景代码文本
     */
    public function getSceneCodeTextAttribute()
    {
        $codes = [
            self::CODE_USER_REGISTER => '用户注册',
            self::CODE_USER_LOGIN => '用户登录',
            self::CODE_ORDER_CREATE => '订单创建',
            self::CODE_ORDER_PAY => '订单支付',
            self::CODE_ORDER_SHIP => '订单发货',
            self::CODE_ORDER_RECEIVE => '订单收货',
            self::CODE_ORDER_CANCEL => '订单取消',
            self::CODE_PASSWORD_RESET => '密码重置',
            self::CODE_VERIFY_CODE => '验证码',
            self::CODE_COUPON_EXPIRE => '优惠券过期',
        ];

        return $codes[$this->scene_code] ?? $this->scene_code;
    }

    /**
     * 关联应用
     */
    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'id');
    }

    /**
     * 关联场景模板
     */
    public function sceneTemplates()
    {
        return $this->hasMany(NotificationSceneTemplate::class, 'scene_id', 'id');
    }

    /**
     * 关联发送记录
     */
    public function sends()
    {
        return $this->hasMany(NotificationSend::class, 'scene_id', 'id');
    }
}