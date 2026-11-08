<?php

namespace app\service;

use app\repository\NotificationTemplateRepository;
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

            $name = trim((string) ($data['template_name'] ?? ''));
            $code = trim((string) ($data['template_code'] ?? ''));
            if ($name === '') {
                throw new BusinessException('请填写模板名称');
            }
            if ($code === '') {
                throw new BusinessException('请填写模板编码');
            }
            $data['template_name'] = $name;
            $data['template_code'] = $code;
            $data['app_id'] = (int) ($data['app_id'] ?? 0);
            $data['template_type'] = (int) ($data['template_type'] ?? 10);
            $data['template_content'] = (string) ($data['template_content'] ?? '');
            $data['template_title'] = (string) ($data['template_title'] ?? '');
            $data['template_category'] = (string) ($data['template_category'] ?? '');

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
     * 启用/禁用模板
     *
     * @param int|string $id
     * @param int        $status 1 启用 0 禁用
     */
    public function updateTemplateStatus($id, $status): bool
    {
        $template = $this->repository->find((int) $id);
        if (!$template) {
            throw new BusinessException('通知模板不存在', 404);
        }
        $status = (int) $status;
        if (!in_array($status, [0, 1], true)) {
            throw new BusinessException('状态值无效，只能为 0 或 1');
        }
        $this->repository->update($id, ['is_active' => $status]);
        $this->logInfo('更新模板状态成功', ['id' => $id, 'is_active' => $status]);
        return true;
    }

    /**
     * 测试模板：用变量占位符渲染一次，校验模板可用。
     * 注意只做渲染，不会真的给用户发消息。
     *
     * @param int|string $id
     * @param array      $testData 自定义变量，如 ['order_no' => 'SN123']
     * @return array{title:string,content:string,variables:array}
     */
    public function testTemplate($id, array $testData = []): array
    {
        $template = $this->repository->find((int) $id);
        if (!$template) {
            throw new BusinessException('通知模板不存在', 404);
        }
        if (!(int) $template->is_active) {
            throw new BusinessException('模板已禁用，请先启用');
        }

        $variables = [];
        $rawVars = $template->template_variables ?? '';
        if (is_string($rawVars) && $rawVars !== '') {
            $decoded = json_decode($rawVars, true);
            if (is_array($decoded)) {
                $variables = $decoded;
            }
        }
        // 变量没配值时先给一个占位示例，保证渲染结果可读
        $values = [];
        foreach ($variables as $key => $item) {
            $name = is_string($key) ? $key : (string) $item;
            $values[$name] = is_array($item) ? (string) ($item['example'] ?? $item['value'] ?? "示例{$name}") : "示例{$name}";
        }
        $values = array_merge($values, $testData);

        $title = (string) ($template->template_title ?? '');
        $content = (string) ($template->template_content ?? '');
        foreach ($values as $k => $v) {
            $title = str_replace(['{' . $k . '}', '{{' . $k . '}}'], (string) $v, $title);
            $content = str_replace(['{' . $k . '}', '{{' . $k . '}}'], (string) $v, $content);
        }

        if (trim($content) === '') {
            throw new BusinessException('模板内容为空，无法测试');
        }

        $this->logInfo('测试渲染通知模板', ['id' => $id]);

        return [
            'title' => $title,
            'content' => $content,
            'variables' => $values,
        ];
    }

    /**
     * 删除模板
     */
    public function deleteTemplate($id): bool
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

    /** 模板详情（记录不存在返回 null） */
    public function getTemplateById($id)
    {
        return $this->repository->find($id);
    }

    /** 按模板编码获取模板（记录不存在返回 null） */
    public function getTemplateByCode(string $templateCode)
    {
        return $this->repository->findByCode($templateCode);
    }
}
