<?php

namespace app\service;

use app\repository\ScheduledTaskRepository;
use app\model\ScheduledTask;
use app\exception\BusinessException;
use Exception;

/**
 * 定时任务服务类
 *
 * @property ScheduledTaskRepository $repository
 */
class ScheduledTaskService extends BaseService
{
    /**
     * 允许在后台手动执行的命令白名单。
     * 命令串来自数据库，必须限制在白名单内，避免被写入任意 shell 命令。
     */
    private const EXECUTABLE_COMMANDS = [
        'log:cleanup' => \app\command\LogCleanupCommand::class,
    ];
    public function __construct(?ScheduledTaskRepository $repository = null)
    {
        parent::__construct($repository ?? new ScheduledTaskRepository());
    }

    public function getEnabled(int $appId = 0)
    {
        try {
            return $this->repository->getEnabled($appId);
        } catch (Exception $e) {
            $this->logError('获取已启用任务失败', [
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getByType(string $taskType, int $appId = 0)
    {
        try {
            return $this->repository->getByType($taskType, $appId);
        } catch (Exception $e) {
            $this->logError('按类型获取任务失败', [
                'task_type' => $taskType,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getPaginatedList(int $appId = 0, array $filters = [], int $pageSize = 20)
    {
        try {
            return $this->repository->getPaginatedList($appId, $filters, $pageSize);
        } catch (Exception $e) {
            $this->logError('获取任务分页列表失败', [
                'app_id' => $appId,
                'filters' => $filters,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function updateLastRun(int $id, int $status)
    {
        try {
            $this->logInfo('更新任务最后运行状态开始', ['id' => $id, 'status' => $status]);
            $task = $this->repository->updateLastRun($id, $status);
            $this->logInfo('更新任务最后运行状态成功', ['id' => $id]);
            return $task;
        } catch (Exception $e) {
            $this->logError('更新任务最后运行状态失败', [
                'id' => $id,
                'status' => $status,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getStats(array $conditions = []): array
    {
        try {
            return $this->repository->getStats($conditions);
        } catch (Exception $e) {
            $this->logError('获取任务统计失败', [
                'app_id' => $conditions['app_id'] ?? 0,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function enable(int $id)
    {
        try {
            $this->logInfo('启用任务开始', ['id' => $id]);
            $task = $this->repository->findOrFail($id);
            $task->status = ScheduledTask::STATUS_ENABLED;
            $task->save();
            $this->logInfo('启用任务成功', ['id' => $id]);
            return $task;
        } catch (Exception $e) {
            $this->logError('启用任务失败', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function disable(int $id)
    {
        try {
            $this->logInfo('禁用任务开始', ['id' => $id]);
            $task = $this->repository->findOrFail($id);
            $task->status = ScheduledTask::STATUS_DISABLED;
            $task->save();
            $this->logInfo('禁用任务成功', ['id' => $id]);
            return $task;
        } catch (Exception $e) {
            $this->logError('禁用任务失败', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function create(array $data)
    {
        try {
            $name = trim((string) ($data['task_name'] ?? ''));
            $type = trim((string) ($data['task_type'] ?? ''));
            $command = trim((string) ($data['task_command'] ?? ''));
            if ($name === '') {
                throw new BusinessException('请填写任务名称');
            }
            if ($type === '') {
                throw new BusinessException('请选择任务类型');
            }
            if ($command === '') {
                throw new BusinessException('请填写任务命令');
            }
            $data['task_name'] = $name;
            $data['task_type'] = $type;
            $data['task_command'] = $command;
            $data['cron_expression'] = trim((string) ($data['cron_expression'] ?? '')) ?: '* * * * *';
            $data['status'] = isset($data['status']) ? (int) $data['status'] : ScheduledTask::STATUS_ENABLED;
            $data['app_id'] = (int) ($data['app_id'] ?? 0);

            $this->logInfo('创建任务开始', ['data' => $data]);
            $task = $this->repository->create($data);
            $this->logInfo('创建任务成功', ['id' => $task->id]);
            return $task;
        } catch (BusinessException $e) {
            // throw $e;
        } catch (Exception $e) {
            $this->logError('创建任务失败', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 后台手动执行一次任务。
     * 只允许执行内置白名单里的命令，避免把数据库中的任意字符串当 shell 命令执行。
     *
     * @param int|string $id
     * @return array{command:string,success:bool,output:string}
     */
    public function execute($id): array
    {
        $task = $this->repository->find((int) $id);
        if (!$task) {
            throw new BusinessException('定时任务不存在', 404);
        }
        if ((int) $task->status !== ScheduledTask::STATUS_ENABLED) {
            throw new BusinessException('任务已禁用，请先启用再执行');
        }

        $command = $this->parseCommandName((string) $task->task_command);
        $map = self::EXECUTABLE_COMMANDS;
        if (!isset($map[$command])) {
            throw new BusinessException("任务命令「{$command}」不在可执行白名单中，无法在后台执行");
        }

        $success = false;
        $output = '';
        try {
            $application = new \Symfony\Component\Console\Application();
            $application->setAutoExit(false);
            $application->add(new $map[$command]());
            $output = new \Symfony\Component\Console\Output\BufferedOutput();
            $exitCode = $application->run(
                new \Symfony\Component\Console\Input\ArrayInput(['command' => $command]),
                $output
            );
            $success = $exitCode === 0;
        } catch (\Throwable $e) {
            // $this->logError('执行定时任务失败', ['id' => $id, 'command' => $command, 'error' => $e->getMessage()]);
            $this->updateLastRun((int) $id, 0);
            throw new BusinessException('任务执行失败：' . $e->getMessage());
        }

        $this->updateLastRun((int) $id, $success ? 1 : 0);
        $this->logInfo('执行定时任务完成', ['id' => $id, 'command' => $command, 'success' => $success]);

        return [
            'command' => $command,
            'success' => $success,
            'output' => trim($output->fetch()),
        ];
    }

    /**
     * 从 "php webman log:cleanup" 这类命令串里取出命令名
     */
    private function parseCommandName(string $command): string
    {
        $command = trim($command);
        if ($command === '') {
            return '';
        }
        if (preg_match('/webman\s+([^\s]+)/', $command, $m)) {
            return $m[1];
        }
        $parts = preg_split('/\s+/', $command);
        return (string) end($parts);
    }

    public function update($id, array $data)
    {
        try {
            $this->logInfo('更新任务开始', ['id' => $id, 'data' => $data]);
            $task = $this->repository->update($id, $data);
            $this->logInfo('更新任务成功', ['id' => $id]);
            return $task;
        } catch (Exception $e) {
            $this->logError('更新任务失败', [
                'id' => $id,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
