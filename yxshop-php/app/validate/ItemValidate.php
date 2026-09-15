<?php

namespace app\validate;

use think\Validate;

/**
 * 商品验证器
 */
class ItemValidate extends Validate
{
    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'category_id' => 'require|number|min:0',
        'name' => 'require|max:200',
        'brand_id' => 'number|min:0',
        'stock' => 'number|min:0',
        'weight' => 'number|min:0',
        'is_on_sale' => 'in:0,1',
        'status' => 'in:0,1',
        'deleted_at' => 'in:0,1',
        'is_free_shipping' => 'in:0,1',
        'sort' => 'number',
        'is_recommended' => 'in:0,1',
        'is_new' => 'in:0,1',
        'is_hot' => 'in:0,1',
        'app_id' => 'require|number|min:1',
        'price' => 'require|float|gt:0',
        'market_price' => 'float|egt:0',
        'cost_price' => 'float|egt:0',
        'sub_category_id' => 'number|min:0',
        'third_category_id' => 'number|min:0',
        'type' => 'in:physical,virtual',
        'subtitle' => 'max:500',
        'sale_price' => 'float|egt:0',
        'stock_warning' => 'number|min:0',
        'video' => 'max:500',
        'description' => 'max:65535',
        'specs' => 'array',
        'skus' => 'array',
        'images' => 'array',
        'tag_ids' => 'array',
    ];

    /**
     * 错误消息
     * @var array
     */
    protected $message = [
        'category_id.require' => '分类ID不能为空',
        'category_id.number' => '分类ID必须是数字',
        'category_id.min' => '分类ID不能小于0',
        'name.require' => '商品名称不能为空',
        'name.max' => '商品名称最多不能超过200个字符',
        'brand_id.number' => '品牌ID必须是数字',
        'brand_id.min' => '品牌ID不能小于0',
        'stock.number' => '库存数量必须是数字',
        'stock.min' => '库存数量不能小于0',
        'weight.number' => '商品重量必须是数字',
        'weight.min' => '商品重量不能小于0',
        'is_on_sale.in' => '是否上架值必须是0或1',
        'status.in' => '状态值必须是0或1',
        'deleted_at.in' => '是否删除值必须是0或1',
        'is_free_shipping.in' => '是否包邮值必须是0或1',
        'sort.number' => '商品排序必须是数字',
        'is_recommended.in' => '是否推荐值必须是0或1',
        'is_new.in' => '是否新品值必须是0或1',
        'is_hot.in' => '是否热卖值必须是0或1',
        'app_id.require' => '应用ID不能为空',
        'app_id.number' => '应用ID必须是数字',
        'app_id.min' => '应用ID不能小于1',
        'price.require' => '商品价格不能为空',
        'price.float' => '商品价格必须是数字',
        'price.gt' => '商品价格必须大于0',
        'market_price.float' => '市场价必须是数字',
        'market_price.egt' => '市场价不能小于0',
        'cost_price.float' => '成本价必须是数字',
        'cost_price.egt' => '成本价不能小于0',
        'sub_category_id.number' => '二级分类ID必须是数字',
        'third_category_id.number' => '三级分类ID必须是数字',
        'type.in' => '商品类型值无效',
        'subtitle.max' => '副标题最多不能超过500个字符',
        'sale_price.float' => '促销价必须是数字',
        'sale_price.egt' => '促销价不能小于0',
        'stock_warning.number' => '库存预警值必须是数字',
        'stock_warning.min' => '库存预警值不能小于0',
        'video.max' => '视频URL最多不能超过500个字符',
        'description.max' => '商品描述超出长度限制',
        'specs.array' => '规格数据必须是数组',
        'skus.array' => 'SKU数据必须是数组',
        'images.array' => '图片数据必须是数组',
        'tag_ids.array' => '标签ID必须是数组',
    ];

    /**
     * 验证场景
     * @var array
     */
    protected $scene = [
        'create' => ['category_id', 'name', 'brand_id', 'stock', 'weight', 'is_on_sale', 'status', 'is_free_shipping', 'sort', 'is_recommended', 'is_new', 'is_hot', 'app_id', 'price', 'market_price', 'cost_price', 'sub_category_id', 'third_category_id', 'type', 'subtitle', 'sale_price', 'stock_warning', 'video', 'description', 'specs', 'skus', 'images', 'tag_ids'],
        'update' => ['category_id', 'name', 'brand_id', 'stock', 'weight', 'is_on_sale', 'status', 'is_free_shipping', 'sort', 'is_recommended', 'is_new', 'is_hot', 'price', 'market_price', 'cost_price', 'sub_category_id', 'third_category_id', 'type', 'subtitle', 'sale_price', 'stock_warning', 'video', 'description', 'specs', 'skus', 'images', 'tag_ids'],
    ];
}