<?php

namespace app\service;

use app\repository\FileTypeRepository;
use app\exception\BusinessException;
use Exception;

class FileTypeService extends BaseService
{
    public function __construct(FileTypeRepository $repository)
    {
        parent::__construct($repository);
    }

    public function getTypeList($appId = 0)
    {
        try {
            $this->logInfo('获取文件类型列表开始', ['app_id' => $appId]);

            $types = $this->repository->listTypes((int) $appId);

            $this->logInfo('获取文件类型列表成功', ['app_id' => $appId]);
            return $types;

        } catch (Exception $e) {
            $this->logError('获取文件类型列表失败', [
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function createType(array $data)
    {
        try {
            $this->logInfo('创建文件类型开始', ['data' => $data]);



            $existing = $this->repository->findByCode((string) $data['code'], (int) $data['app_id']);

            if ($existing) {
                throw new BusinessException('文件类型编码已存在');
            }

            $type = $this->repository->create($data);

            $this->logInfo('创建文件类型成功', ['type_id' => $type->id]);
            return $type;

        } catch (Exception $e) {
            $this->logError('创建文件类型失败', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function updateType($id, array $data)
    {
        try {
            $this->logInfo('更新文件类型开始', ['id' => $id, 'data' => $data]);

            $type = $this->repository->findOrFail($id);

            if (isset($data['code'])) {
                $existing = $this->repository->findByCode((string) $data['code'], (int) $type->app_id, (int) $id);

                if ($existing) {
                    throw new BusinessException('文件类型编码已存在');
                }
            }

            $type = $this->repository->update($id, $data);

            $this->logInfo('更新文件类型成功', ['type_id' => $id]);
            return $type;

        } catch (Exception $e) {
            $this->logError('更新文件类型失败', [
                'id' => $id,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function deleteType($id)
    {
        try {
            $this->logInfo('删除文件类型开始', ['id' => $id]);

            $this->repository->findOrFail($id);
            $this->repository->delete($id);

            $this->logInfo('删除文件类型成功', ['type_id' => $id]);
            return true;

        } catch (Exception $e) {
            $this->logError('删除文件类型失败', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
