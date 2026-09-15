<?php

namespace app\service;

use app\repository\ThemeRepository;
use app\exception\BusinessException;
use app\exception\NotFoundException;

/**
 * 前台主题服务
 *
 * 4 层架构：所有数据访问通过 ThemeRepository
 *
 * @property ThemeRepository $repository
 */
class ThemeService extends BaseService
{
    public function __construct()
    {
        parent::__construct(new ThemeRepository());
    }

    public function getList(): array
    {
        return $this->repository->getActiveList()->toArray();
    }

    public function getCurrent(int $appId)
    {
        return $this->repository->getCurrentActive($appId);
    }

    public function apply(int $themeId, int $appId)
    {
        $theme = $this->repository->findActive($themeId);
        if (!$theme) {
            throw new NotFoundException('主题不存在');
        }

        $this->transaction(function () use ($themeId, $appId) {
            $this->repository->activate($themeId, $appId);
        });

        return true;
    }

    public function custom(array $data, int $appId)
    {
        $themeId = $data['theme_id'] ?? 0;

        $updateData = [];
        if (isset($data['primary_color'])) $updateData['primary_color'] = $data['primary_color'];
        if (isset($data['secondary_color'])) $updateData['secondary_color'] = $data['secondary_color'];
        if (isset($data['font_family'])) $updateData['font_family'] = $data['font_family'];
        if (isset($data['custom_css'])) $updateData['custom_css'] = $data['custom_css'];

        return $this->repository->updateCustom($themeId, $appId, $updateData);
    }

    public function delete(int $themeId)
    {
        return $this->repository->softDelete($themeId);
    }
}
