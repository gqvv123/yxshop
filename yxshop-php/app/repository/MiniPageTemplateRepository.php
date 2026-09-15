<?php

namespace app\repository;

use app\model\MiniPageTemplate;

class MiniPageTemplateRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new MiniPageTemplate());
    }

    public function getTemplatesByApp($appId)
    {
        return $this->query()
            ->where('app_id', $appId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function checkNameExists($appId, $name, $excludeId = null)
    {
        $query = $this->query()
            ->where('app_id', $appId)
            ->where('template_name', $name);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}