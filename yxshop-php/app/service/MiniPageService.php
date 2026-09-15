<?php

namespace app\service;

use app\repository\MiniPageRepository;
use app\repository\MiniPageVersionRepository;
use app\model\MiniPage;
use app\exception\BusinessException;
use support\Db;

class MiniPageService extends BaseService
{
    protected $versionService;
    protected $schemaService;

    private const ALLOWED_HTML_TAGS = [
        'p', 'br', 'b', 'i', 'u', 'a', 'img', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'ul', 'ol', 'li', 'span', 'div', 'strong', 'em', 'table', 'tr', 'td', 'th',
        'tbody', 'thead', 'blockquote', 'pre', 'code',
    ];

    private const ALLOWED_HTML_ATTRS = [
        'a' => ['href', 'title', 'target'],
        'img' => ['src', 'alt', 'width', 'height'],
        'span' => ['style', 'class'],
        'div' => ['style', 'class'],
        'p' => ['style', 'class'],
        'td' => ['style', 'class', 'colspan', 'rowspan'],
        'th' => ['style', 'class', 'colspan', 'rowspan'],
    ];

    public function __construct(?MiniPageRepository $repository = null)
    {
        $repository = $repository ?? new MiniPageRepository();
        parent::__construct($repository);
        $this->versionService = new MiniPageVersionService();
        $this->schemaService = new ComponentSchemaService();
    }

    public function createPage(array $data)
    {
        $data['status'] = MiniPage::STATUS_DRAFT;
        $data['page_data'] = $data['page_data'] ?? [];
        $data['version'] = 1;
        $data['page_type'] = $data['page_type'] ?? MiniPage::TYPE_HOME;

        if (isset($data['page_data'])) {
            $data['page_data'] = $this->sanitizePageData($data['page_data']);
        }

        return $this->create($data);
    }

    public function updatePage($id, array $data)
    {
        $page = $this->findOrFail($id);

        if ($page->isPublished()) {
            throw new BusinessException('已发布页面不能直接编辑，请先下线');
        }

        if (isset($data['version']) && $data['version'] != $page->version) {
            throw new BusinessException('页面已被他人修改，请刷新后重新编辑', 409);
        }

        $data['version'] = $page->version + 1;

        if (isset($data['page_data'])) {
            $data['page_data'] = $this->sanitizePageData($data['page_data']);
            $this->validatePageDataSize($data['page_data']);
            $this->validatePageDataCount($data['page_data']);
        }

        return $this->update($id, $data);
    }

    public function deletePage($id)
    {
        $page = $this->findOrFail($id);

        if ($page->isPublished()) {
            throw new BusinessException('已发布页面不能删除，请先下线');
        }

        $this->logAudit('mini_page_delete', $page->app_id, [
            'page_id' => $id,
            'page_name' => $page->page_name,
        ]);

        return $this->delete($id);
    }

    public function getPageDetail($id)
    {
        $page = $this->findOrFail($id);
        $page->load('theme');
        return $page;
    }

    public function getPageList($appId, $filters = [], $pageSize = 20)
    {
        $pageType = MiniPage::normalizeType($filters['page_type'] ?? null);
        $status = MiniPage::normalizeStatus($filters['status'] ?? null);

        return $this->repository->getPagesByApp($appId, $pageType, $status, $pageSize);
    }

    /**
     * 获取已发布的首页（C端）
     */
    public function getPublishedHomePage($appId)
    {
        return $this->repository->getPublishedHomePage($appId);
    }

    public function publishPage($id, $publisherId = 0, $summary = '')
    {
        $page = $this->findOrFail($id);

        if ($page->isPublished()) {
            throw new BusinessException('页面已处于发布状态');
        }

        $pageData = $page->page_data ?? [];
        $this->schemaService->validatePageDataForPublish($pageData);

        return Db::transaction(function () use ($page, $publisherId, $summary) {
            if ($page->isHome()) {
                $published = $this->repository->getPublishedHomePage($page->app_id);
                if ($published && $published->id !== $page->id) {
                    $published->status = MiniPage::STATUS_OFFLINE;
                    $published->save();
                }
            }

            $this->versionService->createSnapshot($page->id, $publisherId, $summary);

            $page->status = MiniPage::STATUS_PUBLISHED;
            $page->version = $page->version + 1;
            $page->save();

            $this->clearPageCache($page->app_id);

            $this->logAudit('mini_page_publish', $page->app_id, [
                'page_id' => $page->id,
                'page_name' => $page->page_name,
                'publisher_id' => $publisherId,
                'summary' => $summary,
            ]);

            return $page;
        });
    }

    public function unpublishPage($id)
    {
        $page = $this->findOrFail($id);

        if (!$page->isPublished()) {
            throw new BusinessException('仅已发布页面可以下线');
        }

        $page->status = MiniPage::STATUS_OFFLINE;
        $page->version = $page->version + 1;
        $page->save();

        $this->clearPageCache($page->app_id);

        $this->logAudit('mini_page_unpublish', $page->app_id, [
            'page_id' => $page->id,
            'page_name' => $page->page_name,
        ]);

        return $page;
    }

