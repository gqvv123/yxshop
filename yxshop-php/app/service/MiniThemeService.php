<?php

namespace app\service;

use app\repository\MiniThemeRepository;
use app\repository\MiniPageRepository;
use app\model\MiniPage;
use app\exception\BusinessException;

class MiniThemeService extends BaseService
{
    protected $pageRepository;

    public function __construct(?MiniThemeRepository $repository = null)
    {
        $repository = $repository ?? new MiniThemeRepository();
        parent::__construct($repository);
        $this->pageRepository = new MiniPageRepository();
    }

    public function createTheme(array $data)
    {
        $this->validateHexColors($data);

        return $this->create($data);
    }

    public function updateTheme($id, array $data)
    {
        $this->validateHexColors($data);

        return $this->update($id, $data);
    }

    public function deleteTheme($id)
    {
        $theme = $this->findOrFail($id);

        $publishedPages = $this->pageRepository->query()
            ->where('theme_id', $id)
            ->where('status', MiniPage::STATUS_PUBLISHED)
            ->exists();

        if ($publishedPages) {
            throw new BusinessException('该配色方案已被已发布页面引用，不能删除');
        }

        return $this->delete($id);
    }

    public function getThemeList($appId)
    {
        return $this->repository->getThemesByApp($appId);
    }

    public function applyTheme($themeId, $pageId)
    {
        $theme = $this->findOrFail($themeId);
        $page = $this->pageRepository->findOrFail($pageId);

        if ($theme->app_id !== $page->app_id) {
            throw new BusinessException('配色方案与页面不属于同一应用');
        }

        $page->theme_id = $themeId;
        $page->version = $page->version + 1;
        $page->save();

        return $page;
    }

    protected function validateHexColors(array $data)
    {
        $hexFields = ['primary_color', 'secondary_color', 'nav_background_color', 'nav_text_color'];

        foreach ($hexFields as $field) {
            if (isset($data[$field]) && !preg_match('/^#[0-9A-Fa-f]{6}$/', $data[$field])) {
                throw new BusinessException("{$field} 不是有效的HEX颜色值");
            }
        }
    }
}