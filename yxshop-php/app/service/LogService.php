<?php

namespace app\service;

use app\repository\LogRepository;

/**
 * 日志服务类
 *
 * @property LogRepository $repository
 */
class LogService
{
    protected $logRepo;
    
    public function __construct()
    {
        $this->logRepo = new LogRepository();
    }
    
    /**
     * 获取日志列表
     */
    public function getLogList(int $restaurantId, array $params = [], int $page = 1, int $pageSize = 20): array
    {
        return $this->logRepo->getByRestaurantId($restaurantId, $params, $page, $pageSize);
    }
    
    /**
     * 根据日志类型获取日志
     */
    public function getLogsByType(int $restaurantId, string $logType, array $params = []): array
    {
        return $this->logRepo->getByLogType($restaurantId, $logType, $params);
    }
    
    /**
     * 根据日志级别获取日志
     */
    public function getLogsByLevel(int $restaurantId, string $logLevel, array $params = []): array
    {
        return $this->logRepo->getByLogLevel($restaurantId, $logLevel, $params);
    }
    
    /**
     * 根据用户ID获取日志
     */
    public function getLogsByUserId(int $restaurantId, int $userId, array $params = []): array
    {
        return $this->logRepo->getByUserId($restaurantId, $userId, $params);
    }
    
    /**
     * 根据操作类型获取日志
     */
    public function getLogsByAction(int $restaurantId, string $action, array $params = []): array
    {
        return $this->logRepo->getByAction($restaurantId, $action, $params);
    }
    
    /**
     * 获取错误日志
     */
    public function getErrorLogs(int $restaurantId, int $limit = 100): array
    {
        $logs = $this->logRepo->getErrorLogs($restaurantId, $limit);
        
        return [
            'success' => true,
            'logs' => $logs
        ];
    }
    
    /**
     * 获取操作日志
     */
    public function getOperationLogs(int $restaurantId, int $limit = 100): array
    {
        $logs = $this->logRepo->getOperationLogs($restaurantId, $limit);
        
        return [
            'success' => true,
            'logs' => $logs
        ];
    }
    
    /**
     * 获取登录日志
     */
    public function getLoginLogs(int $restaurantId, int $limit = 100): array
    {
        $logs = $this->logRepo->getLoginLogs($restaurantId, $limit);
        
        return [
            'success' => true,
            'logs' => $logs
        ];
    }
    
    /**
     * 记录日志
     */
    public function log(int $restaurantId, array $logData): array
    {
        // 验证数据
        if (empty($logData['log_type'])) {
            return ['success' => false, 'message' => '日志类型不能为空'];
        }
        
        if (empty($logData['log_level'])) {
            return ['success' => false, 'message' => '日志级别不能为空'];
        }
        
        if (empty($logData['action'])) {
            return ['success' => false, 'message' => '操作类型不能为空'];
        }
        
        if (empty($logData['message'])) {
            return ['success' => false, 'message' => '日志消息不能为空'];
        }
        
        $logData['restaurant_id'] = $restaurantId;
        $logData['created_at'] = $logData['created_at'] ?? date('Y-m-d H:i:s');
        
        try {
            $log = $this->logRepo->create($logData);
            
            return [
                'success' => true,
                'message' => '日志记录成功',
                'log' => $log
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => '日志记录失败：' . $e->getMessage()];
        }
    }
    
    /**
     * 记录操作日志
     */
    public function logOperation(int $restaurantId, int $userId, string $userName, string $action, string $message, array $details = [], string $ipAddress = '', string $userAgent = ''): array
    {
        $logData = [
            'log_type' => 'operation',
            'log_level' => 'info',
            'action' => $action,
            'message' => $message,
            'user_id' => $userId,
            'user_name' => $userName,
            'details' => json_encode($details),
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent
        ];
        
        return $this->log($restaurantId, $logData);
    }
    
