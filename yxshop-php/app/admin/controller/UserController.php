<?php

namespace app\admin\controller;

use support\Request;
use app\service\UserService;
use app\service\UserAddressService;

class UserController extends BaseController
{
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
}
