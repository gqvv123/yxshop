<?php

namespace app\service;

use app\repository\NotificationTemplateRepository;
use app\repository\NotificationVariableRepository;
use app\repository\NotificationSceneRepository;
use app\repository\NotificationSceneTemplateRepository;
use Exception;

class NotificationEngineService
{
    protected NotificationTemplateRepository $templateRepo;
    protected NotificationVariableRepository $variableRepo;
    protected NotificationSceneRepository $sceneRepo;
    protected NotificationSceneTemplateRepository $sceneTemplateRepo;

    public function __construct()
    {
        $this->templateRepo = new NotificationTemplateRepository();
        $this->variableRepo = new NotificationVariableRepository();
        $this->sceneRepo = new NotificationSceneRepository();
        $this->sceneTemplateRepo = new NotificationSceneTemplateRepository();
    }

    public function renderTemplate($templateId, array $variables = [])
    {
        $template = $this->templateRepo->find($templateId);
        if (!$template) {
            throw new Exception('通知模板不存在');
        }

        $allVariables = $this->variableRepo->query()
            ->where('app_id', $template->app_id)->get();
        $varMap = [];
        foreach ($allVariables as $var) {
            $varMap[$var->variable_code] = $variables[$var->variable_code] ?? ('{{' . $var->variable_code . '}}');
        }

        $title = $template->template_title;
        $content = $template->template_content;

        foreach ($varMap as $code => $value) {
            $title = str_replace('{{' . $code . '}}', $value, $title);
            $content = str_replace('{{' . $code . '}}', $value, $content);
        }

        return [
            'title' => $title,
            'content' => $content,
            'template_type' => $template->template_type,
        ];
    }

    public function validateVariables($templateId, array $variables = [])
    {
        $template = $this->templateRepo->find($templateId);
        if (!$template) {
            throw new Exception('通知模板不存在');
        }

        preg_match_all('/\{\{(\w+)\}\}/', $template->template_content . $template->template_title, $matches);
        $usedCodes = array_unique($matches[1] ?? []);

        $definedCodes = $this->variableRepo->query()
            ->where('app_id', $template->app_id)
            ->pluck('variable_code')
            ->toArray();

        $missing = array_diff($usedCodes, $definedCodes);
        $undefined = array_diff(array_keys($variables), $definedCodes);

        return [
            'used_codes' => $usedCodes,
            'defined_codes' => $definedCodes,
            'missing_codes' => array_values($missing),
            'undefined_input' => array_values($undefined),
            'is_valid' => empty($missing),
        ];
    }

    public function renderBySceneCode($sceneCode, array $variables = [])
    {
        $scene = $this->sceneRepo->query()
            ->where('scene_code', $sceneCode)->first();
        if (!$scene) {
            throw new Exception('通知场景不存在: ' . $sceneCode);
        }

        $sceneTemplate = $this->sceneTemplateRepo->query()
            ->where('scene_id', $scene->id)
            ->where('is_active', 1)
            ->first();
        if (!$sceneTemplate) {
            throw new Exception('场景未关联活跃模板: ' . $sceneCode);
        }

        return $this->renderTemplate($sceneTemplate->template_id, $variables);
    }
}
