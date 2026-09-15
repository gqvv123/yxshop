<?php

namespace app\model;

/**
 * 管理员模型
 */
class Admin extends BaseModel
{
    protected $table = 'yxshop_admins';

    protected $fillable = [
        'username', 'password', 'app_id', 'nickname', 'is_super_admin', 'pid',
        'role_id', 'phone', 'note', 'vip', 'email', 'avatar', 'last_login_at',
        'two_factor_secret', 'two_factor_enabled',
        'login_fail_count', 'locked_until', 'password_changed_at', 'force_password_change',
        'wechat_openid', 'wechat_bound_at', 'wechat_nickname', 'wechat_avatar'
    ];

    protected $hidden = ['password', 'two_factor_secret'];

    protected $casts = [
        'app_id' => 'integer',
        'is_super_admin' => 'integer',
        'pid' => 'integer',
        'role_id' => 'integer',
        'vip' => 'integer',
        'two_factor_enabled' => 'integer',
        'deleted_at' => 'integer',
        'last_login_at' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer',
        'login_fail_count' => 'integer',
        'locked_until' => 'integer',
        'password_changed_at' => 'integer',
        'force_password_change' => 'integer',
        'wechat_bound_at' => 'integer',
    ];

    /**
     * 应用关联
     */
    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'id');
    }

    /**
     * 角色关联
     */
    public function role()
    {
        return $this->belongsTo(AdminRole::class, 'role_id', 'id');
    }

    /**
     * 父管理员
     */
    public function parent()
    {
        return $this->belongsTo(Admin::class, 'pid', 'id');
    }

    /**
     * 子管理员
     */
    public function children()
    {
        return $this->hasMany(Admin::class, 'pid', 'id');
    }

    /**
     * 检查是否为超级管理员
     */
    public function isSuper()
    {
        return $this->is_super_admin == 1;
    }

    /**
     * 检查密码
     */
    public function checkPassword($password)
    {
        if (!password_verify($password, $this->password)) {
            return false;
        }
        if (password_needs_rehash($this->password, PASSWORD_DEFAULT)) {
            $this->password = password_hash($password, PASSWORD_DEFAULT);
            $this->save();
        }
        return true;
    }

    /**
     * 设置密码
     */
    public function setPassword($password)
    {
        $this->password = password_hash($password, PASSWORD_DEFAULT);
        return $this;
    }

    public function isLocked(): bool
    {
        return $this->locked_until && $this->locked_until > time();
    }

    public function isPasswordExpired(int $expireDays): bool
    {
        if ($expireDays <= 0) return false;
        if (!$this->password_changed_at) return true;
        return (time() - $this->password_changed_at) > $expireDays * 86400;
    }

    public function isFirstLogin(): bool
    {
        return !$this->password_changed_at;
    }

    public function passwordHistories()
    {
        return $this->hasMany(AdminPasswordHistory::class, 'admin_id', 'id');
    }
}
