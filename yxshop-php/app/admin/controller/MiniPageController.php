<?php

namespace app\admin\controller;

use support\Request;
use app\service\MiniPageService;
use app\service\MiniPageVersionService;
use app\service\ComponentSchemaService;

class MiniPageController extends BaseController
{
    protected $pageService;
    protected $versionService;
    protected $schemaService;

    public function __construct()
    {
        parent::__construct();
        $this->pageService = new MiniPageService();
        $this->versionService = new MiniPageVersionService();
        $this->schemaService = new ComponentSchemaService();
    }

    public function index(Request $request)
    {
        $appId = $this->getAppId($request);
        $filters = [
            'page_type' => $request->input('page_type'),
            'status' => $request->input('status'),
        ];
        $pageSize = (int) $request->input('page_size', 20);
        $result = $this->pageService->getPageList($appId, $filters, $pageSize);
        return $this->paginate($result);
    }

    public function show(Request $request, $id)
    {
        $page = $this->pageService->getPageDetail($id);
        return $this->success($page);
    }

    public function store(Request $request)
    {
        $data = $request->post();
        $data['app_id'] = $this->getAppId($request);
        $page = $this->pageService->createPage($data);
        return $this->success($page, '创建成功');
    }

    public function update(Request $request, $id)
    {
        $data = $request->post();
        try {
            $page = $this->pageService->updatePage($id, $data);
            return $this->success($page, '更新成功');
        } catch (\app\exception\BusinessException $e) {
            if ($e->getCode() === 409) {
                return $this->error($e->getMessage(), 409);
            }
            throw $e;
        }
    }

    public function destroy(Request $request, $id)
    {
        $this->pageService->deletePage($id);
        return $this->success(null, '删除成功');
    }

    public function publish(Request $request, $id)
    {
        $admin = $request->admin ?? [];
        $summary = $request->post('summary', '');
        $page = $this->pageService->publishPage($id, $admin['id'] ?? 0, $summary);
        return $this->success($page, '发布成功');
    }

    public function unpublish(Request $request, $id)
    {
        $page = $this->pageService->unpublishPage($id);
        return $this->success($page, '下线成功');
    }

    public function componentSchemas(Request $request)
    {
        $edition = $request->input('edition', 'community');
        $schemas = $this->schemaService->getSchemas($edition);
        return $this->success($schemas);
    }
}