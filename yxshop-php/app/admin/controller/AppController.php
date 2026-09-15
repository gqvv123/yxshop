<?php

namespace app\admin\controller;

use support\Request;
use app\service\AppService;
use app\validate\AppValidate;
use app\exception\ValidationException;

class AppController extends BaseController
{
    protected $appService;

    public function __construct()
    {
        parent::__construct();
        $this->appService = new AppService();
    }

    public function index(Request $request)
    {
        $appId = $this->getAppId($request);
        $apps = $this->appService->getAppList($appId);
        return $this->success($apps);
    }

    public function show(Request $request, $id)
    {
        $app = $this->appService->findById($id);
        if (!$app) {
            return $this->errorNotFound('应用不存在');
        }
        return $this->success($app);
    }

    public function store(Request $request)
    {
        $data = $request->post();

        $validate = new AppValidate();
        $validate->failException(false);
        if (!$validate->scene('create')->check($data)) {
            throw new ValidationException($validate->getError());
        }

        $result = $this->appService->createApp($data);
        if (!$result) {
            return $this->error('添加失败');
        }
        return $this->success($result, '添加成功');
    }

    public function update(Request $request, $id)
    {
        $data = $request->post();

        $validate = new AppValidate();
        $validate->failException(false);
        if (!$validate->scene('update')->check($data)) {
            throw new ValidationException($validate->getError());
        }

        $result = $this->appService->updateApp($id, $data);
        if (!$result) {
            return $this->error('更新失败');
        }
        return $this->success($result, '更新成功');
    }

    public function destroy(Request $request, $id)
    {
        $result = $this->appService->deleteApp($id);
        if (!$result) {
            return $this->error('删除失败');
        }
        return $this->success(null, '删除成功');
    }

    public function updateStatus(Request $request, $id)
    {
        $status = $request->post('status');
        $result = $this->appService->updateApp($id, ['status' => (int)$status]);
        if (!$result) {
            return $this->error('更新失败');
        }
        return $this->success(null, '更新成功');
    }

    public function resetAppKey(Request $request, $id)
    {
        $newKey = random_string(32);
        $newSecret = random_string(48);
        $result = $this->appService->updateApp($id, [
            'appkey' => $newKey,
            'appsecret' => $newSecret
        ]);
        if (!$result) {
            return $this->error('重置失败');
        }
        return $this->success(['appkey' => $newKey, 'appsecret' => $newSecret], '重置成功');
    }
}
