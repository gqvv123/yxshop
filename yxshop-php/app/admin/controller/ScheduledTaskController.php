<?php

namespace app\admin\controller;

use support\Request;
use app\service\ScheduledTaskService;

class ScheduledTaskController extends BaseController
{
    protected $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new ScheduledTaskService();
    }

    public function index(Request $request)
    {
        $appId = $this->getAppId($request);
        $filters = [];
        if ($request->get('task_type')) {
            $filters['task_type'] = $request->get('task_type');
        }
        if ($request->get('status') !== null) {
            $filters['status'] = $request->get('status');
        }
        $pageSize = (int) $request->get('page_size', 20);
        $result = $this->service->getPaginatedList($appId, $filters, $pageSize);
        return $this->success($result);
    }

    public function store(Request $request)
    {
        $data = $request->post();
        $data['app_id'] = $this->getAppId($request);
        $result = $this->service->create($data);
        return $this->success($result, '创建成功');
    }

    public function show(Request $request, $id)
    {
        $result = $this->service->getDetail($id);
        if (!$result) {
            return $this->error('记录不存在');
        }
        return $this->success($result);
    }

    public function update(Request $request, $id)
    {
        $data = $request->post();
        $result = $this->service->update($id, $data);
        return $this->success($result, '更新成功');
    }

    public function destroy(Request $request, $id)
    {
        $this->service->delete($id);
        return $this->success(null, '删除成功');
    }

    public function batchDelete(Request $request)
    {
        $ids = $request->post('ids', []);
        if (empty($ids) || !is_array($ids)) {
            return $this->error('请选择要删除的记录');
        }
        $count = 0;
        foreach ($ids as $id) {
            try {
                $this->service->delete($id);
                $count++;
            } catch (\Throwable $e) {
                // 跳过不存在的记录
            }
        }
        return $this->success(['count' => $count], "成功删除{$count}条记录");
    }

    public function enable(Request $request, $id)
    {
        $result = $this->service->enable($id);
        return $this->success($result, '启用成功');
    }

    public function disable(Request $request, $id)
    {
        $result = $this->service->disable($id);
        return $this->success($result, '禁用成功');
    }

    public function execute(Request $request, $id)
    {
        $result = $this->service->execute($id);
        return $this->success($result, '执行成功');
    }
}
