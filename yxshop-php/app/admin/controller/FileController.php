<?php

namespace app\admin\controller;

use support\Request;
use app\service\UploadFileService;
use app\service\UploadGroupService;

class FileController extends BaseController
{
    protected $fileService;
    protected $groupService;

    public function __construct()
    {
        parent::__construct();
        $this->fileService = new UploadFileService();
        $this->groupService = new UploadGroupService();
    }

    public function index(Request $request)
    {
        $appId = $this->getAppId($request);
        $page = (int) $request->get('page', 1);
        $limit = (int) $request->get('page_size', 20);
        $groupId = $request->get('group_id', 0);
        $fileType = $request->get('file_type', '');
        $auditStatus = $request->get('audit_status', '');

        $filters = ['app_id' => $appId];
        if ($groupId) {
            $filters['group_id'] = $groupId;
        }
        if ($fileType) {
            $filters['file_type'] = $fileType;
        }
        if ($auditStatus !== '') {
            $filters['audit_status'] = (int) $auditStatus;
        }

        $result = $this->fileService->getFileList($page, $limit, $filters);
        $groups = $this->groupService->getGroupList($appId);

        $items = $result->items();
        foreach ($items as &$item) {
            $item->url = $item->file_url;
            $item->origin_name = $item->file_name;
        }
        unset($item);

        return $this->success([
            'data'        => $items,
            'total'      => $result->total(),
            'current_page' => $result->currentPage(),
            'per_page'   => $result->perPage(),
            'groups'     => $groups,
        ]);
    }

    public function show(Request $request, $id)
    {
        $file = $this->fileService->getFileById($id);
        if (!$file) {
            return $this->errorNotFound('文件不存在');
        }
        return $this->success($file);
    }

    public function delete(Request $request, $id)
    {
        $result = $this->fileService->deleteFile($id);
        if (!$result) {
            return $this->error('删除失败');
        }
        return $this->success(null, '删除成功');
    }

    public function batchMove(Request $request)
    {
        $ids = (array) $request->post('ids', []);
        $groupId = (int) $request->post('group_id', 0);
        if (empty($ids) || $groupId <= 0) {
            return $this->error('缺少参数');
        }
        $ok = $this->fileService->batchMoveToGroup($ids, $groupId);
        return $ok ? $this->success(null, '移动成功') : $this->error('移动失败');
    }

    public function moveToGroup(Request $request)
    {
        $fileId = $request->post('file_id');
        $groupId = $request->post('group_id');
        $result = $this->fileService->moveToGroup($fileId, $groupId);
        if (!$result) {
            return $this->error('移动失败');
        }
        return $this->success(null, '移动成功');
    }

    public function groupIndex(Request $request)
    {
        $appId = $this->getAppId($request);
        $groups = $this->groupService->getGroupList($appId);
        return $this->success($groups);
    }

    public function storeGroup(Request $request)
    {
        $data = $request->post();
        $data['app_id'] = $this->getAppId($request);
        $result = $this->groupService->createGroup($data);
        if (!$result) {
            return $this->error('添加失败');
        }
        return $this->success($result, '添加成功');
    }

    public function updateGroup(Request $request, $id)
    {
        $data = $request->post();
        $result = $this->groupService->updateGroup($id, $data);
        if (!$result) {
            return $this->error($this->groupService->getError() ?: '更新失败');
        }
        return $this->success($result, '更新成功');
    }

    public function deleteGroup(Request $request, $id)
    {
        $result = $this->groupService->deleteGroup($id);
        if (!$result) {
            return $this->error('删除失败');
        }
        return $this->success(null, '删除成功');
    }

    public function stats(Request $request)
    {
        $appId = $this->getAppId($request);
        $stats = $this->fileService->getFileStats($appId);
        $quota = $this->fileService->getQuota($appId);
        return $this->success([
            'stats' => $stats,
            'quota' => $quota,
        ]);
    }

    /**
     * 审核文件
     */
    public function audit(Request $request, $id)
    {
        $auditStatus = (int) $request->post('audit_status');
        $remark = $request->post('remark', '');
        $auditorId = $this->admin['id'] ?? 0;

        if (!in_array($auditStatus, [UploadFileService::AUDIT_PASSED, UploadFileService::AUDIT_REJECTED], true)) {
            return $this->error('审核状态值非法');
        }

        try {
            $result = $this->fileService->audit((int) $id, $auditStatus, $auditorId, $remark);
            return $this->success($result, '审核成功');
        } catch (\app\exception\BusinessException $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 批量审核
     */
    public function batchAudit(Request $request)
    {
        $ids = (array) $request->post('ids', []);
        $auditStatus = (int) $request->post('audit_status');
        $remark = $request->post('remark', '');
        $auditorId = $this->admin['id'] ?? 0;

        if (empty($ids)) {
            return $this->error('缺少ids');
        }
        if (!in_array($auditStatus, [UploadFileService::AUDIT_PASSED, UploadFileService::AUDIT_REJECTED], true)) {
            return $this->error('审核状态值非法');
        }

        $success = 0;
        $failed = [];
        foreach ($ids as $id) {
            try {
                $this->fileService->audit((int) $id, $auditStatus, $auditorId, $remark);
                $success++;
            } catch (\Exception $e) {
                $failed[] = $id;
            }
        }

        if (empty($failed)) {
            return $this->success(null, "批量审核成功：{$success} 条");
        }
        return $this->success(['success' => $success, 'failed' => $failed], "成功 {$success} 条，失败 " . count($failed) . ' 条');
    }

    /**
     * 更新存储配额
     */
    public function updateQuota(Request $request)
    {
        $appId = $this->getAppId($request);
        $quotaBytes = (int) $request->post('quota_bytes', 0);
        if ($quotaBytes <= 0) {
            return $this->error('配额值非法');
        }
        $operatorId = $this->admin['id'] ?? 0;
        $operatorName = $this->admin['name'] ?? null;
        $result = $this->fileService->updateQuota($appId, $quotaBytes, $operatorId, $operatorName);
        return $this->success($result, '配额更新成功');
    }

    /**
     * 下载文件（记录下载日志）
     */
    public function download(Request $request, $id)
    {
        $file = $this->fileService->getFileById($id);
        if (!$file) {
            return $this->errorNotFound('文件不存在');
        }

        // 记录下载日志
        $this->fileService->logDownload(
            (int) $id,
            $this->admin['id'] ?? 0,
            $this->admin['name'] ?? null,
            $request->getRealIp() ?? '',
            $request->header('user-agent', '')
        );

        return $this->success(['url' => $file->file_url]);
    }
}
