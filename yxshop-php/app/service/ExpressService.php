<?php

namespace app\service;

use app\repository\ExpressRepository;
use app\model\Express;
use app\exception\BusinessException;
use app\validate\ExpressValidate;
use Exception;

/**
 * 快递公司服务类
 *
 * @property ExpressRepository $repository
 */
class ExpressService extends BaseService
{
    public function __construct(?ExpressRepository $repository = null)
    {
        parent::__construct($repository ?? new ExpressRepository());
    }

    /**
     * 获取快递公司列表
     */
    public function getExpressList($appId = 0)
    {
        try {
            $this->logInfo('获取快递公司列表开始', ['app_id' => $appId]);

            $expresses = $this->repository->getExpresses($appId);

            $this->logInfo('获取快递公司列表成功', ['app_id' => $appId]);
            return $expresses;

        } catch (Exception $e) {
            $this->logError('获取快递公司列表失败', [
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 创建快递公司
     */
    public function createExpress(array $data)
    {
        try {
            $this->logInfo('创建快递公司开始', ['data' => $data]);

            $this->validateWith(ExpressValidate::class, 'create', $data);

            // 检查代码是否重复
            $existing = $this->repository->findByCode($data['code'], $data['app_id']);
            if ($existing) {
                throw new BusinessException('快递公司代码已存在');
            }

            $express = $this->repository->create($data);

            $this->logInfo('创建快递公司成功', ['express_id' => $express->id]);
            return $express;

        } catch (Exception $e) {
            $this->logError('创建快递公司失败', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 更新快递公司
     */
    public function updateExpress($id, array $data)
    {
        try {
            $this->logInfo('更新快递公司开始', ['id' => $id, 'data' => $data]);

            $this->validateWith(ExpressValidate::class, 'update', $data);

            $express = $this->repository->findOrFail($id);

            // 检查代码是否重复
            if (isset($data['code'])) {
                $existing = $this->repository->findByCode($data['code'], $express->app_id);
                if ($existing && $existing->id != $id) {
                    throw new BusinessException('快递公司代码已存在');
                }
            }

            $express = $this->repository->update($id, $data);

            $this->logInfo('更新快递公司成功', ['express_id' => $id]);
            return $express;

        } catch (Exception $e) {
            $this->logError('更新快递公司失败', [
                'id' => $id,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 删除快递公司
     */
    public function deleteExpress($id)
    {
        try {
            $this->logInfo('删除快递公司开始', ['id' => $id]);

            $express = $this->repository->findOrFail($id);

            // 检查是否有订单使用
            if ($express->orders()->count() > 0) {
                throw new BusinessException('该快递公司有订单使用，无法删除');
            }

            $this->repository->delete($id);

            $this->logInfo('删除快递公司成功', ['express_id' => $id]);
            return true;

        } catch (Exception $e) {
            $this->logError('删除快递公司失败', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 根据代码查找快递公司
     */
    public function getExpressByCode($code, $appId = 0)
    {
        try {
            return $this->repository->findByCode($code, $appId);

        } catch (Exception $e) {
            $this->logError('根据代码查找快递公司失败', [
                'code' => $code,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 搜索快递公司
     */
    public function searchExpresses($keyword, $appId = 0)
    {
        try {
            $this->logInfo('搜索快递公司开始', ['keyword' => $keyword, 'app_id' => $appId]);

            $expresses = $this->repository->searchExpresses($keyword, $appId);

            $this->logInfo('搜索快递公司成功', ['keyword' => $keyword]);
            return $expresses;

        } catch (Exception $e) {
            $this->logError('搜索快递公司失败', [
                'keyword' => $keyword,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 获取快递公司统计
     */
    public function getExpressStats($appId = 0)
    {
        try {
            return $this->repository->getExpressStats($appId);

        } catch (Exception $e) {
            $this->logError('获取快递公司统计失败', [
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
