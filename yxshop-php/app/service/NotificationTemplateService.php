<?php

namespace app\service;

use app\repository\NotificationTemplateRepository;
use app\model\NotificationTemplate;
use app\exception\BusinessException;
use Exception;

/**
 * 通知模板服务类   
 *
 * @property NotificationTemplateRepository $repository
 */
class NotificationTemplateService extends BaseService
{
    public function __construct(?NotificationTemplateRepository $repository = null)
    {
        parent::__construct($repository ?? new NotificationTemplateRepository());
    }

    /**
     * 获取模板列表
     */
    public function getTemplateList($templateType = null, $appId = 0)
    {
        try {
            $this->logInfo('获取模板列表开始', ['template_type' => $templateType, 'app_id' => $appId]);

            $templates = $this->repository->getTemplates($templateType, $appId);

            $this->logInfo('获取模板列表成功', ['app_id' => $appId]);
            return $templates;

        } catch (Exception $e) {
            $this->logError('获取模板列表失败', [
                'template_type' => $templateType,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 创建模板
     */
    public function createTemplate(array $data)
    {
        try {
            $this->logInfo('创建模板开始', ['data' => $data]);



            $template = $this->repository->create($data);

            $this->logInfo('创建模板成功', ['template_id' => $template->template_id]);
            return $template;

        } catch (Exception $e) {
            $this->logError('创建模板失败', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 更新模板
     */
    public function updateTemplate($id, array $data)
    {
        try {
            $this->logInfo('更新模板开始', ['id' => $id, 'data' => $data]);

            $template = $this->repository->findOrFail($id);

            $template = $this->repository->update($id, $data);

            $this->logInfo('更新模板成功', ['template_id' => $id]);
            return $template;

        } catch (Exception $e) {
            $this->logError('更新模板失败', [
                'id' => $id,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 删除模板
     */
    public function deleteTemplate($id)
    {
        try {
            $this->logInfo('删除模板开始', ['id' => $id]);

            $template = $this->repository->findOrFail($id);

            // 检查是否有发送记录
            if ($template->sends()->count() > 0) {
                throw new BusinessException('该模板有发送记录，无法删除');
            }

            $this->repository->delete($id);

            $this->logInfo('删除模板成功', ['template_id' => $id]);
            return true;

        } catch (Exception $e) {
            $this->logError('删除模板失败', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 获取活跃模板
     */
    public function getActiveTemplates($templateType = null, $appId = 0)
    {
        try {
            return $this->repository->getActiveTemplates($templateType, $appId);

        } catch (Exception $e) {
            $this->logError('获取活跃模板失败', [
                'template_type' => $templateType,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 根据类型获取模板
     */
    public function getTemplatesByType($templateType, $appId = 0)
    {
        try {
            return $this->repository->getTemplatesByType($templateType, $appId);

        } catch (Exception $e) {
            $this->logError('根据类型获取模板失败', [
                'template_type' => $templateType,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 获取模板统计
     */
    public function getTemplateStats($appId = 0)
    {
        try {
            return $this->repository->getTemplateStats($appId);

        } catch (Exception $e) {
            $this->logError('获取模板统计失败', [
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
