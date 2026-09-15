<?php

namespace app\admin\controller;

use support\Request;
use app\service\PromotionService;

class PromotionController extends BaseController
{
    protected $promotionService;

    public function __construct()
    {
        parent::__construct();
        $this->promotionService = new PromotionService();
    }

    public function index(Request $request)
    {
        $appId = $this->getAppId($request);
        $pageSize = (int) $request->get('page_size', 20);
        $keyword = $request->get('keyword', '');
        $result = $this->promotionService->getList($appId, $pageSize, $keyword);
        return $this->success($result);
    }

    public function store(Request $request)
    {
        $data = $request->post();
        $data['app_id'] = $this->getAppId($request);
        $result = $this->promotionService->create($data);
        return $this->success($result, '创建成功');
    }

    public function show(Request $request, $id)
    {
        $result = $this->promotionService->getDetail($id);
        if (!$result) {
            return $this->error('记录不存在');
        }
        return $this->success($result);
    }

    public function update(Request $request, $id)
    {
        $data = $request->post();
        $result = $this->promotionService->update($id, $data);
        return $this->success(null, '更新成功');
    }

    public function destroy(Request $request, $id)
    {
        $this->promotionService->delete($id);
        return $this->success(null, '删除成功');
    }

    /**
     * 发送促销通知（通过通知系统）
     */
    public function notify(Request $request, $id)
    {
        $data = $request->post();
        $title = $data['title'] ?? '';
        $content = $data['content'] ?? '';
        $targetType = $data['target_type'] ?? 'all';

        if (empty($title) || empty($content)) {
            return $this->error('标题和内容不能为空');
        }

        try {
            $appId = $this->getAppId($request);
            $result = $this->promotionService->notify((int) $id, $title, $content, $targetType, $appId);
            return $this->success($result, '通知发送成功');
        } catch (\Throwable $e) {
            return $this->error('通知发送失败：' . $e->getMessage());
        }
    }
}
