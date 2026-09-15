<?php

namespace app\service;

use app\repository\BackupRepository;

/**
 * 备份服务类
 *
 * @property BackupRepository $repository
 */
class BackupService
{
    protected $backupRepo;
    
    public function __construct()
    {
        $this->backupRepo = new BackupRepository();
    }
    
    /**
     * 获取备份列表
     */
    public function getBackupList(int $restaurantId, array $params = [], int $page = 1, int $pageSize = 20): array
    {
        return $this->backupRepo->getByRestaurantId($restaurantId, $params, $page, $pageSize);
    }
    
    /**
     * 根据备份类型获取备份
     */
    public function getBackupsByType(int $restaurantId, string $backupType, array $params = []): array
    {
        return $this->backupRepo->getByBackupType($restaurantId, $backupType, $params);
    }
    
    /**
     * 根据备份状态获取备份
     */
    public function getBackupsByStatus(int $restaurantId, string $status, array $params = []): array
    {
        return $this->backupRepo->getByStatus($restaurantId, $status, $params);
    }
    
    /**
     * 根据备份级别获取备份
     */
    public function getBackupsByLevel(int $restaurantId, string $level, array $params = []): array
    {
        return $this->backupRepo->getByLevel($restaurantId, $level, $params);
    }
    
    /**
     * 获取最新的备份
     */
    public function getLatestBackup(int $restaurantId, string $backupType = null): array
    {
        $backup = $this->backupRepo->getLatestBackup($restaurantId, $backupType);
        
        if ($backup) {
            return [
                'success' => true,
                'backup' => $backup
            ];
        } else {
            return [
                'success' => false,
                'message' => '没有找到备份记录'
            ];
        }
    }
    
    /**
     * 获取成功的备份列表
     */
    public function getSuccessfulBackups(int $restaurantId, int $limit = 10): array
    {
        $backups = $this->backupRepo->getSuccessfulBackups($restaurantId, $limit);
        
        return [
            'success' => true,
            'backups' => $backups
        ];
    }
    
    /**
     * 获取失败的备份列表
     */
    public function getFailedBackups(int $restaurantId, int $limit = 10): array
    {
        $backups = $this->backupRepo->getFailedBackups($restaurantId, $limit);
        
        return [
            'success' => true,
            'backups' => $backups
        ];
    }
    
    /**
     * 获取进行中的备份
     */
    public function getInProgressBackups(int $restaurantId): array
    {
        $backups = $this->backupRepo->getInProgressBackups($restaurantId);
        
        return [
            'success' => true,
            'backups' => $backups
        ];
    }
    
    /**
     * 创建备份记录
     */
    public function createBackupRecord(array $data, int $restaurantId): array
    {
        // 验证数据
        if (empty($data['backup_name'])) {
            return ['success' => false, 'message' => '备份名称不能为空'];
        }
        
        if (empty($data['backup_type'])) {
            return ['success' => false, 'message' => '备份类型不能为空'];
        }
        
        if (empty($data['backup_level'])) {
            return ['success' => false, 'message' => '备份级别不能为空'];
        }
        
        $data['restaurant_id'] = $restaurantId;
        $data['status'] = $data['status'] ?? 'pending';
        $data['progress'] = $data['progress'] ?? 0;
        $data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');
        
        try {
            $backup = $this->backupRepo->create($data);
            
            return [
                'success' => true,
                'message' => '备份记录创建成功',
                'backup' => $backup
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => '备份记录创建失败：' . $e->getMessage()];
        }
    }
    
    /**
     * 开始数据库备份
     */
    public function startDatabaseBackup(int $restaurantId, string $backupName = null): array
    {
        if (!$backupName) {
            $backupName = '数据库备份_' . date('Y-m-d_H-i-s');
        }
        
        $data = [
            'backup_name' => $backupName,
            'backup_type' => 'database',
            'backup_level' => 'full',
            'description' => '完整数据库备份',
            'status' => 'pending'
        ];
        
        $result = $this->createBackupRecord($data, $restaurantId);
        
        if ($result['success']) {
            // 异步执行备份任务
            $this->executeDatabaseBackup($result['backup']->id, $restaurantId);
            
            return [
                'success' => true,
                'message' => '数据库备份已开始',
                'backup_id' => $result['backup']->id
            ];
        } else {
            return $result;
        }
    }
    
