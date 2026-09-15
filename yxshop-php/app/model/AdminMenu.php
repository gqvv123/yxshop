<?php

namespace app\model;

use app\model\BaseModel;

/**
 * 后台管理菜单模型
 */
class AdminMenu extends BaseModel
{
    protected $table = 'yxshop_admin_menus';
    
    protected $fillable = [
        'parent_id',
        'name', 
        'model',
        'url',
        'icon',
        'sort',
        'is_show',
        'permission',
        'method',
        'app_id',
    ];
    
    protected $casts = [];
    
    /**
     * 获取子菜单
     */
    public function children()
    {
        return $this->hasMany(AdminMenu::class, 'parent_id', 'id')
            ->where('is_show', 1)
            ->orderBy('sort', 'asc');
    }
    
    /**
     * 获取父菜单
     */
    public function parent()
    {
        return $this->belongsTo(AdminMenu::class, 'parent_id', 'id');
    }
}
