<?php

namespace app\api\v1\controller;

use app\service\ItemAttributeService;
use app\service\ItemTagService;
use app\service\ItemTypeService;
use support\Request;

/**
 * C 端控制器：商品标签 / 属性 / 类型。
 *
 * 对应管理端「商品管理 → 商品标签 / 商品属性 / 商品类型」。
 * 小程序侧用于商品列表的筛选栏。
 */
class TagController extends BaseController
{
    protected ItemTagService $tagService;
    protected ItemAttributeService $attributeService;
    protected ItemTypeService $typeService;

    public function __construct()
    {
        $this->tagService = new ItemTagService();
        $this->attributeService = new ItemAttributeService();
        $this->typeService = new ItemTypeService();
    }

    /**
     * 商品标签列表
     *
     * GET /api/v1/item/tags
     */
    public function getTags(Request $request)
    {
        $appId = (int) $request->get('app_id', 0);
        $tags = $this->tagService->getTagList($appId);

        return $this->success(['list' => $tags, 'total' => count($tags)]);
    }

    /**
     * 热门标签（首页/列表页快捷筛选）
     *
     * GET /api/v1/item/hot-tags?limit=10
     */
    public function getHotTags(Request $request)
    {
        $appId = (int) $request->get('app_id', 0);
        $limit = min(30, max(1, (int) $request->get('limit', 10)));
        $tags = $this->tagService->getHotTags($appId, $limit);

        return $this->success(['list' => $tags, 'total' => count($tags)]);
    }

    /**
     * 商品属性列表（筛选项，如颜色、尺码）
     *
     * GET /api/v1/item/attributes
     */
    public function getAttributes(Request $request)
    {
        $appId = (int) $request->get('app_id', 0);
        // 属性是筛选项，不分页
        $list = $this->attributeService->getEnabledList($appId);

        return $this->success(['list' => $list, 'total' => count($list)]);
    }

    /**
     * 商品类型列表
     *
     * GET /api/v1/item/types
     */
    public function getTypes(Request $request)
    {
        $appId = (int) $request->get('app_id', 0);
        $list = $this->typeService->getTypeList($appId);

        return $this->success(['list' => $list, 'total' => count($list)]);
    }
}
