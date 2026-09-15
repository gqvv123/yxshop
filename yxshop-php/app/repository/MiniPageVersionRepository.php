<?php

namespace app\repository;

use app\model\MiniPageVersion;

class MiniPageVersionRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new MiniPageVersion());
    }

    public function getByPageId($pageId, $page = 1, $pageSize = 10)
    {
        return $this->query()
            ->where('page_id', $pageId)
            ->orderBy('version_number', 'desc')
            ->paginate($pageSize, ['*'], 'page', $page);
    }

    public function getLatestVersionNumber($pageId)
    {
        $result = $this->query()
            ->where('page_id', $pageId)
            ->max('version_number');

        return $result ?? 0;
    }

    public function cleanOldVersions($pageId, $maxCount = 20)
    {
        $count = $this->query()->where('page_id', $pageId)->count();

        if ($count <= $maxCount) {
            return 0;
        }

        $deleteCount = $count - $maxCount;
        $idsToDelete = $this->query()
            ->where('page_id', $pageId)
            ->orderBy('version_number', 'asc')
            ->limit($deleteCount)
            ->pluck('id')
            ->toArray();

        return $this->query()->whereIn('id', $idsToDelete)->delete();
    }
}