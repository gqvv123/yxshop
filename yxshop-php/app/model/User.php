<?php

namespace app\model;

/**
 * 用户模型
 */
class User extends BaseModel
{
    // 用户来源常量（source 字段为 tinyint）
    const SOURCE_MINIPROGRAM   = 10; // 微信小程序
    const SOURCE_H5            = 20; // H5
    const SOURCE_APP           = 30; // APP
    const SOURCE_WECHAT_QRCODE = 40; // 微信公众号扫码

    protected $table = 'yxshop_users';

    protected $fillable = [
        'phone', 'openid', 'unionid', 'email', 'nickname', 'avatar_url', 'gender', 'country', 'province', 'city',
        'default_address_id', 'money', 'total_spent', 'commission', 'deleted_at', 'app_id', 'password',
        'pid', 'level', 'sign', 'integral', 'path', 'agio', 'freeze_money', 'level_id',
        'is_dealer', 'first_num', 'second_num', 'third_num', 'referrer_id', 'referrer_spent',
        'freeze_integral', 'last_login_at', 'last_login_ip', 'login_count', 'birthday', 'source',
        'risk_level', 'risk_remark', 'frozen_reason', 'login_security_status',
        'dealer_level_id', 'dealer_status', 'total_commission', 'pending_commission',
        'withdrawn_commission', 'frozen_commission', 'dealer_apply_time', 'dealer_active_time',
    ];

    protected $hidden = ['password', 'openid'];

    protected $casts = [
        'money' => 'decimal:2',
        'total_spent' => 'decimal:2',
        'commission' => 'decimal:2',
        'integral' => 'decimal:2',
        'agio' => 'decimal:2',
        'freeze_money' => 'decimal:2',
        'referrer_spent' => 'decimal:2',
        'freeze_integral' => 'decimal:2',
        'deleted_at' => 'integer',
        'is_dealer' => 'integer',
        'gender' => 'integer',
        'level_id' => 'integer',
        'login_count' => 'integer',
        'first_num' => 'integer',
        'second_num' => 'integer',
        'third_num' => 'integer',
        'referrer_id' => 'integer',
        'sign' => 'integer',
        'last_login_at' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer',
        'risk_level' => 'integer',
        'login_security_status' => 'integer',
        'dealer_level_id' => 'integer',
        'dealer_status' => 'integer',
        'total_commission' => 'decimal:2',
        'pending_commission' => 'decimal:2',
        'withdrawn_commission' => 'decimal:2',
        'frozen_commission' => 'decimal:2',
        'dealer_apply_time' => 'integer',
        'dealer_active_time' => 'integer',
    ];

    /**
     * 用户等级关联
     */
    public function level()
    {
        return $this->belongsTo(UserLevel::class, 'level_id', 'id');
    }

    /**
     * 用户地址
     */
    public function addresses()
    {
        return $this->hasMany(UserAddress::class, 'user_id', 'id');
    }

    /**
     * 默认地址
     */
    public function defaultAddress()
    {
        return $this->hasOne(UserAddress::class, 'user_id', 'id')->where('is_default', 1);
    }

    /**
     * 用户订单
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'user_id', 'id');
    }

    /**
     * 用户优惠券
     */
    public function coupons()
    {
        return $this->hasMany(UserCoupon::class, 'user_id', 'id');
    }

    /**
     * 用户收藏
     */
    public function favorites()
    {
        return $this->hasMany(ItemFavorite::class, 'user_id', 'id');
    }

    /**
     * 用户浏览记录
     */
    public function views()
    {
        return $this->hasMany(ItemView::class, 'user_id', 'id');
    }

    /**
     * 用户搜索记录
     */
    public function searches()
    {
        return $this->hasMany(ItemSearch::class, 'user_id', 'id');
    }

    /**
     * 用户积分记录
     */
    public function integrals()
    {
        return $this->hasMany(Integral::class, 'user_id', 'id');
    }

    /**
     * 推荐人
     */
    public function referee()
    {
        return $this->belongsTo(User::class, 'referrer_id', 'id');
    }

    /**
     * 被推荐人
     */
    public function referrals()
    {
        return $this->hasMany(User::class, 'referrer_id', 'id');
    }

    /**
     * 用户标签
     */
    public function tags()
    {
        return $this->belongsToMany(UserTag::class, 'yxshop_user_tag_relations', 'user_id', 'tag_id');
    }

    /**
     * 风险行为日志
     */
    public function riskLogs()
    {
        return $this->hasMany(UserRiskLog::class, 'user_id', 'id')
            ->orderBy('created_at', 'desc');
    }

    /**
     * 是否为风险用户
     */
    public function isRisky(): bool
    {
        return $this->risk_level >= 2;
    }

    /**
     * 是否被冻结
     */
    public function isFrozen(): bool
    {
        return !empty($this->frozen_reason);
    }

    /**
     * 获取风险等级文字
     */
    public function getRiskLevelTextAttribute(): string
    {
        return [0 => '正常', 1 => '低', 2 => '中', 3 => '高'][$this->risk_level] ?? '未知';
    }

    /**
     * 获取用户余额
     */
    public function getBalanceAttribute()
    {
        return $this->money - $this->freeze_money;
    }

    /**
     * 获取用户积分余额
     */
    public function getIntegralBalanceAttribute()
    {
        return $this->integral - $this->freeze_integral;
    }

    /**
     * 检查是否为VIP用户
     */
    public function isVip()
    {
        return $this->level_id > 1;
    }

    /**
     * 检查是否为分销商
     */
    public function isDealer()
    {
        return $this->is_dealer == 1;
    }

    /**
     * 获取用户等级名称
     */
    public function getLevelNameAttribute()
    {
        return $this->level ? $this->level->name : '普通用户';
    }

    /**
     * 获取用户折扣
     */
    public function getDiscountAttribute()
    {
        return $this->level ? $this->level->agio : 10.00;
    }

    /**
     * 更新登录信息
     */
    public function updateLoginInfo($ip = '')
    {
        $this->last_login_at = time();
        $this->last_login_ip = $ip;
        $this->login_count = $this->login_count + 1;
        return $this->save();
    }

    /**
     * 增加积分
     *
     * @deprecated 请使用 IntegralService::addIntegral()。
     *             保留薄封装仅为向后兼容旧调用点。
     */
    public function addIntegral($amount, $note = '', $type = 1, $orderId = 0)
    {
        return \support\Container::get('app\service\IntegralService')->addIntegral($this->id, $amount, $note, $type, $orderId);
    }

    /**
     * 减少积分
     *
     * @deprecated 请使用 IntegralService::reduceIntegral()。
     */
    public function reduceIntegral($amount, $note = '', $type = 2, $orderId = 0)
    {
        return \support\Container::get('app\service\IntegralService')->reduceIntegral($this->id, $amount, $note, $type, $orderId);
    }

    /**
     * 增加余额
     *
     * @deprecated 请使用 UserMoneyService::addMoney()（原子 + 事务 + 流水）。
     *             保留薄封装仅为向后兼容旧调用点（如 OrderAgent::settle）。
     */
    public function addMoney($amount, $note = '')
    {
        return \support\Container::get('app\service\UserMoneyService')->addMoney($this->id, $amount, $note);
    }

    /**
     * 减少余额
     *
     * @deprecated 请使用 UserMoneyService::reduceMoney()（带余额守卫防透支）。
     */
    public function reduceMoney($amount, $note = '')
    {
        return \support\Container::get('app\service\UserMoneyService')->reduceMoney($this->id, $amount, $note);
    }
}
