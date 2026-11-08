<?php

namespace app\model;

/**
 * 商品属性定义模型
 */
class ItemAttribute extends BaseModel
{
    protected $table = 'yxshop_item_attributes';

    protected $fillable = [
        'name', 'type_id', 'values', 'sort', 'app_id'
    ];

    protected $casts = [
        'type_id' => 'integer',
        'sort' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer'
    ];

    // 属性类型常量
    const TYPE_TEXT = 1;         // 文本
    const TYPE_SELECT = 2;       // 选择
    const TYPE_MULTI_SELECT = 3; // 多选
    const TYPE_NUMBER = 4;       // 数字
    const TYPE_DATE = 5;         // 日期

    /**
     * 应用关联
     */
    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'id');
    }

    /**
     * 商品属性值
     */
    public function itemAttrs()
    {
        return $this->hasMany(ItemAttr::class, 'attr_id', 'id');
    }

    /**
     * 获取类型文本
     */
    public function getTypeTextAttribute()
    {
        $typeMap = [
            self::TYPE_TEXT => '文本',
            self::TYPE_SELECT => '选择',
            self::TYPE_MULTI_SELECT => '多选',
            self::TYPE_NUMBER => '数字',
            self::TYPE_DATE => '日期',
        ];
        return $typeMap[$this->type_id] ?? '未知';
    }
}
