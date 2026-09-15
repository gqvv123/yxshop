<?php

namespace app\service;

use app\repository\CronRepository;

/**
 * 定时任务服务类
 *
 * @property CronRepository $repository
 */
class CronService
{
    protected $cronRepo;
    
    public function __construct()
    {
        $this->cronRepo = new CronRepository();
    }
    
    /**
     * 获取定时任务列表
     */
    public function getCronList(int $restaurantId, array $params = [], int $page = 1, int $pageSize = 20): array
    {
        return $this->cronRepo->getByRestaurantId($restaurantId, $params, $page, $pageSize);
    }
    
    /**
     * 根据任务类型获取定时任务
     */
    public function getCronsByType(int $restaurantId, string $taskType, array $params = []): array
    {
        return $this->cronRepo->getByTaskType($restaurantId, $taskType, $params);
    }
    
    /**
     * 根据任务状态获取定时任务
     */
    public function getCronsByStatus(int $restaurantId, string $status, array $params = []): array
    {
        return $this->cronRepo->getByStatus($restaurantId, $status, $params);
    }
    
    /**
     * 根据执行频率获取定时任务
     */
    public function getCronsByFrequency(int $restaurantId, string $frequency, array $params = []): array
    {
        return $this->cronRepo->getByFrequency($restaurantId, $frequency, $params);
    }
    
    /**
     * 获取活跃的定时任务
     */
    public function getActiveCrons(int $restaurantId): array
    {
        $crons = $this->cronRepo->getActiveCrons($restaurantId);
        
        return [
            'success' => true,
            'crons' => $crons
        ];
    }
    
    /**
     * 获取需要执行的任务
     */
    public function getPendingCrons(int $restaurantId, int $limit = 50): array
    {
        $crons = $this->cronRepo->getPendingCrons($restaurantId, $limit);
        
        return [
            'success' => true,
            'crons' => $crons
        ];
    }
    
    /**
     * 获取失败的任务
     */
    public function getFailedCrons(int $restaurantId, int $limit = 50): array
    {
        $crons = $this->cronRepo->getFailedCrons($restaurantId, $limit);
        
        return [
            'success' => true,
            'crons' => $crons
        ];
    }
    
    /**
     * 创建定时任务
     */
    public function createCron(array $data, int $restaurantId): array
    {
        // 验证数据
        if (empty($data['task_name'])) {
            return ['success' => false, 'message' => '任务名称不能为空'];
        }
        
        if (empty($data['command'])) {
            return ['success' => false, 'message' => '执行命令不能为空'];
        }
        
        if (empty($data['frequency'])) {
            return ['success' => false, 'message' => '执行频率不能为空'];
        }
        
        if (empty($data['task_type'])) {
            return ['success' => false, 'message' => '任务类型不能为空'];
        }
        
        // 验证频率格式
        if (!$this->validateCronExpression($data['frequency'])) {
            return ['success' => false, 'message' => '无效的Cron表达式'];
        }
        
        $data['restaurant_id'] = $restaurantId;
        $data['status'] = $data['status'] ?? 'active';
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['success_count'] = $data['success_count'] ?? 0;
        $data['failure_count'] = $data['failure_count'] ?? 0;
        $data['avg_execution_time'] = $data['avg_execution_time'] ?? 0;
        $data['next_run_at'] = $this->calculateNextRunTime($data['frequency']);
        $data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');
        
        try {
            $cron = $this->cronRepo->create($data);
            
            return [
                'success' => true,
                'message' => '定时任务创建成功',
                'cron' => $cron
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => '定时任务创建失败：' . $e->getMessage()];
        }
    }
    
