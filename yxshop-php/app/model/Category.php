<?php

namespace app\model;

/**
 * 商品分类模型
 */
class Category extends BaseModel
{
    protected $table = 'yxshop_categories';

    protected $fillable = [
        'name', 'parent_id', 'level', 'sort', 'is_visible', 'image', 'icon',
        'seo_title', 'seo_keywords', 'seo_description', 'app_id', 'status'
    ];

    protected $casts = [
        'parent_id' => 'integer',
        'level' => 'integer',
        'sort' => 'integer',
        'is_visible' => 'integer',
        'app_id' => 'integer',
        'status' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer'
    ];

    /**
     * 父分类
     */
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id', 'id');
    }

    /**
     * 子分类
     */
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id', 'id');
    }

    /**
     * 所有子分类（递归）
     */
    public function allChildren()
    {
        return $this->children()->with('allChildren');
    }

    /**
     * 分类下的商品
     */
    public function items()
    {
        return $this->hasMany(Item::class, 'category_id', 'id');
    }

    /**
     * 获取所有父级分类ID
     */
    public function getAllParentIds()
    {
        $parentIds = [];
        $parent = $this->parent;
        
        while ($parent) {
            $parentIds[] = $parent->id;
            $parent = $parent->parent;
        }
        
        return array_reverse($parentIds);
    }

    /**
     * 获取所有子级分类ID
     */
    public function getAllChildIds()
    {
        $childIds = [$this->id];
        if ($this->relationLoaded('children')) {
            foreach ($this->getRelation('children') as $child) {
                $childIds = array_merge($childIds, $child->getAllChildIds());
            }
        } else {
            foreach ($this->children as $child) {
                $childIds = array_merge($childIds, $child->getAllChildIds());
            }
        }
        return $childIds;
    }

    /**
     * 检查是否有子分类
     */
    public function hasChildren()
    {
        return $this->children()->count() > 0;
    }

    /**
     * 检查是否有商品
     */
    public function hasItems()
    {
        return $this->items()->count() > 0;
    }

    /**
     * 获取分类路径
     */
    public function getPath()
    {
        $path = [$this->name];
        $parent = $this->parent;
        
        while ($parent) {
            array_unshift($path, $parent->name);
            $parent = $parent->parent;
        }
        
        return implode(' > ', $path);
    }
}
