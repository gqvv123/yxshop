<?php

namespace app\service;

use app\repository\NotificationSendRepository;
use app\repository\NotificationTemplateRepository;
use app\model\NotificationSend;
use app\exception\BusinessException;
use Exception;

class NotificationSendService extends BaseService
{
    protected $templateRepository;

    public function __construct(NotificationSendRepository $repository = null)
    {
        $repository = $repository ?? new NotificationSendRepository();
        parent::__construct($repository);
        $this->templateRepository = new NotificationTemplateRepository();
    }

    public function getPaginatedList(int $appId, int $perPage = 20)
    {
        return $this->repository->getPaginatedByApp($appId, $perPage);
    }

    /**
     * 发送通知
     */
    public function sendNotification($sceneId, $templateId, $userId, $userType, $sendType, $sendTo, $variables = [], $appId = 0)
    {
        try {
            $this->logInfo('发送通知开始', [
                'scene_id' => $sceneId,
                'template_id' => $templateId,
                'user_id' => $userId,
                'send_type' => $sendType,
                'app_id' => $appId
            ]);

            // 获取模板
            $template = $this->templateRepository->find($templateId);
            if (!$template) {
                throw new BusinessException('通知模板不存在');
            }

            // 渲染模板内容
            $title = $template->renderTitle($variables);
            $content = $template->renderContent($variables);

            // 创建发送记录
            $sendRecord = $this->repository->create([
                'scene_id' => $sceneId,
                'template_id' => $templateId,
                'user_id' => $userId,
                'user_type' => $userType,
                'send_type' => $sendType,
                'send_title' => $title,
                'send_content' => $content,
                'send_to' => $sendTo,
                'send_status' => NotificationSend::STATUS_PENDING,
                'app_id' => $appId,
            ]);

            // 执行发送
            $result = $this->executeSend($sendRecord);

            // 更新发送状态
            $sendRecord->send_status = $result['status'];
            $sendRecord->send_result = $result['result'];
            $sendRecord->send_time = time();
            $sendRecord->save();

            $this->logInfo('发送通知成功', ['send_id' => $sendRecord->send_id]);
            return $sendRecord;

        } catch (Exception $e) {
            $this->logError('发送通知失败', [
                'scene_id' => $sceneId,
                'template_id' => $templateId,
                'user_id' => $userId,
                'send_type' => $sendType,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 执行发送
     */
    private function executeSend($sendRecord)
    {
        try {
            switch ($sendRecord->send_type) {
                case NotificationSend::SEND_TYPE_SMS:
                    return $this->sendSms($sendRecord);
                case NotificationSend::SEND_TYPE_EMAIL:
                    return $this->sendEmail($sendRecord);
                case NotificationSend::SEND_TYPE_WECHAT:
                    return $this->sendWechat($sendRecord);
                case NotificationSend::SEND_TYPE_PUSH:
                    return $this->sendPush($sendRecord);
                case NotificationSend::SEND_TYPE_SYSTEM:
                    return $this->sendSystem($sendRecord);
                default:
                    throw new BusinessException('不支持的发送类型');
            }
        } catch (Exception $e) {
            return [
                'status' => NotificationSend::STATUS_FAILED,
                'result' => ['error' => $e->getMessage()]
            ];
        }
    }

    /**
     * 发送短信
     */
    private function sendSms($sendRecord)
    {
        // 这里集成具体的短信服务商
        return [
            'status' => NotificationSend::STATUS_SUCCESS,
            'result' => ['message' => '短信发送成功']
        ];
    }

    /**
     * 发送邮件
     */
    private function sendEmail($sendRecord)
    {
        // 这里集成具体的邮件服务
        return [
            'status' => NotificationSend::STATUS_SUCCESS,
            'result' => ['message' => '邮件发送成功']
        ];
    }

    /**
     * 发送微信
     */
    private function sendWechat($sendRecord)
    {
        // 这里集成微信发送
        return [
            'status' => NotificationSend::STATUS_SUCCESS,
            'result' => ['message' => '微信发送成功']
        ];
    }

    /**
     * 发送推送
     */
    private function sendPush($sendRecord)
    {
        // 这里集成推送服务
        return [
            'status' => NotificationSend::STATUS_SUCCESS,
            'result' => ['message' => '推送发送成功']
        ];
    }

    /**
     * 发送系统通知
     */
    private function sendSystem($sendRecord)
    {
        // 系统通知直接成功
        return [
            'status' => NotificationSend::STATUS_SUCCESS,
            'result' => ['message' => '系统通知发送成功']
        ];
    }

    /**
     * 获取发送记录
     */
    public function getSendRecords($sceneId = null, $templateId = null, $userId = null, $sendType = null, $sendStatus = null, $appId = 0, $limit = 20)
    {
        try {
            return $this->repository->getSendRecords($sceneId, $templateId, $userId, $sendType, $sendStatus, $appId, $limit);

        } catch (Exception $e) {
            $this->logError('获取发送记录失败', [
                'scene_id' => $sceneId,
                'template_id' => $templateId,
                'user_id' => $userId,
                'send_type' => $sendType,
                'send_status' => $sendStatus,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 获取发送统计
     */
    public function getSendStats($startTime = null, $endTime = null, $appId = 0)
    {
        try {
            return $this->repository->getSendStats($startTime, $endTime, $appId);

        } catch (Exception $e) {
            $this->logError('获取发送统计失败', [
                'start_time' => $startTime,
                'end_time' => $endTime,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 获取待发送记录
     */
    public function getPendingSends($sendType = null, $appId = 0, $limit = 100)
    {
        try {
            return $this->repository->getPendingSends($sendType, $appId, $limit);

        } catch (Exception $e) {
            $this->logError('获取待发送记录失败', [
                'send_type' => $sendType,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getList($appId = 0, $pageSize = 20, $keyword = '')
    {
        return $this->repository->getPaginatedList((int) $appId, (int) $pageSize);
    }

    /**
     * 用户消息列表（小程序/H5 端）
     */
    public function getUserInbox(int $userId, int $page = 1, int $pageSize = 20)
    {
        return $this->repository->getUserInbox($userId, $page, $pageSize);
    }

    /**
     * 用户未读消息数
     */
    public function getUserUnreadCount(int $userId): int
    {
        return $this->repository->countUserUnread($userId);
    }

    /**
     * 单条标记已读
     */
    public function markRead(int $id, int $userId): bool
    {
        return $this->repository->markRead($id, $userId) > 0;
    }

    /**
     * 批量标记已读
     */
    public function markMultipleRead(array $ids, int $userId): int
    {
        return $this->repository->markBatchRead($ids, $userId);
    }
}