    /**
     * 记录登录日志
     */
    public function logLogin(int $restaurantId, int $userId, string $userName, string $status, string $message, string $ipAddress = '', string $userAgent = ''): array
    {
        $logData = [
            'log_type' => 'auth',
            'log_level' => $status === 'success' ? 'info' : 'warning',
            'action' => 'login',
            'message' => $message,
            'user_id' => $userId,
            'user_name' => $userName,
            'details' => json_encode(['status' => $status]),
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent
        ];
        
        return $this->log($restaurantId, $logData);
    }
    
    /**
     * 记录错误日志
     */
    public function logError(int $restaurantId, string $action, string $message, array $details = [], int $userId = 0, string $userName = '', string $ipAddress = ''): array
    {
        $logData = [
            'log_type' => 'system',
            'log_level' => 'error',
            'action' => $action,
            'message' => $message,
            'user_id' => $userId,
            'user_name' => $userName,
            'details' => json_encode($details),
            'ip_address' => $ipAddress
        ];
        
        return $this->log($restaurantId, $logData);
    }
    
    /**
     * 记录系统日志
     */
    public function logSystem(int $restaurantId, string $action, string $message, array $details = [], string $logLevel = 'info'): array
    {
        $logData = [
            'log_type' => 'system',
            'log_level' => $logLevel,
            'action' => $action,
            'message' => $message,
            'details' => json_encode($details)
        ];
        
        return $this->log($restaurantId, $logData);
    }
    
    /**
     * 记录安全日志
     */
    public function logSecurity(int $restaurantId, string $action, string $message, array $details = [], int $userId = 0, string $userName = '', string $ipAddress = ''): array
    {
        $logData = [
            'log_type' => 'security',
            'log_level' => 'warning',
            'action' => $action,
            'message' => $message,
            'user_id' => $userId,
            'user_name' => $userName,
            'details' => json_encode($details),
            'ip_address' => $ipAddress
        ];
        
        return $this->log($restaurantId, $logData);
    }
    
    /**
     * 清理旧日志
     */
    public function cleanOldLogs(int $restaurantId, int $days = 30): array
    {
        $deletedCount = $this->logRepo->cleanOldLogs($restaurantId, $days);
        
        return [
            'success' => true,
            'message' => "成功清理 {$deletedCount} 条旧日志",
            'deleted_count' => $deletedCount
        ];
    }
    
    /**
     * 获取日志统计信息
     */
    public function getLogStatistics(int $restaurantId, string $dateRange = 'today'): array
    {
        $statistics = $this->logRepo->getLogStatistics($restaurantId, $dateRange);
        
        return [
            'success' => true,
            'statistics' => $statistics
        ];
    }
    
    /**
     * 导出日志
     */
    public function exportLogs(int $restaurantId, array $params = []): array
    {
        $exportData = $this->logRepo->exportLogs($restaurantId, $params);
        
        if (!empty($exportData)) {
            return [
                'success' => true,
                'message' => '日志导出成功',
                'data' => $exportData,
                'count' => count($exportData)
            ];
        } else {
            return [
                'success' => false,
                'message' => '没有可导出的日志数据'
            ];
        }
    }
    
    /**
     * 获取日志分析报告
     */
    public function getLogAnalysisReport(int $restaurantId, string $dateRange = 'month'): array
    {
        try {
            // 获取基础统计
            $statistics = $this->logRepo->getLogStatistics($restaurantId, $dateRange);
            
            // 分析错误趋势
            $errorTrend = $this->analyzeErrorTrend($restaurantId, $dateRange);
            
            // 分析用户行为
            $userBehavior = $this->analyzeUserBehavior($restaurantId, $dateRange);
            
            // 分析系统性能
            $systemPerformance = $this->analyzeSystemPerformance($restaurantId, $dateRange);
            
            $report = [
                'summary' => [
                    'total_logs' => $statistics['total_logs'],
                    'date_range' => $dateRange,
                    'generated_at' => date('Y-m-d H:i:s')
                ],
                'statistics' => $statistics,
                'error_analysis' => $errorTrend,
                'user_behavior' => $userBehavior,
                'system_performance' => $systemPerformance,
                'recommendations' => $this->generateRecommendations($statistics, $errorTrend, $userBehavior, $systemPerformance)
            ];
            
            return [
                'success' => true,
                'report' => $report
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '生成日志分析报告失败：' . $e->getMessage()
            ];
        }
    }
    
