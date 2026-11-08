<?php

namespace app\service;
use app\model\User;
use app\model\NotificationSend;
use Exception;

/**
 * UserNotificationTrait：从 UserService.php 抽离的sendRegisterNotification、sendLoginNotification、dispatchUserNotification、sendLevelChangeNotification、sendDealerChangeNotification、logDataChange等方法。
 *
 * 拆分目的：控制单文件体积、聚焦单一职责；方法实现保持原样，行为完全等价。
 *
 * @package app\service
 */
trait UserNotificationTrait
{
    private function sendRegisterNotification(User $user): void
    {
        $this->dispatchUserNotification('register', '注册', $user);
    }

    private function sendLoginNotification(User $user): void
    {
        $this->dispatchUserNotification('login', '登录', $user);
    }

    /**
     * 按场景编码派发用户站内通知。
     *
     * 约定：通知场景（scene_code）与通知模板（template_code）使用同一编码，
     * 例如 register / login。后台未配置对应场景或模板时静默跳过，
     * 不影响注册、登录主流程；发送失败同样只记录日志。
     */
    private function dispatchUserNotification(string $sceneCode, string $sceneName, User $user): void
    {
        try {
            $sceneService = new NotificationSceneService();
            $scene = $sceneService->getSceneByCode($sceneCode);
            if (!$scene || (int) $scene->is_active !== 1) {
                $this->logInfo('通知场景未启用，跳过发送', [
                    'scene_code' => $sceneCode,
                    'user_id' => $user->id,
                ]);
                return;
            }

            $templateService = new NotificationTemplateService();
            $template = $templateService->getTemplateByCode($sceneCode);
            if (!$template || (int) $template->is_active !== 1) {
                $this->logInfo('通知模板未启用，跳过发送', [
                    'scene_code' => $sceneCode,
                    'user_id' => $user->id,
                ]);
                return;
            }

            $sendService = new NotificationSendService();
            $sendService->sendNotification(
                (int) $scene->id,
                (int) $template->id,
                (int) $user->id,
                'user',
                NotificationSend::SEND_TYPE_SYSTEM,
                (string) $user->id,
                [
                    'user_id' => $user->id,
                    'nickname' => $user->nickname ?? $user->username ?? '',
                    'scene' => $sceneName,
                    'time' => date('Y-m-d H:i:s'),
                ],
                (int) ($scene->app_id ?? 0)
            );

            $this->logInfo('发送' . $sceneName . '通知成功', [
                'scene_code' => $sceneCode,
                'user_id' => $user->id,
            ]);
        } catch (Exception $e) {
            $this->logError('发送' . $sceneName . '通知失败', [
                'scene_code' => $sceneCode,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function sendLevelChangeNotification(User $user, $level): void
    {
        try {
            $this->logInfo('发送等级变更通知', ['user_id' => $user->id, 'level_id' => $level->id]);
        } catch (Exception $e) {
            $this->logError('发送等级变更通知失败', ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }
    }

    private function sendDealerChangeNotification(User $user, $isDealer): void
    {
        try {
            $this->logInfo('发送分销商变更通知', ['user_id' => $user->id, 'is_dealer' => $isDealer]);
        } catch (Exception $e) {
            $this->logError('发送分销商变更通知失败', ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }
    }

    /**
     * 数据变更审计日志
     * @enterprise-only 接入 DataChangeLogService 后入库（现仅打 info 日志）
     */
    private function logDataChange($tableName, $recordId, $changeType, $oldValue, $newValue): void
    {
        $this->logInfo('记录数据变更日志', [
            'table_name'  => $tableName,
            'record_id'   => $recordId,
            'change_type' => $changeType,
        ]);
    }
}
