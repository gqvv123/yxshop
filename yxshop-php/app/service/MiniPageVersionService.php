<?php

namespace app\service;

use app\repository\MiniPageVersionRepository;
use app\model\MiniPage;
use app\exception\BusinessException;
use support\Db;

class MiniPageVersionService extends BaseService
{
    public function __construct(?MiniPageVersionRepository $repository = null)
    {
        $repository = $repository ?? new MiniPageVersionRepository();
        parent::__construct($repository);
    }

    public function createSnapshot($pageId, $publisherId, $summary = '')
    {
        $page = (new \app\repository\MiniPageRepository())->findOrFail($pageId);

        return Db::transaction(function () use ($pageId, $page, $publisherId, $summary) {
            $latestVersion = $this->repository->getLatestVersionNumber($pageId);

            $version = $this->create([
                'page_id' => $pageId,
                'version_number' => $latestVersion + 1,
                'page_data' => $page->page_data,
                'summary' => $summary,
                'publish_at' => time(),
                'publisher_id' => $publisherId,
                'app_id' => $page->app_id,
            ]);

            $this->repository->cleanOldVersions($pageId, 20);

            return $version;
        });
    }

    public function getVersionList($pageId, $page = 1, $pageSize = 10)
    {
        return $this->repository->getByPageId($pageId, $page, $pageSize);
    }

    public function getVersionDetail($pageId, $versionId)
    {
        $version = $this->findOrFail($versionId);

        if ($version->page_id !== $pageId) {
            throw new BusinessException('版本不属于该页面');
        }

        return $version;
    }

    public function rollbackVersion($pageId, $versionId)
    {
        $version = $this->getVersionDetail($pageId, $versionId);
        $pageRepo = new \app\repository\MiniPageRepository();
        $page = $pageRepo->findOrFail($pageId);

        if ($page->isPublished()) {
            throw new BusinessException('已发布页面不能直接回滚，请先下线');
        }

        $page->page_data = $version->page_data;
        $page->status = MiniPage::STATUS_DRAFT;
        $page->version = $page->version + 1;
        $page->save();

        return $page;
    }
}