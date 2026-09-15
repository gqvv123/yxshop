<?php

namespace app\service;

use app\repository\PromotionRepository;
use app\model\Promotion;
use app\model\NotificationSend;
use Exception;

class PromotionService extends BaseService
{
    protected NotificationSendService $notificationSendService;

    public function __construct(?PromotionRepository $repository = null)
    {
        parent::__construct($repository ?? new PromotionRepository());
        $this->notificationSendService = new NotificationSendService();
    }

    public function getList($appId = 0, $pageSize = 20, $keyword = '')
    {
        return $this->repository->getList($appId, $pageSize, $keyword);
    }

    public function getDetail($id)
    {
        return $this->repository->find($id);
    }

    public function create(array $data)
    {
        return $this->repository->create($data);
    }

    public function update($id, array $data)
    {
        return $this->repository->update($id, $data);
    }

    public function delete($id)
    {
        return $this->repository->softDelete($id);
    }

    /**
     * 发送促销通知（通过通知系统）
     */
    public function notify(int $promotionId, string $title, string $content, string $targetType = 'all', int $appId = 0): array
    {
        $promotion = $this->repository->find($promotionId);
        if (!$promotion) {
            throw new Exception('促销活动不存在');
        }

        // 通过 NotificationSendService 创建系统通知记录
        $sendRecord = $this->notificationSendService->getRepository()->create([
            'scene_id' => 0,
            'template_id' => 0,
            'user_id' => 0,
            'user_type' => $targetType,
            'send_type' => NotificationSend::SEND_TYPE_SYSTEM,
            'send_title' => $title,
            'send_content' => $content,
            'send_to' => $targetType,
            'send_status' => NotificationSend::STATUS_SUCCESS,
            'send_result' => json_encode(['message' => '促销通知已创建', 'promotion_id' => $promotionId]),
            'send_time' => time(),
            'app_id' => $appId,
        ]);

        return ['send_id' => $sendRecord->id];
    }
}
