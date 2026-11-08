<?php

namespace app\admin\controller;

use support\Request;
use app\service\ItemService;
use app\service\CategoryService;
use app\service\BrandService;
use app\validate\ItemValidate;
use app\exception\ValidationException;

class ItemController extends BaseController
{
    protected $itemService;
    protected $categoryService;
    protected $brandService;

    public function __construct()
    {
        parent::__construct();
        $this->itemService = new ItemService();
        $this->categoryService = new CategoryService();
        $this->brandService = new BrandService();
    }

    public function index(Request $request)
    {
        $appId = $this->getAppId($request);

        $filters = [
            'keyword' => $request->get('keyword'),
            'category_id' => $request->get('category_id'),
            'brand_id' => $request->get('brand_id'),
            'status' => $request->get('status'),
        ];
        $page = (int) $request->get('page', 1);
        $pageSize = (int) $request->get('page_size', 20);

        $items = $this->itemService->searchItems($appId, $filters, $pageSize);

        return $this->paginate($items);
    }

    public function show(Request $request, $id)
    {
        $item = $this->itemService->findWithRelations($id);
        if (!$item) {
            return $this->errorNotFound('商品不存在');
        }
        return $this->success($item);
    }

    public function skus(Request $request, $id)
    {
        $skus = $this->itemService->getItemSkus($id);
        return $this->success($skus);
    }

    public function store(Request $request)
    {
        $data = $request->post();
        $data['app_id'] = $this->getAppId($request);

        $validate = new ItemValidate();
        $validate->failException(false);
        if (!$validate->scene('create')->check($data)) {
            throw new ValidationException($validate->getError());
        }

        $item = $this->itemService->create($data);
        return $this->success($item, '商品创建成功');
    }

    public function update(Request $request, $id)
    {
        $data = $request->post();

        $validate = new ItemValidate();
        $validate->failException(false);
        if (!$validate->scene('update')->check($data)) {
            throw new ValidationException($validate->getError());
        }

        $admin = $request->admin ?? [];
        $item = $this->itemService->update($id, $data, $admin['id'] ?? 0, $admin['username'] ?? '');
        return $this->success($item, '商品更新成功');
    }

    public function destroy(Request $request, $id)
    {
        $this->itemService->delete($id);
        return $this->success(null, '商品删除成功');
    }

    public function updateStatus(Request $request, $id)
    {
        $status = $request->post('status');
        if (!in_array($status, [0, 1, '0', '1'], true)) {
            return $this->error('状态值无效，必须是0或1');
        }
        $this->itemService->updateItemStatus($id, (int) $status);
        return $this->success(null, '状态更新成功');
    }

    public function batchStatus(Request $request)
    {
        $ids = $request->post('ids');
        $status = $request->post('status');

        if (empty($ids) || !is_array($ids)) {
            return $this->error('请选择商品');
        }
        if (!in_array($status, [0, 1, '0', '1'], true)) {
            return $this->error('状态值无效，必须是0或1');
        }

        $this->itemService->batch('update', $ids, ['status' => (int) $status]);
        return $this->success(null, '批量更新成功');
    }

    public function batchDelete(Request $request)
    {
        $ids = $request->post('ids');
        if (empty($ids) || !is_array($ids)) {
            return $this->error('请选择商品');
        }
        $this->itemService->batch('soft_delete', $ids);
        return $this->success(null, '批量删除成功');
    }

    public function select(Request $request)
    {
        $appId = $this->getAppId($request);
        $keyword = $request->get('keyword', '');
        $pageSize = (int) $request->get('page_size', 999);

        $items = $this->itemService->getSelectList($appId, $keyword, $pageSize);

        if ($items instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            return $this->paginate($items);
        }
        return $this->success($items);
    }

    public function summary(Request $request)
    {
        $appId = $this->getAppId($request);
        $period = $request->input('period', 'month');
        $data = $this->itemService->getSummary($appId, $period);
        return $this->success($data);
    }

    public function batchPrice(Request $request)
    {
        $ids = $request->post('ids', []);
        $type = $request->post('type', 'fixed');
        $value = $request->post('value', 0);
        $admin = $request->admin ?? [];

        if (empty($ids) || !is_array($ids)) {
            return $this->error('请选择商品');
        }
        if (!in_array($type, ['fixed', 'percent'])) {
            return $this->error('改价方式无效');
        }

        $result = $this->itemService->batchPrice($ids, $type, (float) $value, $admin['id'] ?? 0, $admin['username'] ?? '');
        return $this->success($result);
    }

    public function stockAdjust(Request $request, $id)
    {
        if (empty($id)) {
            return $this->error('商品ID不能为空');
        }
        $type = $request->post('type', 'in');
        $quantity = (int) $request->post('quantity', 0);
        $remark = $request->post('remark', '');
        $admin = $request->admin ?? [];

        if (!in_array($type, ['in', 'out', 'check'])) {
            return $this->error('调整类型无效');
        }
        if ($quantity < 0) {
            return $this->error('数量不能为负');
        }

        try {
            $result = $this->itemService->adjustStock((int) $id, $type, $quantity, $remark, $admin['id'] ?? 0, $admin['username'] ?? '');
            return $this->success($result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function batchStockAdjust(Request $request)
    {
        $ids = $request->post('ids', []);
        $type = $request->post('type', 'in');
        $quantity = (int) $request->post('quantity', 0);
        $remark = $request->post('remark', '');
        $admin = $request->admin ?? [];

        if (empty($ids) || !is_array($ids)) {
            return $this->error('请选择商品');
        }

        $result = $this->itemService->batchAdjustStock($ids, $type, $quantity, $remark, $admin['id'] ?? 0, $admin['username'] ?? '');
        return $this->success($result);
    }

    public function duplicate(Request $request, $id)
    {
        if (empty($id)) {
            return $this->error('商品ID不能为空');
        }
        $admin = $request->admin ?? [];
        try {
            $result = $this->itemService->duplicateItem((int) $id, $admin['id'] ?? 0);
            return $this->success($result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function priceHistory(Request $request, $id)
    {
        if (empty($id)) {
            return $this->error('商品ID不能为空');
        }
        try {
            $data = $this->itemService->getPriceHistory((int) $id);
            return $this->success($data);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function export(Request $request)
    {
        $appId = $this->getAppId($request);
        $format = $request->input('format', 'csv');
        if (!is_string($format) || !in_array($format, ['csv', 'xlsx'], true)) {
            $format = 'csv';
        }
        $filters = [
            'keyword' => $request->input('keyword'),
            'category_id' => $request->input('category_id'),
            'brand_id' => $request->input('brand_id'),
            'status' => $request->input('status'),
        ];

        // ids 可能以逗号分隔字符串或数组形式传入
        $ids = $request->input('ids', []);
        if (is_string($ids) && $ids !== '') {
            $ids = array_filter(array_map('intval', explode(',', $ids)), fn($v) => $v > 0);
        }
        if (!is_array($ids)) {
            $ids = [];
        }

        try {
            return $this->itemService->exportItems($appId, $filters, $format, $ids);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
