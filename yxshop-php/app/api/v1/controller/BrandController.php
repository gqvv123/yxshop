<?php

namespace app\api\v1\controller;

use support\Request;
use app\service\BrandService;

/**
 * 品牌控制器（C端）
 *
 * 提供系统品牌列表，供小程序首页品牌专区组件使用
 */
class BrandController extends BaseController
{
    protected $brandService;

    public function __construct()
    {
        $this->brandService = new BrandService();
    }

    /**
     * 获取品牌列表
     *
     * GET /api/v1/brand/list?app_id=1&is_hot=1&limit=10
     *
     * @param Request $request
     * @return \support\Response
     */
    public function list(Request $request)
    {
        $appId = (int) $request->get('app_id', 0);
        $isHot = $request->get('is_hot');
        $limit = (int) $request->get('limit', 12);

        // 限制最大数量，防止滥用
        if ($limit <= 0 || $limit > 50) {
            $limit = 12;
        }

        try {
            if ($isHot !== null && (int) $isHot === 1) {
                $brands = $this->brandService->getHotBrands($appId, $limit);
            } else {
                $brands = $this->brandService->getActiveBrands($appId, $limit);
            }

            // 过滤输出字段，只返回前端需要的
            $formatted = $brands->map(function ($brand) {
                return [
                    'id'   => (string) $brand->id,
                    'name' => $brand->name,
                    'logo' => \app\model\BaseModel::resolveAssetUrl($brand->logo),
                    'desc' => $brand->desc ?? '',
                    'link' => '/pages/search/search?keyword=' . urlencode($brand->name),
                ];
            })->values();

            return $this->success($formatted);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