    /**
     * 开始文件备份
     */
    public function startFileBackup(int $restaurantId, string $backupName = null, array $includePaths = []): array
    {
        if (!$backupName) {
            $backupName = '文件备份_' . date('Y-m-d_H-i-s');
        }
        
        $data = [
            'backup_name' => $backupName,
            'backup_type' => 'files',
            'backup_level' => 'full',
            'description' => '文件系统备份',
            'status' => 'pending',
            'extra_data' => json_encode(['include_paths' => $includePaths])
        ];
        
        $result = $this->createBackupRecord($data, $restaurantId);
        
        if ($result['success']) {
            // 异步执行备份任务
            $this->executeFileBackup($result['backup']->id, $restaurantId, $includePaths);
            
            return [
                'success' => true,
                'message' => '文件备份已开始',
                'backup_id' => $result['backup']->id
            ];
        } else {
            return $result;
        }
    }
    
    /**
     * 开始配置备份
     */
    public function startConfigBackup(int $restaurantId, string $backupName = null): array
    {
        if (!$backupName) {
            $backupName = '配置备份_' . date('Y-m-d_H-i-s');
        }
        
        $data = [
            'backup_name' => $backupName,
            'backup_type' => 'config',
            'backup_level' => 'full',
            'description' => '系统配置备份',
            'status' => 'pending'
        ];
        
        $result = $this->createBackupRecord($data, $restaurantId);
        
        if ($result['success']) {
            // 异步执行备份任务
            $this->executeConfigBackup($result['backup']->id, $restaurantId);
            
            return [
                'success' => true,
                'message' => '配置备份已开始',
                'backup_id' => $result['backup']->id
            ];
        } else {
            return $result;
        }
    }
    
