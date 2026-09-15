<?php

namespace app\model;

class AdminRole extends BaseModel
{
    protected $table = 'yxshop_admin_roles';

    protected $fillable = [
        'role_name', 'role_desc', 'deleted_at', 'app_id'
    ];

    protected $casts = [
        'deleted_at' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer'
    ];

    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'id');
    }

    public function admins()
    {
        return $this->hasMany(Admin::class, 'role_id', 'id');
    }

    public function auths()
    {
        return $this->belongsToMany(
            AdminMenu::class,
            'yxshop_admin_role_auths',
            'role_id',
            'auth_id'
        )->withTimestamps();
    }

    public function assignAuths(array $authIds)
    {
        $this->auths()->sync($authIds);
    }

    public function getAuthIds()
    {
        return $this->auths()->pluck('yxshop_admin_menus.id')->toArray();
    }

    public function hasAuth($authId)
    {
        return $this->auths()->where('yxshop_admin_role_auths.auth_id', $authId)->exists();
    }

    public function scopeNotDeleted($query)
    {
        return $query->where('deleted_at', 0);
    }
}
