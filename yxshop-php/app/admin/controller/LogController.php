<?php

namespace app\admin\controller;

use support\Request;
use app\service\LoginLogService;
use app\service\AdminLogService;
use app\service\UserLogService;
use app\service\SystemLogService;

class LogController extends BaseController
{
    protected $loginLogService;
    protected $adminLogService;
    protected $userLogService;
    protected $systemLogService;

    public function __construct()
    {
        parent::__construct();
        $this->loginLogService = new LoginLogService();
        $this->adminLogService = new AdminLogService();
        $this->userLogService = new UserLogService();
        $this->systemLogService = new SystemLogService();
    }

    public function login(Request $request)
    {
        $appId = $this->getAppId($request);
        $page = (int) $request->get('page', 1);
        $limit = (int) $request->get('page_size', 20);
        $userType = $request->get('user_type', '');
        $status = $request->get('status', '');

        $logs = $this->loginLogService->getLoginLogList($page, $limit, [
            'user_type' => $userType,
            'status' => $status,
            'app_id' => $appId
        ]);
        return $this->success($logs);
    }

    public function admin(Request $request)
    {
        $appId = $this->getAppId($request);
        $page = (int) $request->get('page', 1);
        $limit = (int) $request->get('page_size', 20);
        $adminId = $request->get('admin_id', '');
        $action = $request->get('action', '');

        $logs = $this->adminLogService->getAdminLogList($page, $limit, [
            'admin_id' => $adminId,
            'action' => $action,
            'app_id' => $appId
        ]);
        return $this->success($logs);
    }

    public function user(Request $request)
    {
        $appId = $this->getAppId($request);
        $page = (int) $request->get('page', 1);
        $limit = (int) $request->get('page_size', 20);
        $userId = $request->get('user_id', '');
        $action = $request->get('action', '');

        $logs = $this->userLogService->getUserLogList($page, $limit, [
            'user_id' => $userId,
            'action' => $action,
            'app_id' => $appId
        ]);
        return $this->success($logs);
    }

    public function system(Request $request)
    {
        $appId = $this->getAppId($request);
        $page = (int) $request->get('page', 1);
        $limit = (int) $request->get('page_size', 20);
        $level = $request->get('level', '');
        $module = $request->get('module', '');

        $logs = $this->systemLogService->getSystemLogList($page, $limit, [
            'level' => $level,
            'module' => $module,
            'app_id' => $appId
        ]);
        return $this->success($logs);
    }

    public function show(Request $request, $id)
    {
        $type = $request->get('type', 'login');
        $log = $this->getLogByType($type, $id);
        if (!$log) {
            return $this->errorNotFound('日志记录不存在');
        }
        return $this->success($log);
    }

    public function delete(Request $request, $id)
    {
        $type = $request->post('type', 'login');
        $result = $this->deleteLogByType($type, $id);
        if (!$result) {
            return $this->error('删除失败');
        }
        return $this->success(null, '删除成功');
    }

    public function batchDelete(Request $request)
    {
        $ids = $request->post('ids', []);
        $type = $request->post('type', 'login');
        $result = $this->batchDeleteLogsByType($type, $ids);
        if (!$result) {
            return $this->error('批量删除失败');
        }
        return $this->success(null, '批量删除成功');
    }

    public function clear(Request $request)
    {
        $type = $request->post('type', 'login');
        $days = $request->post('days', 30);
        $result = $this->clearLogsByType($type, $days);
        if (!$result) {
            return $this->error('清空失败');
        }
        return $this->success(null, '清空成功');
    }

    public function export(Request $request)
    {
        $type = $request->get('type', 'login');
        $startDate = $request->get('start_date', '');
        $endDate = $request->get('end_date', '');
        $format = $request->get('format', 'json');

        $data = $this->exportLogsByType($type, $startDate, $endDate);
        if (!$data) {
            return $this->error('导出失败');
        }

        if ($format === 'csv') {
            return $this->exportCsv($type, $data);
        }

        return $this->success($data, '导出成功');
    }

    public function api(Request $request)
    {
        $appId = $this->getAppId($request);
        $page = (int) $request->get('page', 1);
        $limit = (int) $request->get('page_size', 20);
        $requestUrl = $request->get('request_url', '');
        $method = $request->get('method', '');

        $filters = ['app_id' => $appId];
        if ($requestUrl) {
            $filters['action'] = $requestUrl;
        }
        if ($method) {
            $filters['log_type'] = $method;
        }

        $logs = $this->systemLogService->getSystemLogList($page, $limit, $filters);
        return $this->success($logs);
    }

    private function getLogByType($type, $id)
    {
        return match ($type) {
            'login' => $this->loginLogService->getLoginLogById($id),
            'admin' => $this->adminLogService->getAdminLogById($id),
            'user' => $this->userLogService->getUserLogById($id),
            'system' => $this->systemLogService->getSystemLogById($id),
            'api' => $this->systemLogService->getSystemLogById($id),
            default => null,
        };
    }

    private function deleteLogByType($type, $id)
    {
        return match ($type) {
            'login' => $this->loginLogService->deleteLoginLog($id),
            'admin' => $this->adminLogService->deleteAdminLog($id),
            'user' => $this->userLogService->deleteUserLog($id),
            'system' => $this->systemLogService->deleteSystemLog($id),
            'api' => $this->systemLogService->deleteSystemLog($id),
            default => false,
        };
    }

