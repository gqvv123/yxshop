<?php

namespace app\model;

/**
 * @deprecated 请使用 AdminRole 模型代替，此类将在后续版本移除
 */
class Role extends BaseModel
{
    protected $table = 'yxshop_admin_roles';

    protected $fillable = [
        'role_name', 'role_desc', 'app_id', 'deleted_at'
    ];

    protected $casts = [
        'deleted_at' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer'
    ];

    public function admins()
    {
        return $this->hasMany(Admin::class, 'role_id', 'id');
    }

}
