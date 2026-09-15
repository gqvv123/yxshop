<?php

namespace app\service;

use app\repository\MiniPageTemplateRepository;
use app\repository\MiniPageRepository;
use app\model\MiniPage;
use app\exception\BusinessException;

class MiniPageTemplateService extends BaseService
{
    protected $pageRepository;

    private const BUILTIN_TEMPLATE_DIR = __DIR__ . '/../../database/templates/';

    public function __construct(?MiniPageTemplateRepository $repository = null)
    {
        $repository = $repository ?? new MiniPageTemplateRepository();
        parent::__construct($repository);
        $this->pageRepository = new MiniPageRepository();
    }

    public function createTemplate(array $data)
    {
        $appId = $data['app_id'] ?? 0;

        if ($this->repository->checkNameExists($appId, $data['template_name'])) {
            throw new BusinessException('模板名称已存在');
        }

        if (!empty($data['page_id'])) {
            $page = $this->pageRepository->findOrFail($data['page_id']);
            $data['template_data'] = $page->page_data;
            unset($data['page_id']);
        }

        return $this->create($data);
    }

    public function getTemplateList($appId)
    {
        return $this->repository->getTemplatesByApp($appId);
    }

    public function createPageFromTemplate($templateId, $pageName, $pageType = MiniPage::TYPE_CUSTOM)
    {
        $template = $this->findOrFail($templateId);

        $pageService = new MiniPageService();
        return $pageService->createPage([
            'page_name' => $pageName,
            'page_type' => $pageType,
            'page_data' => $template->template_data ?? [],
            'app_id' => $template->app_id,
        ]);
    }

    public function deleteTemplate($id)
    {
        return $this->delete($id);
    }

    /**
     * 获取内置文件模板列表
     */
    public function getBuiltinTemplates(): array
    {
        $result = [];
        $dir = self::BUILTIN_TEMPLATE_DIR;
        if (!is_dir($dir)) {
            return $result;
        }

        $files = glob($dir . '*.json');
        foreach ($files as $file) {
            $content = file_get_contents($file);
            $data = json_decode($content, true);
            if (!$data || !isset($data['template_id'])) {
                continue;
            }
            $result[] = [
                'template_id' => $data['template_id'],
                'template_name' => $data['template_name'] ?? basename($file),
                'description' => $data['description'] ?? '',
                'page_type' => $data['page_type'] ?? 'custom',
                'component_count' => isset($data['page_data']) ? count($data['page_data']) : 0,
                'file' => basename($file),
            ];
        }

        return $result;
    }

    /**
     * 读取内置模板完整数据
     */
    public function getBuiltinTemplate(string $templateId): ?array
    {
        $dir = self::BUILTIN_TEMPLATE_DIR;
        $files = glob($dir . '*.json');
        foreach ($files as $file) {
            $content = file_get_contents($file);
            $data = json_decode($content, true);
            if ($data && isset($data['template_id']) && $data['template_id'] === $templateId) {
                return $data;
            }
        }
        return null;
    }

    /**
     * 导入内置模板到数据库
     */
    public function importBuiltinTemplate(string $templateId, int $appId): array
    {
        $data = $this->getBuiltinTemplate($templateId);
        if (!$data) {
            throw new BusinessException('内置模板不存在: ' . $templateId);
        }

        $templateName = $data['template_name'] ?? $templateId;

        if ($this->repository->checkNameExists($appId, $templateName)) {
            throw new BusinessException('模板名称已存在: ' . $templateName);
        }

        $template = $this->create([
            'template_name' => $templateName,
            'template_data' => $data['page_data'] ?? [],
            'thumbnail' => '',
            'app_id' => $appId,
        ]);

        return [
            'template' => $template,
            'source' => $data,
        ];
    }

    /**
     * 规范化页面类型（暴露 Model 静态方法，避免 Controller 直接调用 Model）
     */
    public function normalizePageType($value): ?int
    {
        return MiniPage::normalizeType($value);
    }

    /**
     * 直接从内置模板创建页面
     */
    public function createPageFromBuiltinTemplate(string $templateId, int $appId, string $pageName = '', int $pageType = null): array
    {
        $data = $this->getBuiltinTemplate($templateId);
        if (!$data) {
            throw new BusinessException('内置模板不存在: ' . $templateId);
        }

        if (empty($pageName)) {
            $pageName = $data['template_name'] ?? ('模板页面_' . date('md'));
        }

        if ($pageType === null) {
            $pageType = ($data['page_type'] ?? 'custom') === 'home'
                ? MiniPage::TYPE_HOME
                : MiniPage::TYPE_CUSTOM;
        }

        $pageService = new MiniPageService();
        $page = $pageService->createPage([
            'page_name' => $pageName,
            'page_type' => $pageType,
            'page_data' => $data['page_data'] ?? [],
            'app_id' => $appId,
        ]);

        return [
            'page' => $page,
            'template' => $data,
        ];
    }
}