    protected function sanitizePageData(array $pageData): array
    {
        foreach ($pageData as &$component) {
            if (!isset($component['component_type']) || !isset($component['props'])) {
                continue;
            }

            if ($component['component_type'] === 'rich_text' && isset($component['props']['content'])) {
                $component['props']['content'] = $this->filterXss($component['props']['content']);
            }

            $component['props'] = $this->validateComponentLinks($component['component_type'], $component['props']);
        }
        unset($component);

        return $pageData;
    }

    protected function filterXss(string $html): string
    {
        $html = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/is', '', $html);
        $html = preg_replace('/<iframe\b[^<]*(?:(?!<\/iframe>)<[^<]*)*<\/iframe>/is', '', $html);
        $html = preg_replace('/<object\b[^<]*(?:(?!<\/object>)<[^<]*)*<\/object>/is', '', $html);
        $html = preg_replace('/<embed\b[^>]*>/is', '', $html);
        $html = preg_replace('/\bon\w+\s*=\s*["\'][^"\']*["\']/is', '', $html);
        $html = preg_replace('/\bon\w+\s*=\s*[^\s>]+/is', '', $html);
        $html = preg_replace('/javascript\s*:/is', '', $html);
        $html = preg_replace('/vbscript\s*:/is', '', $html);
        $html = preg_replace('/expression\s*\(/is', '', $html);

        return $html;
    }

    protected function validateComponentLinks(string $componentType, array $props): array
    {
        $linkFields = [];

        switch ($componentType) {
            case 'swiper':
                $linkFields = ['images'];
                break;
            case 'image_ad':
                $linkFields = ['images'];
                break;
            case 'grid_nav':
                $linkFields = ['items'];
                break;
            case 'promo_banner':
                $linkFields = ['btn_link'];
                break;
            case 'promo_grid':
                $linkFields = ['cards'];
                break;
            case 'brand_zone':
                $linkFields = ['items', 'more_link'];
                break;
            case 'product_list':
                $linkFields = ['more_link'];
                break;
        }

        foreach ($linkFields as $field) {
            if (!isset($props[$field]) || !is_array($props[$field])) {
                // promo_banner btn_link and brand_zone more_link are strings, not arrays
                if ($field === 'btn_link' || $field === 'more_link') {
                    if (isset($props[$field]) && is_string($props[$field]) && $props[$field] !== '') {
                        if (!$this->isValidLink($props[$field])) {
                            throw new BusinessException("链接地址不合法: {$props[$field]}，仅允许小程序页面路径(/pages/...)或HTTPS外链");
                        }
                    }
                    continue;
                }
                continue;
            }

            foreach ($props[$field] as &$item) {
                if (isset($item['link']) && $item['link'] !== '') {
                    if (!$this->isValidLink($item['link'])) {
                        throw new BusinessException("组件链接地址不合法: {$item['link']}，仅允许小程序页面路径(/pages/...)或HTTPS外链");
                    }
                }
            }
            unset($item);
        }

        return $props;
    }

    protected function isValidLink(string $link): bool
    {
        if (preg_match('#^/pages/#', $link)) {
            return true;
        }

        if (preg_match('#^https?://#i', $link)) {
            return true;
        }

        return false;
    }

    protected function validatePageDataSize($pageData)
    {
        $jsonSize = strlen(json_encode($pageData));
        if ($jsonSize > 200 * 1024) {
            throw new BusinessException('页面数据体积超过200KB限制');
        }
    }

    protected function validatePageDataCount($pageData)
    {
        if (is_array($pageData) && count($pageData) > 50) {
            throw new BusinessException('组件数量已达上限(50)');
        }
    }

    protected function clearPageCache($appId)
    {
        try {
            $key = "mini_page:home:{$appId}";
            \support\Redis::del($key);
        } catch (\Throwable $e) {
        }
    }

    protected function logAudit(string $action, int $appId, array $details = [])
    {
        // 审计日志（企业版功能，开源版跳过）
        if (!class_exists('\\enterprise\\audit_trail\\Model\\DataChangeLog')) {
            return;
        }

        try {
            $pageId = $details['page_id'] ?? '0';
            $pageName = $details['page_name'] ?? '';
            $reason = $details['summary'] ?? $action;

            $changeTypeMap = [
                'mini_page_publish' => \enterprise\audit_trail\Model\DataChangeLog::CHANGE_TYPE_UPDATE,
                'mini_page_unpublish' => \enterprise\audit_trail\Model\DataChangeLog::CHANGE_TYPE_UPDATE,
                'mini_page_delete' => \enterprise\audit_trail\Model\DataChangeLog::CHANGE_TYPE_DELETE,
            ];

            \enterprise\audit_trail\Model\DataChangeLog::record(
                'yxshop_mini_pages',
                $pageId,
                $changeTypeMap[$action] ?? \enterprise\audit_trail\Model\DataChangeLog::CHANGE_TYPE_UPDATE,
                null,
                null,
                $action,
                $reason,
                $appId
            );
        } catch (\Throwable $e) {
        }
    }
}