    /**
     * 分析错误趋势
     */
    private function analyzeErrorTrend(int $restaurantId, string $dateRange): array
    {
        $errorLogs = $this->logRepo->getErrorLogs($restaurantId, 1000);
        
        $errorCount = count($errorLogs);
        $criticalCount = 0;
        $errorTypes = [];
        
        foreach ($errorLogs as $log) {
            if ($log['log_level'] === 'critical') {
                $criticalCount++;
            }
            
            $action = $log['action'];
            if (!isset($errorTypes[$action])) {
                $errorTypes[$action] = 0;
            }
            $errorTypes[$action]++;
        }
        
        arsort($errorTypes);
        
        return [
            'total_errors' => $errorCount,
            'critical_errors' => $criticalCount,
            'error_rate' => $errorCount > 0 ? round(($criticalCount / $errorCount) * 100, 2) : 0,
            'top_error_types' => array_slice($errorTypes, 0, 5, true)
        ];
    }
    
    /**
     * 分析用户行为
     */
    private function analyzeUserBehavior(int $restaurantId, string $dateRange): array
    {
        $operationLogs = $this->logRepo->getOperationLogs($restaurantId, 1000);
        
        $userActions = [];
        $activeUsers = [];
        
        foreach ($operationLogs as $log) {
            $action = $log['action'];
            if (!isset($userActions[$action])) {
                $userActions[$action] = 0;
            }
            $userActions[$action]++;
            
            $userId = $log['user_id'];
            if (!isset($activeUsers[$userId])) {
                $activeUsers[$userId] = [
                    'user_id' => $userId,
                    'user_name' => $log['user_name'],
                    'action_count' => 0
                ];
            }
            $activeUsers[$userId]['action_count']++;
        }
        
        arsort($userActions);
        usort($activeUsers, function($a, $b) {
            return $b['action_count'] - $a['action_count'];
        });
        
        return [
            'top_actions' => array_slice($userActions, 0, 10, true),
            'most_active_users' => array_slice($activeUsers, 0, 10)
        ];
    }
    
    /**
     * 分析系统性能
     */
    private function analyzeSystemPerformance(int $restaurantId, string $dateRange): array
    {
        $systemLogs = $this->logRepo->getByLogType($restaurantId, 'system', []);
        
        $performanceIssues = 0;
        $slowOperations = 0;
        
        foreach ($systemLogs as $log) {
            if (strpos($log['message'], 'performance') !== false || strpos($log['message'], 'slow') !== false) {
                $performanceIssues++;
            }
            
            if (strpos($log['message'], 'timeout') !== false || strpos($log['message'], 'delay') !== false) {
                $slowOperations++;
            }
        }
        
        return [
            'performance_issues' => $performanceIssues,
            'slow_operations' => $slowOperations,
            'overall_health' => $performanceIssues === 0 ? 'excellent' : ($performanceIssues < 5 ? 'good' : 'needs_attention')
        ];
    }
    
    /**
     * 生成建议
     */
    private function generateRecommendations(array $statistics, array $errorTrend, array $userBehavior, array $systemPerformance): array
    {
        $recommendations = [];
        
        // 基于错误趋势的建议
        if ($errorTrend['critical_errors'] > 0) {
            $recommendations[] = '存在严重错误，建议立即检查系统状态';
        }
        
        if ($errorTrend['total_errors'] > 100) {
            $recommendations[] = '错误日志较多，建议优化系统稳定性';
        }
        
        // 基于用户行为的建议
        if (count($userBehavior['top_actions']) > 0) {
            $topAction = array_key_first($userBehavior['top_actions']);
            $recommendations[] = "用户最常执行的操作是：{$topAction}，建议优化相关功能";
        }
        
        // 基于系统性能的建议
        if ($systemPerformance['overall_health'] === 'needs_attention') {
            $recommendations[] = '系统性能需要关注，建议检查资源使用情况';
        }
        
        if (empty($recommendations)) {
            $recommendations[] = '系统运行良好，继续保持';
        }
        
        return $recommendations;
    }
} 