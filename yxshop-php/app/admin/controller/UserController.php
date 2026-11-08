<?php

namespace app\admin\controller;

use support\Request;
use app\service\UserService;
use app\traits\XlsxExportTrait;
use app\service\UserAddressService;

class UserController extends BaseController
{
    use XlsxExportTrait;

    protected $userService;
    protected $userAddressService;

    public function __construct()
    {
        parent::__construct();
        $this->userService = new UserService();
        $this->userAddressService = new UserAddressService();
    }

    public function index(Request $request)
    {
        $appId = $this->getAppId($request);
        $pageSize = (int) $request->get('page_size', 20);
        $users = $this->userService->getPaginatedListWithRelations($appId, $pageSize);

        return $this->paginate($users);
    }

    public function dashboard(Request $request)
    {
        $appId = $this->getAppId($request);
        $data = $this->userService->getUserDashboard($appId);
        return $this->success($data);
    }

    public function show(Request $request, $id)
    {
        $userData = $this->userService->getDetailWithRelations($id);
        return $this->success($userData);
    }

    public function addresses(Request $request)
    {
        $appId = $this->getAppId($request);
        $pageSize = (int) $request->get('page_size', 20);
        $addresses = $this->userAddressService->getPaginatedList($appId, $pageSize);
        return $this->paginate($addresses);
    }

    /**
     * 导出用户看板数据
     */
    public function exportDashboard(Request $request)
    {
        $appId = $this->getAppId($request);
        $data = $this->userService->getUserDashboard($appId);
        $stats = $data['stats'] ?? $data;

        $rows = [];
        foreach ((array) $stats as $key => $value) {
            if (is_array($value) || is_object($value)) {
                continue;
            }
            $rows[] = [$key, $value];
        }

        return $this->xlsxDownload(['指标', '数值'], $rows, 'user-dashboard.xlsx');
    }
}