    private function batchDeleteLogsByType($type, $ids)
    {
        return match ($type) {
            'login' => $this->loginLogService->batchDeleteLoginLogs($ids),
            'admin' => $this->adminLogService->batchDeleteAdminLogs($ids),
            'user' => $this->userLogService->batchDeleteUserLogs($ids),
            'system' => $this->systemLogService->batchDeleteSystemLogs($ids),
            'api' => $this->systemLogService->batchDeleteSystemLogs($ids),
            default => false,
        };
    }

    private function clearLogsByType($type, $days)
    {
        return match ($type) {
            'login' => $this->loginLogService->clearOldLoginLogs($days),
            'admin' => $this->adminLogService->clearOldAdminLogs($days),
            'user' => $this->userLogService->clearOldUserLogs($days),
            'system' => $this->systemLogService->clearOldSystemLogs($days),
            'api' => $this->systemLogService->clearOldSystemLogs($days),
            default => false,
        };
    }

    private function exportLogsByType($type, $startDate, $endDate)
    {
        return match ($type) {
            'login' => $this->loginLogService->exportLoginLogs($startDate, $endDate),
            'admin' => $this->adminLogService->exportAdminLogs($startDate, $endDate),
            'user' => $this->userLogService->exportUserLogs($startDate, $endDate),
            'system' => $this->systemLogService->exportSystemLogs($startDate, $endDate),
            'api' => $this->systemLogService->exportSystemLogs($startDate, $endDate),
            default => false,
        };
    }

    private function exportCsv(string $type, $data)
    {
        $csvHeaders = $this->getCsvHeaders($type);
        $filename = $type . '_logs_' . date('Ymd_His') . '.csv';

        $output = fopen('php://temp', 'r+');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($output, $csvHeaders);

        $rows = $data instanceof \Illuminate\Support\Collection ? $data : collect($data);
        foreach ($rows as $row) {
            $rowArray = is_object($row) ? $row->toArray() : (array) $row;
            $csvRow = $this->mapRowToCsv($type, $rowArray);
            fputcsv($output, $csvRow);
        }

        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);

        return new \support\Response(200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ], $content);
    }

    private function getCsvHeaders(string $type): array
    {
        return match ($type) {
            'login' => ['ID', '用户类型', '用户ID', '用户名', '登录类型', '登录结果', '登录IP', '登录地点', '登录时间'],
            'admin' => ['ID', '管理员ID', '操作模块', '操作动作', '请求URL', '请求方法', 'IP', '操作时间'],
            'user' => ['ID', '用户ID', '用户名', '模块', '动作', '操作类型', '操作描述', '请求URL', '请求方法', '操作结果', 'IP', '操作时间'],
            'system', 'api' => ['ID', '日志级别', '日志类型', '模块', '动作', '日志消息', 'IP', '用户代理', '创建时间'],
            default => ['ID', '数据'],
        };
    }

    private function mapRowToCsv(string $type, array $row): array
    {
        return match ($type) {
            'login' => [
                $row['id'] ?? '',
                $row['user_type'] ?? '',
                $row['user_id'] ?? '',
                $row['user_name'] ?? '',
                $row['login_type'] ?? '',
                $row['login_result'] ?? '',
                $row['login_ip'] ?? '',
                $row['location'] ?? '',
                isset($row['created_at']) ? date('Y-m-d H:i:s', is_numeric($row['created_at']) ? $row['created_at'] : strtotime($row['created_at'])) : '',
            ],
            'admin' => [
                $row['id'] ?? '',
                $row['admin_id'] ?? '',
                $row['module'] ?? '',
                $row['action'] ?? '',
                $row['request_url'] ?? '',
                $row['request_method'] ?? '',
                $row['ip'] ?? '',
                isset($row['created_at']) ? date('Y-m-d H:i:s', is_numeric($row['created_at']) ? $row['created_at'] : strtotime($row['created_at'])) : '',
            ],
            'user' => [
                $row['id'] ?? '',
                $row['user_id'] ?? '',
                $row['user_name'] ?? '',
                $row['module'] ?? '',
                $row['action'] ?? '',
                $row['operation_type'] ?? '',
                $row['operation_desc'] ?? '',
                $row['request_url'] ?? '',
                $row['request_method'] ?? '',
                $row['operation_result'] ?? '',
                $row['ip'] ?? '',
                isset($row['created_at']) ? date('Y-m-d H:i:s', is_numeric($row['created_at']) ? $row['created_at'] : strtotime($row['created_at'])) : '',
            ],
            'system', 'api' => [
                $row['id'] ?? '',
                $row['log_level'] ?? '',
                $row['log_type'] ?? '',
                $row['module'] ?? '',
                $row['action'] ?? '',
                $row['message'] ?? '',
                $row['ip'] ?? '',
                $row['user_agent'] ?? '',
                isset($row['created_at']) ? date('Y-m-d H:i:s', is_numeric($row['created_at']) ? $row['created_at'] : strtotime($row['created_at'])) : '',
            ],
            default => [$row['id'] ?? '', json_encode($row, JSON_UNESCAPED_UNICODE)],
        };
    }
}
