<?php

namespace app\admin\controller;

use support\Request;
use app\service\AfterSalesService;
use app\service\UserService;
use app\service\OrderService;

/**
 * 售后控制器（兼容旧路由 /admin/api/services）
 */
class ServiceController extends BaseController
{
    /** @var AfterSalesService */
    protected $afterSalesService;
    /** @var UserService */
    protected $userService;

    public function __construct()
    {
        parent::__construct();
        $this->afterSalesService = new AfterSalesService();
        $this->userService = new UserService();
    }

    public function index(Request $request)
    {
        $appId  = $this->getAppId($request);
        $page   = (int) $request->get('page', 1);
        $limit  = (int) $request->get('page_size', 20);
        $status = $request->get('status', '');
        $type   = $request->get('type', '');

        $services = $this->afterSalesService->getList($status, $type, $page, $limit, $appId);
        return $this->success($services);
    }

    public function show(Request $request, $id)
    {
        $service = $this->afterSalesService->getDetail((int) $id);
        if (!$service) {
            return $this->errorNotFound('售后记录不存在');
        }
        try {
            $user = $this->userService->findOrFail($service->user_id);
        } catch (\Exception $e) {
            $user = null;
        }
        return $this->success([
            'service' => $service,
            'user'    => $user,
            'order'   => $service->order ?? null,
        ]);
    }

    /**
     * 通用处理动作（approve / reject / refund / complete / receive）
     * POST {action}  -> /admin/api/services/{id}/{action}
     */
    public function handle(Request $request, $id)
    {
        $data = $request->post();
        $action = $data['action'] ?? '';
        $operatorId = $this->admin['id'] ?? 0;
        $actorName = $this->admin['name'] ?? null;

        try {
            switch ($action) {
                case 'approve':
                    $result = $this->afterSalesService->process(
                        (int) $id,
                        AfterSalesService::STATUS_APPROVED,
                        $operatorId,
                        $actorName,
                        $data['remark'] ?? null
                    );
                    return $this->success($result, '审核通过');

                case 'reject':
                    $result = $this->afterSalesService->reject(
                        (int) $id,
                        $operatorId,
                        $actorName,
                        $data['reason'] ?? ''
                    );
                    return $this->success($result, '审核拒绝');

                case 'return_ship':
                    // 买家发货（前端传 express_no / express_id）
                    $result = $this->afterSalesService->returnShip(
                        (int) $id,
                        $data['express_no'] ?? '',
                        (int) ($data['express_id'] ?? 0)
                    );
                    return $this->success($result, '买家已发货');

                case 'receive':
                    // 卖家确认收货
                    $result = $this->afterSalesService->receive((int) $id, $operatorId, $actorName);
                    return $this->success($result, '确认收货成功');

                case 'refund':
                    // 发起退款
                    $result = $this->afterSalesService->refund(
                        (int) $id,
                        $operatorId,
                        $actorName,
                        $data['remark'] ?? null
                    );
                    return $this->success($result, '已发起退款');

                case 'complete':
                    // 完成售后（退款到账后）
                    $result = $this->afterSalesService->complete(
                        (int) $id,
                        $operatorId,
                        $actorName,
                        $data['remark'] ?? null
                    );
                    return $this->success($result, '售后已完成');

                default:
                    return $this->error('未知操作：' . $action);
            }
        } catch (\app\exception\BusinessException $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 快捷改状态（兼容旧 updateStatus）
     */
    public function updateStatus(Request $request, $id)
    {
        $status = (int) $request->post('status');
        $remark = $request->post('remark', '');
        $operatorId = $this->admin['id'] ?? 0;
        $actorName = $this->admin['name'] ?? null;

        try {
            $result = $this->afterSalesService->process($id, $status, $operatorId, $actorName, $remark);
            return $this->success(null, '状态更新成功');
        } catch (\app\exception\BusinessException $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 批量审核通过（仅限待处理 → 已通过）
     */
    public function batchProcess(Request $request)
    {
        $ids = (array) $request->post('ids', []);
        if (empty($ids)) {
            return $this->error('缺少ids');
        }

        $operatorId = $this->admin['id'] ?? 0;
        $actorName = $this->admin['name'] ?? null;
        $remark = $request->post('remark', '');

        $success = 0;
        $failed = [];

        foreach ($ids as $id) {
            try {
                $this->afterSalesService->process((int) $id, AfterSalesService::STATUS_APPROVED, $operatorId, $actorName, $remark);
                $success++;
            } catch (\Exception $e) {
                $failed[] = $id;
            }
        }

        if (empty($failed)) {
            return $this->success(null, "批量处理成功：{$success} 条");
        }
        return $this->success(['success' => $success, 'failed' => $failed], "成功 {$success} 条，失败 " . count($failed) . ' 条');
    }

    public function export(Request $request)
    {
        $appId = $this->getAppId($request);
        $data  = $this->afterSalesService->getAllForExport($appId);
        return $this->success($data);
    }

    public function stats(Request $request)
    {
        $appId = $this->getAppId($request);
        $stats = $this->afterSalesService->getStatistics($appId);
        return $this->success($stats);
    }
}
