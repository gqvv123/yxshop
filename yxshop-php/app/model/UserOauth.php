<?php

namespace app\model;

class UserOauth extends BaseModel
{
    protected $table = 'yxshop_user_oauths';

    protected $usesSnowflake = false;

    public $incrementing = true;

    protected $keyType = 'int';

    const OAUTH_WECHAT = 'wechat';
    const OAUTH_WECHAT_QRCODE = 'wx_qrcode';
    const OAUTH_ALIPAY = 'alipay';
    const OAUTH_QQ = 'qq';
    const OAUTH_WEIBO = 'weibo';

    protected $fillable = [
        'user_id', 'oauth_type', 'openid', 'unionid', 'nickname',
        'avatar', 'access_token', 'refresh_token', 'expires_at', 'app_id'
    ];

    protected $casts = [
        'user_id' => 'integer',
        'expires_at' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'id');
    }
}