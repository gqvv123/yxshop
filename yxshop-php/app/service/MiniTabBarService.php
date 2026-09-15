<?php

namespace app\service;

use app\repository\MiniTabBarRepository;
use app\exception\BusinessException;

class MiniTabBarService extends BaseService
{
    public function __construct(?MiniTabBarRepository $repository = null)
    {
        $repository = $repository ?? new MiniTabBarRepository();
        parent::__construct($repository);
    }

    public function getTabBar($appId)
    {
        return $this->repository->getByApp($appId);
    }

    public function saveTabBar($appId, array $data)
    {
        $this->validateTabBarData($data);

        $existing = $this->repository->getByApp($appId);

        if ($existing) {
            return $this->update($existing->id, $data);
        }

        $data['app_id'] = $appId;
        return $this->create($data);
    }

    protected function validateTabBarData(array $data)
    {
        $items = $data['items'] ?? [];

        if (count($items) < 2) {
            throw new BusinessException('导航项至少需要2个');
        }

        if (count($items) > 5) {
            throw new BusinessException('导航项最多5个');
        }

        foreach ($items as $index => $item) {
            if (empty($item['text'])) {
                throw new BusinessException("第 " . ($index + 1) . " 个导航项缺少文字");
            }
            if (empty($item['page_path'])) {
                throw new BusinessException("第 " . ($index + 1) . " 个导航项缺少页面路径");
            }
            // icon_path / selected_icon_path 可以为空，为空时前端使用内置矢量图标
        }

        if (!empty($items[0]['page_path']) && !str_starts_with($items[0]['page_path'], '/pages/home')) {
            throw new BusinessException('第一个导航项必须指向首页路径');
        }
    }
}