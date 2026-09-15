<?php

namespace app\model;

/**
 * 签到模型
 */
class Sign extends BaseModel
{
    protected $table = 'yxshop_signs';

    protected $fillable = [
        'user_id',
        'sign_date',
        'sign_points',
        'sign_continuous',
        'sign_total',
        'sign_reward',
        'sign_status',
        'app_id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'sign_date' => 'date',
        'sign_points' => 'integer',
        'sign_continuous' => 'integer',
        'sign_total' => 'integer',
        'sign_reward' => 'array',
        'sign_status' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer',
    ];

    // 签到状态常量
    const STATUS_NORMAL = 1;       // 正常签到
    const STATUS_REWARD = 2;       // 奖励签到
    const STATUS_BONUS = 3;        // 额外奖励

    /**
     * 获取签到状态文本
     */
    public function getSignStatusTextAttribute()
    {
        $statuses = [
            self::STATUS_NORMAL => '正常签到',
            self::STATUS_REWARD => '奖励签到',
            self::STATUS_BONUS => '额外奖励',
        ];

        return $statuses[$this->sign_status] ?? '未知';
    }

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * 关联应用
     */
    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'id');
    }

    /**
     * 用户今日是否已签到
     */
    public static function isSignedToday($userId, $appId = 0)
    {
        $query = static::where('user_id', $userId)
            ->where('sign_date', date('Y-m-d'));

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->exists();
    }

    /**
     * 获取用户连续签到天数
     */
    public static function getContinuousDays($userId, $appId = 0)
    {
        $query = static::where('user_id', $userId)
            ->orderBy('sign_date', 'desc');

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        $signs = $query->get();
        $continuous = 0;
        $lastDate = null;

        foreach ($signs as $sign) {
            if ($lastDate === null) {
                $lastDate = $sign->sign_date;
                $continuous = 1;
            } else {
                $diff = strtotime($lastDate) - strtotime($sign->sign_date);
                if ($diff == 86400) { // 相差1天
                    $continuous++;
                    $lastDate = $sign->sign_date;
                } else {
                    break;
                }
            }
        }

        return $continuous;
    }
}
