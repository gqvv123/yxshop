<?php

namespace app\model;

class Permission extends BaseModel
{
    protected $table = 'yxshop_permissions';

    protected $usesSnowflake = false;

    public $incrementing = true;

    protected $keyType = 'int';

    const TYPE_MENU = 1;
    const TYPE_BUTTON = 2;
    const TYPE_DATA = 3;

    const STATUS_DISABLED = 0;
    const STATUS_ENABLED = 1;

    protected $fillable = [
        'permission_name', 'permission_code', 'permission_type', 'parent_id',
        'menu_id', 'sort', 'status', 'app_id'
    ];

    protected $casts = [
        'permission_type' => 'integer',
        'parent_id' => 'integer',
        'menu_id' => 'integer',
        'sort' => 'integer',
        'status' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer'
    ];

    public function parent()
    {
        return $this->belongsTo(Permission::class, 'parent_id', 'id');
    }

    public function children()
    {
        return $this->hasMany(Permission::class, 'parent_id', 'id');
    }

    public function menu()
    {
        return $this->belongsTo(AdminMenu::class, 'menu_id', 'id');
    }

    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'id');
    }
}