    /**
     * 更新定时任务
     */
    public function updateCron(int $cronId, array $data, int $restaurantId): array
    {
        // 验证数据
        if (empty($data['task_name'])) {
            return ['success' => false, 'message' => '任务名称不能为空'];
        }
        
        if (empty($data['command'])) {
            return ['success' => false, 'message' => '执行命令不能为空'];
        }
        
        if (empty($data['frequency'])) {
            return ['success' => false, 'message' => '执行频率不能为空'];
        }
        
        if (empty($data['task_type'])) {
            return ['success' => false, 'message' => '任务类型不能为空'];
        }
        
        // 验证频率格式
        if (!$this->validateCronExpression($data['frequency'])) {
            return ['success' => false, 'message' => '无效的Cron表达式'];
        }
        
        // 检查定时任务是否存在
        $cron = $this->cronRepo->getInfo($cronId);
        if (!$cron) {
            return ['success' => false, 'message' => '定时任务不存在'];
        }
        
        // 检查定时任务是否属于该餐厅
        if ($cron->restaurant_id != $restaurantId) {
            return ['success' => false, 'message' => '无权操作此定时任务'];
        }
        
        // 如果频率发生变化，重新计算下次执行时间
        if ($data['frequency'] !== $cron->frequency) {
            $data['next_run_at'] = $this->calculateNextRunTime($data['frequency']);
        }
        
        try {
            $result = $this->cronRepo->update($cronId, $data);
            
            if ($result) {
                return ['success' => true, 'message' => '定时任务更新成功'];
            } else {
                return ['success' => false, 'message' => '定时任务更新失败'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'message' => '定时任务更新失败：' . $e->getMessage()];
        }
    }
    
    /**
     * 删除定时任务
     */
    public function deleteCron(int $cronId, int $restaurantId): array
    {
        // 检查定时任务是否存在
        $cron = $this->cronRepo->getInfo($cronId);
        if (!$cron) {
            return ['success' => false, 'message' => '定时任务不存在'];
        }
        
        // 检查定时任务是否属于该餐厅
        if ($cron->restaurant_id != $restaurantId) {
            return ['success' => false, 'message' => '无权操作此定时任务'];
        }
        
        $result = $this->cronRepo->delete($cronId);
        
        if ($result) {
            return ['success' => true, 'message' => '定时任务删除成功'];
        } else {
            return ['success' => false, 'message' => '定时任务删除失败'];
        }
    }
    
    /**
     * 更新任务状态
     */
    public function updateCronStatus(int $cronId, string $status, string $remark, int $restaurantId): array
    {
        // 检查定时任务是否存在
        $cron = $this->cronRepo->getInfo($cronId);
        if (!$cron) {
            return ['success' => false, 'message' => '定时任务不存在'];
        }
        
        // 检查定时任务是否属于该餐厅
        if ($cron->restaurant_id != $restaurantId) {
            return ['success' => false, 'message' => '无权操作此定时任务'];
        }
        
        // 验证状态值
        $validStatuses = ['active', 'inactive', 'running', 'completed', 'failed', 'paused'];
        if (!in_array($status, $validStatuses)) {
            return ['success' => false, 'message' => '无效的状态值'];
        }
        
        $result = $this->cronRepo->updateCronStatus($cronId, $status, $remark);
        
        if ($result) {
            return ['success' => true, 'message' => '任务状态更新成功'];
        } else {
            return ['success' => false, 'message' => '任务状态更新失败'];
        }
    }
    
    /**
     * 手动执行任务
     */
    public function executeCron(int $cronId, int $restaurantId): array
    {
        // 检查定时任务是否存在
        $cron = $this->cronRepo->getInfo($cronId);
        if (!$cron) {
            return ['success' => false, 'message' => '定时任务不存在'];
        }
        
        // 检查定时任务是否属于该餐厅
        if ($cron->restaurant_id != $restaurantId) {
            return ['success' => false, 'message' => '无权操作此定时任务'];
        }
        
        if ($cron->status !== 'active') {
            return ['success' => false, 'message' => '任务未激活，无法执行'];
        }
        
        try {
            // 更新状态为执行中
            $this->cronRepo->updateCronStatus($cronId, 'running', '手动执行');
            
            // 执行任务
            $startTime = microtime(true);
            $output = $this->executeCommand($cron->command);
            $executionTime = microtime(true) - $startTime;
            
            // 判断执行结果
            $success = $output['success'];
            $outputText = $output['output'];
            
            // 更新执行统计
            $this->cronRepo->updateExecutionStats($cronId, $success, $outputText, $executionTime);
            
            // 计算下次执行时间
            $nextRunTime = $this->calculateNextRunTime($cron->frequency);
            $this->cronRepo->updateNextRunTime($cronId, $nextRunTime);
            
            if ($success) {
                return [
                    'success' => true,
                    'message' => '任务执行成功',
                    'execution_time' => round($executionTime, 2),
                    'output' => $outputText
                ];
            } else {
                return [
                    'success' => false,
                    'message' => '任务执行失败',
                    'execution_time' => round($executionTime, 2),
                    'output' => $outputText
                ];
            }
            
        } catch (\Exception $e) {
            // 更新状态为失败
            $this->cronRepo->updateCronStatus($cronId, 'failed', '执行异常：' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => '任务执行异常：' . $e->getMessage()
            ];
        }
    }
    
    /**
     * 获取定时任务统计信息
     */
    public function getCronStatistics(int $restaurantId): array
    {
        $statistics = $this->cronRepo->getCronStatistics($restaurantId);
        
        return [
            'success' => true,
            'statistics' => $statistics
        ];
    }
    
    /**
     * 获取执行趋势数据
     */
    public function getExecutionTrends(int $restaurantId, string $dateRange = 'week'): array
    {
        $trends = $this->cronRepo->getExecutionTrends($restaurantId, $dateRange);
        
        return [
            'success' => true,
            'trends' => $trends
        ];
    }
    
    /**
     * 获取即将执行的任务
     */
    public function getUpcomingCrons(int $restaurantId, int $hours = 24): array
    {
        $crons = $this->cronRepo->getUpcomingCrons($restaurantId, $hours);
        
        return [
            'success' => true,
            'crons' => $crons
        ];
    }
    
    /**
     * 清理过期的执行记录
     */
    public function cleanExpiredExecutionRecords(int $restaurantId, int $days = 90): array
    {
        if ($days <= 0) {
            return ['success' => false, 'message' => '天数必须大于0'];
        }
        
        $cleanedCount = $this->cronRepo->cleanExpiredExecutionRecords($restaurantId, $days);
        
        return [
            'success' => true,
            'message' => "成功清理过期执行记录",
            'cleaned_count' => $cleanedCount
        ];
    }
    
    /**
     * 验证Cron表达式
     */
    private function validateCronExpression(string $expression): bool
    {
        // 简单的Cron表达式验证
        $parts = explode(' ', trim($expression));
        
        if (count($parts) !== 5) {
            return false;
        }
        
        // 验证每个部分
        $patterns = [
            'minute' => '/^(\*|[0-5]?[0-9](-[0-5]?[0-9])?(,\d+)*|\*\/\d+)$/',
            'hour' => '/^(\*|1?[0-9]|2[0-3](-1?[0-9]|2[0-3])?(,\d+)*|\*\/\d+)$/',
            'day' => '/^(\*|[1-9]|[12][0-9]|3[01](-[1-9]|[12][0-9]|3[01])?(,\d+)*|\*\/\d+)$/',
            'month' => '/^(\*|[1-9]|1[0-2](-[1-9]|1[0-2])?(,\d+)*|\*\/\d+)$/',
            'weekday' => '/^(\*|[0-6](-[0-6])?(,\d+)*|\*\/\d+)$/'
        ];
        
        foreach ($parts as $index => $part) {
            $field = array_keys($patterns)[$index];
            if (!preg_match($patterns[$field], $part)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * 计算下次执行时间
     */
    private function calculateNextRunTime(string $expression): string
    {
        // 简单的下次执行时间计算
        $now = time();
        
        // 这里应该使用更复杂的Cron解析库
        // 为了简化，这里返回1小时后
        return date('Y-m-d H:i:s', $now + 3600);
    }
    
    /**
     * 允许执行的命令白名单（只允许 PHP 脚本和预定义命令）
     */
    private const ALLOWED_COMMAND_PREFIXES = [
        'php ',          // PHP 脚本执行
        'cd ',           // 切换目录（需配合后续 php 命令）
    ];

    /**
     * 禁止使用的危险字符/命令
     */
    private const DANGEROUS_PATTERNS = [
        '/[;&|`$(){}]/',   // Shell 元字符
        '/\b(rm|wget|curl|nc|bash|sh|python|perl|ruby)\b/i', // 危险命令
        '/\.\.\//',         // 路径遍历
        '/\/etc\//',        // 系统目录
        '/\bnofile\b/i',
    ];

    /**
     * 执行命令（安全加固版：命令白名单 + 元字符过滤）
     */
    private function executeCommand(string $command): array
    {
        try {
            $command = trim($command);

            // 空命令直接拒绝
            if ($command === '') {
                return [
                    'success' => false,
                    'output' => '命令不能为空',
                    'return_code' => -1,
                    'execution_time' => 0,
                ];
            }

            // 检查危险字符和命令
            foreach (self::DANGEROUS_PATTERNS as $pattern) {
                if (preg_match($pattern, $command)) {
                    \support\Log::warning('CronService 拒绝执行危险命令', [
                        'command' => $command,
                        'pattern' => $pattern,
                    ]);
                    return [
                        'success' => false,
                        'output' => '命令包含禁止的字符或关键词，已被安全策略拦截',
                        'return_code' => -1,
                        'execution_time' => 0,
                    ];
                }
            }

            // 检查命令白名单前缀
            $allowed = false;
            foreach (self::ALLOWED_COMMAND_PREFIXES as $prefix) {
                if (str_starts_with(strtolower($command), $prefix)) {
                    $allowed = true;
                    break;
                }
            }
            if (!$allowed) {
                \support\Log::warning('CronService 拒绝执行非白名单命令', [
                    'command' => $command,
                ]);
                return [
                    'success' => false,
                    'output' => '命令不在允许的白名单内，仅允许执行 php 脚本',
                    'return_code' => -1,
                    'execution_time' => 0,
                ];
            }

            $startTime = microtime(true);

            // 执行命令（使用 escapeshellcmd 进行二次转义防护）
            $safeCommand = escapeshellcmd($command);
            $output = [];
            $returnCode = 0;
            exec($safeCommand . ' 2>&1', $output, $returnCode);

            $executionTime = microtime(true) - $startTime;
            $outputText = implode("\n", $output);

            return [
                'success' => $returnCode === 0,
                'output' => $outputText,
                'return_code' => $returnCode,
                'execution_time' => $executionTime
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'output' => '执行异常：' . $e->getMessage(),
                'return_code' => -1,
                'execution_time' => 0
            ];
        }
    }
    
    /**
     * 获取任务执行建议
     */
    public function getCronRecommendations(int $restaurantId): array
    {
        try {
            $recommendations = [];
            
            // 获取失败的任务
            $failedCrons = $this->cronRepo->getFailedCrons($restaurantId, 1000);
            if (count($failedCrons) > 5) {
                $recommendations[] = '失败的任务较多，建议检查任务配置和系统状态';
            }
            
            // 获取统计信息
            $statistics = $this->cronRepo->getCronStatistics($restaurantId);
            $totalSuccess = $statistics['execution_stats']['total_success'] ?? 0;
            $totalFailure = $statistics['execution_stats']['total_failure'] ?? 0;
            
            if ($totalSuccess > 0) {
                $failureRate = ($totalFailure / ($totalSuccess + $totalFailure)) * 100;
                if ($failureRate > 20) {
                    $recommendations[] = '任务失败率较高，建议优化任务逻辑和错误处理';
                }
            }
            
            // 检查任务分布
            if (isset($statistics['frequency_stats'])) {
                $highFrequencyCount = 0;
                foreach ($statistics['frequency_stats'] as $freqStat) {
                    if (in_array($freqStat['frequency'], ['* * * * *', '*/5 * * * *'])) {
                        $highFrequencyCount += $freqStat['count'];
                    }
                }
                
                if ($highFrequencyCount > 10) {
                    $recommendations[] = '高频任务较多，建议优化任务频率以减少系统负载';
                }
            }
            
            if (empty($recommendations)) {
                $recommendations[] = '定时任务运行良好，继续保持当前配置';
            }
            
            return [
                'success' => true,
                'recommendations' => $recommendations
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '获取任务执行建议失败：' . $e->getMessage()
            ];
        }
    }
} 