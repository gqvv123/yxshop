<?php

namespace app\traits;

use support\Request;
use app\exception\BusinessException;

trait BatchOperationTrait
{
    protected function getBatchServiceProperty(): string
    {
        if (property_exists($this, 'batchServiceName') && !empty($this->batchServiceName)) {
            return $this->batchServiceName;
        }

        $className = (new \ReflectionClass($this))->getShortName();
        $serviceName = str_replace('Controller', '', $className);
        $propertyName = lcfirst($serviceName) . 'Service';

        if (property_exists($this, $propertyName)) {
            return $propertyName;
        }

        return '';
    }

    public function batchUpdateStatus(Request $request)
    {
        $ids = (array) $request->post('ids', []);
        $status = (int) $request->post('status', 0);

        if (empty($ids)) {
            return $this->batchRespondError('缺少ids参数');
        }

        $serviceProp = $this->getBatchServiceProperty();

        if (!empty($serviceProp) && property_exists($this, $serviceProp)) {
            $service = $this->$serviceProp;
            if (method_exists($service, 'getRepository')) {
                $idField = $this->getBatchIdField();
                $updated = $service->getRepository()->updateWhere([$idField => $ids], ['status' => $status]);
                return $this->batchRespondSuccess($updated ? '批量更新成功' : '没有记录被更新');
            }
        }

        if (property_exists($this, 'batchModelClass') && !empty($this->batchModelClass)) {
            $updated = ($this->batchModelClass)::whereIn('id', $ids)->update(['status' => $status]);
            return $this->batchRespondSuccess($updated > 0 ? '批量更新成功' : '没有记录被更新');
        }

        throw new BusinessException('控制器未定义 batchServiceName 或 batchModelClass');
    }

    public function batchDelete(Request $request)
    {
        $ids = (array) $request->post('ids', []);

        if (empty($ids)) {
            return $this->batchRespondError('缺少ids参数');
        }

        $softDelete = property_exists($this, 'batchSoftDelete') ? $this->batchSoftDelete : true;
        $serviceProp = $this->getBatchServiceProperty();

        if (!empty($serviceProp) && property_exists($this, $serviceProp)) {
            $service = $this->$serviceProp;
            if (method_exists($service, 'getRepository')) {
                if ($softDelete) {
                    $deleted = $service->getRepository()->updateWhere(['id' => $ids], ['deleted_at' => time()]);
                } else {
                    $deleted = $service->getRepository()->deleteWhere(['id' => $ids]);
                }
                return $this->batchRespondSuccess($deleted ? '批量删除成功' : '没有记录被删除');
            }
        }

        if (property_exists($this, 'batchModelClass') && !empty($this->batchModelClass)) {
            if ($softDelete) {
                $deleted = ($this->batchModelClass)::whereIn('id', $ids)->update(['deleted_at' => time()]);
            } else {
                $deleted = ($this->batchModelClass)::whereIn('id', $ids)->delete();
            }
            return $this->batchRespondSuccess($deleted > 0 ? '批量删除成功' : '没有记录被删除');
        }

        throw new BusinessException('控制器未定义 batchServiceName 或 batchModelClass');
    }

    protected function getBatchIdField(): string
    {
        return property_exists($this, 'batchIdField') ? $this->batchIdField : 'id';
    }

    protected function batchRespondSuccess(string $message)
    {
        if (method_exists($this, 'renderSuccess')) {
            return $this->renderSuccess($message);
        }
        if (method_exists($this, 'success')) {
            return $this->success(null, $message);
        }
        return json(['code' => 1, 'msg' => $message]);
    }

    protected function batchRespondError(string $message)
    {
        if (method_exists($this, 'renderError')) {
            return $this->renderError($message);
        }
        if (method_exists($this, 'error')) {
            return $this->error($message);
        }
        return json(['code' => 0, 'msg' => $message]);
    }
}
