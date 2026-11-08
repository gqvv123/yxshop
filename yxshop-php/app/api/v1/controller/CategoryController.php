<?php

namespace app\api\v1\controller;

use support\Request;
use app\service\CategoryService;
use app\service\ItemService;

/**
 * 分类控制器（C端）
 *
 * 提供分类树、分类下商品列表、分类下品牌
 */
class CategoryController extends BaseController
{
    protected $categoryService;
    protected $itemService;

    public function __construct()
    {
        $this->categoryService = new CategoryService();
        $this->itemService = new ItemService();
    }

    /**
     * 获取分类树（三级）
     *
     * GET /api/v1/category/tree?app_id=1
     *
     * 返回可见分类的树形结构（一级 > 二级 > 三级），含 icon、image
     */
    public function tree(Request $request)
    {
        $appId = (int) $request->get('app_id', 0);

        try {
            $tree = $this->categoryService->getCategoryTree($appId);

            // 过滤输出字段，只返回前端需要的
            $formatted = $tree->map(function ($cat) {
                return [
                    'id'       => (string) $cat->id,
                    'name'     => $cat->name,
                    'icon'     => \app\model\BaseModel::resolveAssetUrl($cat->icon),
                    'image'    => \app\model\BaseModel::resolveAssetUrl($cat->image),
                    'level'    => $cat->level,
                    'children' => $cat->children->map(function ($sub) {
                        return [
                            'id'       => (string) $sub->id,
                            'name'     => $sub->name,
                            'icon'     => \app\model\BaseModel::resolveAssetUrl($sub->icon),
                            'image'    => \app\model\BaseModel::resolveAssetUrl($sub->image),
                            'level'    => $sub->level,
                            'children' => $sub->children->map(function ($third) {
                                return [
                                    'id'    => (string) $third->id,
                                    'name'  => $third->name,
                                    'icon'  => \app\model\BaseModel::resolveAssetUrl($third->icon),
                                    'image' => \app\model\BaseModel::resolveAssetUrl($third->image),
                                    'level' => $third->level,
                                ];
                            })->values(),
                        ];
                    })->values(),
                ];
            })->values();

            return $this->success($formatted);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 获取分类下商品列表
     *
     * GET /api/v1/category/items?category_id=1&page=1&page_size=20&sort=comprehensive&min_price=0&max_price=1000
     *
     * sort: comprehensive(综合) / sales(销量) / price_asc(价格升序) / price_desc(价格降序) / new(新品)
     */
    public function items(Request $request)
    {
        $categoryId = $request->get('category_id', '');
        if (empty($categoryId)) {
            return $this->error('分类ID不能为空');
        }

        $params = $request->all();
        $params['cat_id'] = $categoryId;
        $params['user_id'] = $this->getCurrentUserId($request);

        try {
            $result = $this->itemService->getItemList($params);

            // 精简输出字段
            $list = $result['data'] ?? $result['list'] ?? $result;
            if (is_array($list) && isset($list[0])) {
                $list = collect($list)->map(function ($item) {
                    return [
                        'id'           => (string) ($item->id ?? $item['id'] ?? 0),
                        'name'         => $item->name ?? $item['name'] ?? '',
                        'subtitle'     => $item->subtitle ?? $item['subtitle'] ?? '',
                        'sale_price'   => $item->sale_price ?? $item['sale_price'] ?? 0,
                        'price'        => $item->price ?? $item['price'] ?? 0,
                        'image'        => \app\model\BaseModel::resolveAssetUrl($item->main_image ?? $item->image ?? ($item->images->first()->url ?? '')),
                        'total_sales'  => $item->total_sales ?? $item['total_sales'] ?? 0,
                        'is_hot'       => $item->is_hot ?? $item['is_hot'] ?? 0,
                        'is_new'       => $item->is_new ?? $item['is_new'] ?? 0,
                        'is_recommended' => $item->is_recommended ?? $item['is_recommended'] ?? 0,
                    ];
                })->values();
            }

            // 保持分页结构
            $result['data'] = $list ?? $result['data'] ?? [];
            $result['list'] = $list ?? $result['list'] ?? [];

            return $this->success($result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 获取分类下品牌列表
     *
     * GET /api/v1/category/brands?category_id=1
     */
    public function brands(Request $request)
    {
        $categoryId = $request->get('category_id', '');
        if (empty($categoryId)) {
            return $this->error('分类ID不能为空');
        }

        try {
            // 获取分类下所有子分类ID（含自身）
            $category = $this->categoryService->getCategoryById($categoryId);
            if (!$category) {
                return $this->success([]);
            }
            $allCategoryIds = $this->categoryService->getCategoryTree($category->app_id);

            // 简化：直接查商品表中该分类下的品牌
            $brands = $this->itemService->getBrandsByCategory($categoryId);

            return $this->success($brands);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