    /**
     * 执行数据库备份
     */
    private function executeDatabaseBackup(int $backupId, int $restaurantId): void
    {
        try {
            // 更新状态为进行中
            $this->backupRepo->updateBackupStatus($backupId, 'in_progress');
            $this->backupRepo->updateBackupProgress($backupId, 10, '开始数据库备份');
            
            // 获取数据库配置
            $dbConfig = config('database.connections.mysql');
            $backupDir = storage_path('backups/database');
            
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }
            
            $filename = "backup_{$restaurantId}_{$backupId}_" . date('Y-m-d_H-i-s') . '.sql';
            $filePath = $backupDir . '/' . $filename;
            
            // 执行mysqldump命令
            $command = sprintf(
                'mysqldump -h%s -P%s -u%s -p%s %s > %s',
                $dbConfig['host'],
                $dbConfig['port'],
                $dbConfig['username'],
                $dbConfig['password'],
                $dbConfig['database'],
                $filePath
            );
            
            exec($command, $output, $returnCode);
            
            if ($returnCode === 0 && file_exists($filePath)) {
                $fileSize = filesize($filePath);
                $checksum = md5_file($filePath);
                
                // 更新备份信息
                $this->backupRepo->updateBackupFileInfo($backupId, $filePath, $fileSize, $checksum);
                $this->backupRepo->updateBackupProgress($backupId, 100, '备份完成');
                $this->backupRepo->updateBackupStatus($backupId, 'completed', '数据库备份成功');
            } else {
                throw new \Exception('mysqldump命令执行失败');
            }
            
        } catch (\Exception $e) {
            $this->backupRepo->updateBackupStatus($backupId, 'failed', '数据库备份失败：' . $e->getMessage());
        }
    }
    
    /**
     * 执行文件备份
     */
    private function executeFileBackup(int $backupId, int $restaurantId, array $includePaths = []): void
    {
        try {
            // 更新状态为进行中
            $this->backupRepo->updateBackupStatus($backupId, 'in_progress');
            $this->backupRepo->updateBackupProgress($backupId, 10, '开始文件备份');
            
            $backupDir = storage_path('backups/files');
            
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }
            
            $filename = "files_backup_{$restaurantId}_{$backupId}_" . date('Y-m-d_H-i-s') . '.tar.gz';
            $filePath = $backupDir . '/' . $filename;
            
            // 如果没有指定路径，备份整个项目目录
            if (empty($includePaths)) {
                $includePaths = [base_path()];
            }
            
            // 创建tar.gz压缩包
            $command = 'tar -czf ' . $filePath . ' ' . implode(' ', $includePaths);
            exec($command, $output, $returnCode);
            
            if ($returnCode === 0 && file_exists($filePath)) {
                $fileSize = filesize($filePath);
                $checksum = md5_file($filePath);
                
                // 更新备份信息
                $this->backupRepo->updateBackupFileInfo($backupId, $filePath, $fileSize, $checksum);
                $this->backupRepo->updateBackupProgress($backupId, 100, '备份完成');
                $this->backupRepo->updateBackupStatus($backupId, 'completed', '文件备份成功');
            } else {
                throw new \Exception('tar命令执行失败');
            }
            
        } catch (\Exception $e) {
            $this->backupRepo->updateBackupStatus($backupId, 'failed', '文件备份失败：' . $e->getMessage());
        }
    }
    
    /**
     * 执行配置备份
     */
    private function executeConfigBackup(int $backupId, int $restaurantId): void
    {
        try {
            // 更新状态为进行中
            $this->backupRepo->updateBackupStatus($backupId, 'in_progress');
            $this->backupRepo->updateBackupProgress($backupId, 10, '开始配置备份');
            
            $backupDir = storage_path('backups/config');
            
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }
            
            $filename = "config_backup_{$restaurantId}_{$backupId}_" . date('Y-m-d_H-i-s') . '.json';
            $filePath = $backupDir . '/' . $filename;
            
            // 收集配置数据
            $configData = [
                'restaurant_id' => $restaurantId,
                'backup_id' => $backupId,
                'backup_time' => date('Y-m-d H:i:s'),
                'system_config' => config('app'),
                'database_config' => config('database'),
                'cache_config' => config('cache'),
                'queue_config' => config('queue')
            ];
            
            // 保存配置到文件
            if (file_put_contents($filePath, json_encode($configData, JSON_PRETTY_PRINT))) {
                $fileSize = filesize($filePath);
                $checksum = md5_file($filePath);
                
                // 更新备份信息
                $this->backupRepo->updateBackupFileInfo($backupId, $filePath, $fileSize, $checksum);
                $this->backupRepo->updateBackupProgress($backupId, 100, '备份完成');
                $this->backupRepo->updateBackupStatus($backupId, 'completed', '配置备份成功');
            } else {
                throw new \Exception('配置文件写入失败');
            }
            
        } catch (\Exception $e) {
            $this->backupRepo->updateBackupStatus($backupId, 'failed', '配置备份失败：' . $e->getMessage());
        }
    }
    
    /**
     * 更新备份状态
     */
    public function updateBackupStatus(int $backupId, string $status, string $remark, int $restaurantId): array
    {
        // 检查备份记录是否存在
        $backup = $this->backupRepo->getInfo($backupId);
        if (!$backup) {
            return ['success' => false, 'message' => '备份记录不存在'];
        }
        
        // 检查备份记录是否属于该餐厅
        if ($backup->restaurant_id != $restaurantId) {
            return ['success' => false, 'message' => '无权操作此备份记录'];
        }
        
        // 验证状态值
        $validStatuses = ['pending', 'in_progress', 'completed', 'failed', 'cancelled'];
        if (!in_array($status, $validStatuses)) {
            return ['success' => false, 'message' => '无效的状态值'];
        }
        
        $result = $this->backupRepo->updateBackupStatus($backupId, $status, $remark);
        
        if ($result) {
            return ['success' => true, 'message' => '备份状态更新成功'];
        } else {
            return ['success' => false, 'message' => '备份状态更新失败'];
        }
    }
    
    /**
     * 更新备份进度
     */
    public function updateBackupProgress(int $backupId, int $progress, string $currentStep, int $restaurantId): array
    {
        // 检查备份记录是否存在
        $backup = $this->backupRepo->getInfo($backupId);
        if (!$backup) {
            return ['success' => false, 'message' => '备份记录不存在'];
        }
        
        // 检查备份记录是否属于该餐厅
        if ($backup->restaurant_id != $restaurantId) {
            return ['success' => false, 'message' => '无权操作此备份记录'];
        }
        
        if ($progress < 0 || $progress > 100) {
            return ['success' => false, 'message' => '进度值必须在0-100之间'];
        }
        
        $result = $this->backupRepo->updateBackupProgress($backupId, $progress, $currentStep);
        
        if ($result) {
            return ['success' => true, 'message' => '备份进度更新成功'];
        } else {
            return ['success' => false, 'message' => '备份进度更新失败'];
        }
    }
    
    /**
     * 删除备份
     */
    public function deleteBackup(int $backupId, int $restaurantId): array
    {
        // 检查备份记录是否存在
        $backup = $this->backupRepo->getInfo($backupId);
        if (!$backup) {
            return ['success' => false, 'message' => '备份记录不存在'];
        }
        
        // 检查备份记录是否属于该餐厅
        if ($backup->restaurant_id != $restaurantId) {
            return ['success' => false, 'message' => '无权操作此备份记录'];
        }
        
        // 删除备份文件
        if (!empty($backup->file_path) && file_exists($backup->file_path)) {
            unlink($backup->file_path);
        }
        
        $result = $this->backupRepo->delete($backupId);
        
        if ($result) {
            return ['success' => true, 'message' => '备份删除成功'];
        } else {
            return ['success' => false, 'message' => '备份删除失败'];
        }
    }
    
    /**
     * 获取备份统计信息
     */
    public function getBackupStatistics(int $restaurantId): array
    {
        $statistics = $this->backupRepo->getBackupStatistics($restaurantId);
        
        return [
            'success' => true,
            'statistics' => $statistics
        ];
    }
    
    /**
     * 获取备份趋势数据
     */
    public function getBackupTrends(int $restaurantId, string $dateRange = 'month'): array
    {
        $trends = $this->backupRepo->getBackupTrends($restaurantId, $dateRange);
        
        return [
            'success' => true,
            'trends' => $trends
        ];
    }
    
    /**
     * 清理过期备份记录
     */
    public function cleanExpiredBackupRecords(int $restaurantId, int $days = 90): array
    {
        if ($days <= 0) {
            return ['success' => false, 'message' => '天数必须大于0'];
        }
        
        $deletedCount = $this->backupRepo->cleanExpiredBackupRecords($restaurantId, $days);
        
        return [
            'success' => true,
            'message' => "成功清理 {$deletedCount} 条过期备份记录",
            'deleted_count' => $deletedCount
        ];
    }
    
    /**
     * 获取备份建议
     */
    public function getBackupRecommendations(int $restaurantId): array
    {
        try {
            $recommendations = [];
            
            // 检查是否有最近的备份
            $latestBackup = $this->backupRepo->getLatestBackup($restaurantId);
            if (!$latestBackup) {
                $recommendations[] = '建议立即创建首次备份';
            } else {
                $lastBackupTime = strtotime($latestBackup->created_at);
                $daysSinceLastBackup = (time() - $lastBackupTime) / 86400;
                
                if ($daysSinceLastBackup > 7) {
                    $recommendations[] = '距离上次备份已超过7天，建议创建新备份';
                }
            }
            
            // 检查备份成功率
            $statistics = $this->backupRepo->getBackupStatistics($restaurantId);
            $totalBackups = $statistics['total_backups'];
            $failedBackups = 0;
            
            foreach ($statistics['status_stats'] as $statusStat) {
                if ($statusStat['status'] === 'failed') {
                    $failedBackups = $statusStat['count'];
                    break;
                }
            }
            
            if ($totalBackups > 0) {
                $failureRate = ($failedBackups / $totalBackups) * 100;
                if ($failureRate > 20) {
                    $recommendations[] = '备份失败率较高，建议检查备份配置和系统状态';
                }
            }
            
            // 检查备份存储空间
            $totalSize = $statistics['total_size'] ?? 0;
            $totalSizeGB = $totalSize / (1024 * 1024 * 1024);
            
            if ($totalSizeGB > 10) {
                $recommendations[] = '备份文件总大小超过10GB，建议清理旧备份或优化备份策略';
            }
            
            if (empty($recommendations)) {
                $recommendations[] = '备份状态良好，继续保持当前备份策略';
            }
            
            return [
                'success' => true,
                'recommendations' => $recommendations
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '获取备份建议失败：' . $e->getMessage()
            ];
        }
    }
} 