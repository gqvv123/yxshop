<?php

namespace app\service;

use app\repository\FileGroupRepository;
use app\exception\BusinessException;
use app\validate\UploadGroupValidate;
use Exception;

class FileGroupService extends BaseService
{
    public function __construct(FileGroupRepository $repository)
    {
        parent::__construct($repository);
    }

    public function getGroupList($appId = 0)
    {
        try {
            $this->logInfo('获取文件分组列表开始', ['app_id' => $appId]);

            $groups = $this->repository->listGroups((int) $appId);

            $this->logInfo('获取文件分组列表成功', ['app_id' => $appId]);
            return $groups;

        } catch (Exception $e) {
            $this->logError('获取文件分组列表失败', [
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function createGroup(array $data)
    {
        try {
            $this->logInfo('创建文件分组开始', ['data' => $data]);

            $this->validateWith(UploadGroupValidate::class, 'create', $data);

            $group = $this->repository->create($data);

            $this->logInfo('创建文件分组成功', ['group_id' => $group->id]);
            return $group;

        } catch (Exception $e) {
            $this->logError('创建文件分组失败', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function updateGroup($id, array $data)
    {
        try {
            $this->logInfo('更新文件分组开始', ['id' => $id, 'data' => $data]);

            $this->validateWith(UploadGroupValidate::class, 'update', $data);

            $this->repository->findOrFail($id);

            $group = $this->repository->update($id, $data);

            $this->logInfo('更新文件分组成功', ['group_id' => $id]);
            return $group;

        } catch (Exception $e) {
            $this->logError('更新文件分组失败', [
                'id' => $id,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function deleteGroup($id)
    {
        try {
            $this->logInfo('删除文件分组开始', ['id' => $id]);

            $group = $this->repository->findOrFail($id);

            if ($group->files()->count() > 0) {
                throw new BusinessException('该分组下有文件，无法删除');
            }

            $this->repository->delete($id);

            $this->logInfo('删除文件分组成功', ['group_id' => $id]);
            return true;

        } catch (Exception $e) {
            $this->logError('删除文件分组失败', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getGroupWithFiles($groupId)
    {
        try {
            $this->logInfo('获取文件分组及文件列表开始', ['group_id' => $groupId]);

            $group = $this->repository->findOrFail($groupId);

            $result = [
                'group' => $group,
                'files' => $group->files()->orderBy('sort', 'asc')->get(),
            ];

            $this->logInfo('获取文件分组及文件列表成功', ['group_id' => $groupId]);
            return $result;

        } catch (Exception $e) {
            $this->logError('获取文件分组及文件列表失败', [
                'group_id' => $groupId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
