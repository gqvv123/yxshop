<?php

namespace app\service;

use app\repository\AppNavbarRepository;
use app\validate\AppNavbarValidate;
use Exception;

/**
 * 小程序页面服务类
 *
 * @property AppNavbarRepository $repository
 */
class AppNavbarService extends BaseService
{
    public function __construct(AppNavbarRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * 获取页面列表
     */
    public function getPageList($appId = 0)
    {
        try {
            $this->logInfo('获取页面列表开始', ['app_id' => $appId]);

            $pages = $this->repository->getPages($appId);

            $this->logInfo('获取页面列表成功', ['app_id' => $appId]);
            return $pages;

        } catch (Exception $e) {
            $this->logError('获取页面列表失败', [
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 获取页面详情
     */
    public function getPageById($id)
    {
        try {
            return $this->repository->findOrFail($id);

        } catch (Exception $e) {
            $this->logError('获取页面详情失败', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 创建页面
     */
    public function createPage(array $data)
    {
        try {
            $this->logInfo('创建页面开始', ['data' => $data]);

            $this->validateWith(AppNavbarValidate::class, 'create', $data);

            $page = $this->repository->create($data);

            $this->logInfo('创建页面成功', ['page_id' => $page->page_id]);
            return $page;

        } catch (Exception $e) {
            $this->logError('创建页面失败', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 更新页面
     */
    public function updatePage($id, array $data)
    {
        try {
            $this->logInfo('更新页面开始', ['id' => $id, 'data' => $data]);

            $this->validateWith(AppNavbarValidate::class, 'update', $data);

            $page = $this->repository->findOrFail($id);

            $page = $this->repository->update($id, $data);

            $this->logInfo('更新页面成功', ['page_id' => $id]);
            return $page;

        } catch (Exception $e) {
            $this->logError('更新页面失败', [
                'id' => $id,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 删除页面
     */
    public function deletePage($id)
    {
        try {
            $this->logInfo('删除页面开始', ['id' => $id]);

            $this->repository->delete($id);

            $this->logInfo('删除页面成功', ['page_id' => $id]);
            return true;

        } catch (Exception $e) {
            $this->logError('删除页面失败', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 更新页面状态
     */
    public function updatePageStatus($id, $status)
    {
        try {
            $this->logInfo('更新页面状态开始', ['id' => $id, 'status' => $status]);

            $page = $this->repository->findOrFail($id);

            $page->status = $status;
            $page->save();

            $this->logInfo('更新页面状态成功', ['id' => $id, 'status' => $status]);
            return $page;

        } catch (Exception $e) {
            $this->logError('更新页面状态失败', [
                'id' => $id,
                'status' => $status,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 获取页面统计
     */
    public function getPageStats($appId = 0)
    {
        try {
            return $this->repository->getPageStats((int) $appId);

        } catch (Exception $e) {
            $this->logError('获取页面统计失败', [
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
