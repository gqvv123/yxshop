<?php

namespace app\service;

use app\repository\ApplyRepository;
use app\model\Apply;
use app\exception\BusinessException;
use Exception;

/**
 * 申请服务类
 *
 * @property ApplyRepository $repository
 */
class ApplyService extends BaseService
{
    public function __construct(ApplyRepository $repository = null)
    {
        $repository = $repository ?? new ApplyRepository();
        parent::__construct($repository);
    }

    /**
     * 创建申请
     */
    public function createApply($userId, $applyType, $applyTitle, $applyContent, $applyData = [], $appId = 0)
    {
        try {
            $this->logInfo('创建申请开始', [
                'user_id' => $userId,
                'apply_type' => $applyType,
                'apply_title' => $applyTitle,
                'app_id' => $appId
            ]);

            $apply = $this->repository->create([
                'user_id' => $userId,
                'apply_type' => $applyType,
                'apply_title' => $applyTitle,
                'apply_content' => $applyContent,
                'apply_data' => $applyData,
                'apply_status' => Apply::STATUS_PENDING,
                'apply_time' => time(),
                'app_id' => $appId,
            ]);

            $this->logInfo('创建申请成功', ['apply_id' => $apply->apply_id]);
            return $apply;

        } catch (Exception $e) {
            $this->logError('创建申请失败', [
                'user_id' => $userId,
                'apply_type' => $applyType,
                'apply_title' => $applyTitle,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 获取申请列表
     */
    public function getApplyList($userId = null, $applyType = null, $applyStatus = null, $appId = 0, $limit = 20)
    {
        try {
            return $this->repository->getApplies($userId, $applyType, $applyStatus, $appId, $limit);

        } catch (Exception $e) {
            $this->logError('获取申请列表失败', [
                'user_id' => $userId,
                'apply_type' => $applyType,
                'apply_status' => $applyStatus,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 获取待审核申请
     */
    public function getPendingApplies($applyType = null, $appId = 0, $limit = 20)
    {
        try {
            return $this->repository->getPendingApplies($applyType, $appId, $limit);

        } catch (Exception $e) {
            $this->logError('获取待审核申请失败', [
                'apply_type' => $applyType,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 审核申请
     */
    public function auditApply($applyId, $auditUser, $auditResult, $auditRemark = '')
    {
        try {
            $this->logInfo('审核申请开始', [
                'apply_id' => $applyId,
                'audit_user' => $auditUser,
                'audit_result' => $auditResult
            ]);

            $apply = $this->repository->findOrFail($applyId);

            if ($apply->apply_status != Apply::STATUS_PENDING) {
                throw new BusinessException('该申请已处理');
            }

            $apply->apply_status = $auditResult;
            $apply->audit_user = $auditUser;
            $apply->audit_remark = $auditRemark;
            $apply->audit_time = time();
            $apply->save();

            $this->logInfo('审核申请成功', ['apply_id' => $applyId, 'result' => $auditResult]);
            return $apply;

        } catch (Exception $e) {
            $this->logError('审核申请失败', [
                'apply_id' => $applyId,
                'audit_user' => $auditUser,
                'audit_result' => $auditResult,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 处理申请
     */
    public function processApply($applyId, $processUser)
    {
        try {
            $this->logInfo('处理申请开始', ['apply_id' => $applyId, 'process_user' => $processUser]);

            $apply = $this->repository->findOrFail($applyId);

            if ($apply->apply_status != Apply::STATUS_APPROVED) {
                throw new BusinessException('该申请未通过审核');
            }

            $apply->apply_status = Apply::STATUS_PROCESSING;
            $apply->audit_user = $processUser;
            $apply->audit_time = time();
            $apply->save();

            $this->logInfo('处理申请成功', ['apply_id' => $applyId]);
            return $apply;

        } catch (Exception $e) {
            $this->logError('处理申请失败', [
                'apply_id' => $applyId,
                'process_user' => $processUser,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 完成申请
     */
    public function completeApply($applyId, $completeUser)
    {
        try {
            $this->logInfo('完成申请开始', ['apply_id' => $applyId, 'complete_user' => $completeUser]);

            $apply = $this->repository->findOrFail($applyId);

            if ($apply->apply_status != Apply::STATUS_PROCESSING) {
                throw new BusinessException('该申请未在处理中');
            }

            $apply->apply_status = Apply::STATUS_COMPLETED;
            $apply->audit_user = $completeUser;
            $apply->audit_time = time();
            $apply->save();

            $this->logInfo('完成申请成功', ['apply_id' => $applyId]);
            return $apply;

        } catch (Exception $e) {
            $this->logError('完成申请失败', [
                'apply_id' => $applyId,
                'complete_user' => $completeUser,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 取消申请
     */
    public function cancelApply($applyId, $userId)
    {
        try {
            $this->logInfo('取消申请开始', ['apply_id' => $applyId, 'user_id' => $userId]);

            $apply = $this->repository->findOrFail($applyId);

            if ($apply->user_id != $userId) {
                throw new BusinessException('无权限取消该申请');
            }

            if ($apply->apply_status != Apply::STATUS_PENDING) {
                throw new BusinessException('该申请已处理，无法取消');
            }

            $apply->apply_status = Apply::STATUS_CANCELLED;
            $apply->audit_time = time();
            $apply->save();

            $this->logInfo('取消申请成功', ['apply_id' => $applyId]);
            return $apply;

        } catch (Exception $e) {
            $this->logError('取消申请失败', [
                'apply_id' => $applyId,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 获取申请统计
     */
    public function getApplyStats($startTime = null, $endTime = null, $appId = 0)
    {
        try {
            return $this->repository->getApplyStats($startTime, $endTime, $appId);

        } catch (Exception $e) {
            $this->logError('获取申请统计失败', [
                'start_time' => $startTime,
                'end_time' => $endTime,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 获取用户申请统计
     */
    public function getUserApplyStats($userId, $appId = 0)
    {
        try {
            return $this->repository->getUserApplyStats($userId, $appId);

        } catch (Exception $e) {
            $this->logError('获取用户申请统计失败', [
                'user_id' => $userId,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function updateApply($id, array $data)
    {
        return $this->repository->update($id, $data);
    }

    public function batchProcess(array $ids, $status = 2)
    {
        return $this->repository->updateWhere(['apply_id' => $ids], ['apply_status' => $status]);
    }

    public function getAll()
    {
        return $this->repository->all();
    }

    /**
     * 获取用户最新的企业认证申请
     */
    public function getLatestByUserAndType(int $userId, int $applyType)
    {
        return $this->repository->query()
            ->where('user_id', $userId)
            ->where('apply_type', $applyType)
            ->orderBy('id', 'desc')
            ->first();
    }

    /**
     * 检查用户是否有待审核的申请
     */
    public function hasPendingByUserAndType(int $userId, int $applyType): bool
    {
        return $this->repository->query()
            ->where('user_id', $userId)
            ->where('apply_type', $applyType)
            ->whereIn('apply_status', [Apply::STATUS_PENDING, Apply::STATUS_PROCESSING])
            ->exists();
    }
}
