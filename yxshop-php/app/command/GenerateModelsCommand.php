<?php

namespace app\command;

use support\Command;
use support\Db;

/**
 * 生成模型、仓储、服务文件命令
 */
class GenerateModelsCommand extends Command
{
    protected static $defaultName = 'generate:models';
    protected static $defaultDescription = '生成模型、仓储、服务文件';

    public function handle()
    {
        $this->info('开始生成模型、仓储、服务文件...');

        // 获取所有表名
        $tables = $this->getTables();
        
        foreach ($tables as $table) {
            $this->info("正在生成 {$table} 相关文件...");
            
            // 生成模型
            $this->generateModel($table);
            
            // 生成仓储
            $this->generateRepository($table);
            
            // 生成服务
            $this->generateService($table);
        }

        $this->info('所有文件生成完成！');
    }

    /**
     * 获取所有表名
     */
    private function getTables()
    {
        $tables = Db::select("SHOW TABLES LIKE 'yxshop_%'");
        $tableNames = [];
        
        foreach ($tables as $table) {
            $tableName = array_values((array)$table)[0];
            $tableNames[] = $tableName;
        }
        
        return $tableNames;
    }

    /**
     * 生成模型文件
     */
    private function generateModel($table)
    {
        $className = $this->getClassName($table);
        $modelPath = base_path("app/model/{$className}.php");
        
        if (file_exists($modelPath)) {
            $this->warn("模型文件 {$className}.php 已存在，跳过生成");
            return;
        }

        // 获取表结构
        $columns = $this->getTableColumns($table);
        
        // 生成模型内容
        $content = $this->generateModelContent($className, $table, $columns);
        
        // 写入文件
        file_put_contents($modelPath, $content);
        $this->info("生成模型文件: {$className}.php");
    }

    /**
     * 生成仓储文件
     */
    private function generateRepository($table)
    {
        $className = $this->getClassName($table);
        $repositoryPath = base_path("app/repository/{$className}Repository.php");
        
        if (file_exists($repositoryPath)) {
            $this->warn("仓储文件 {$className}Repository.php 已存在，跳过生成");
            return;
        }

        // 生成仓储内容
        $content = $this->generateRepositoryContent($className, $table);
        
        // 写入文件
        file_put_contents($repositoryPath, $content);
        $this->info("生成仓储文件: {$className}Repository.php");
    }

    /**
     * 生成服务文件
     */
    private function generateService($table)
    {
        $className = $this->getClassName($table);
        $servicePath = base_path("app/service/{$className}Service.php");
        
        if (file_exists($servicePath)) {
            $this->warn("服务文件 {$className}Service.php 已存在，跳过生成");
            return;
        }

        // 生成服务内容
        $content = $this->generateServiceContent($className, $table);
        
        // 写入文件
        file_put_contents($servicePath, $content);
        $this->info("生成服务文件: {$className}Service.php");
    }

    /**
     * 获取类名
     */
    private function getClassName($table)
    {
        // 移除前缀
        $name = str_replace('yxshop_', '', $table);
        
        // 转换为驼峰命名
        $name = str_replace('_', ' ', $name);
        $name = ucwords($name);
        $name = str_replace(' ', '', $name);
        
        return $name;
    }

    /**
     * 获取表结构
     */
    private function getTableColumns($table)
    {
        $columns = Db::select("DESCRIBE `{$table}`");
        $result = [];
        
        foreach ($columns as $column) {
            $result[] = [
                'name' => $column->Field,
                'type' => $column->Type,
                'null' => $column->Null,
                'key' => $column->Key,
                'default' => $column->Default,
                'extra' => $column->Extra,
            ];
        }
        
        return $result;
    }

    /**
     * 生成模型内容
     */
    private function generateModelContent($className, $table, $columns)
    {
        $fillable = [];
        $casts = [];
        $hidden = [];
        
        foreach ($columns as $column) {
            $fillable[] = "'{$column['name']}'";
            
            // 生成类型转换
            if (strpos($column['type'], 'int') !== false) {
                $casts[] = "'{$column['name']}' => 'integer'";
            } elseif (strpos($column['type'], 'decimal') !== false || strpos($column['type'], 'float') !== false) {
                $casts[] = "'{$column['name']}' => 'decimal:2'";
            } elseif (strpos($column['type'], 'tinyint(1)') !== false) {
                $casts[] = "'{$column['name']}' => 'boolean'";
            } elseif (strpos($column['type'], 'text') !== false) {
                $casts[] = "'{$column['name']}' => 'string'";
            }
            
            // 隐藏敏感字段
            if (in_array($column['name'], ['password', 'open_id', 'app_secret'])) {
                $hidden[] = "'{$column['name']}'";
            }
        }
        
        $fillableStr = implode(', ', $fillable);
        $castsStr = implode(', ', $casts);
        $hiddenStr = implode(', ', $hidden);
        
        $content = "<?php

namespace app\model;

/**
 * {$className}模型
 */
class {$className} extends BaseModel
{
    protected \$table = '{$table}';

    protected \$fillable = [
        {$fillableStr}
    ];

    protected \$casts = [
        {$castsStr}
    ];

    protected \$hidden = [{$hiddenStr}];

    /**
     * 获取表名
     */
    public function getTable()
    {
        return '{$table}';
    }
}";

        return $content;
    }

    /**
     * 生成仓储内容
     */
    private function generateRepositoryContent($className, $table)
    {
        $content = "<?php

namespace app\repository;

use app\model\\{$className};

/**
 * {$className}仓储类
 */
class {$className}Repository extends BaseRepository
{
    public function __construct({$className} \$model)
    {
        parent::__construct(\$model);
    }

    /**
     * 根据条件查找
     */
    public function findByCondition(array \$conditions)
    {
        \$query = \$this->query();
        
        foreach (\$conditions as \$field => \$value) {
            if (is_array(\$value)) {
                \$query->whereIn(\$field, \$value);
            } else {
                \$query->where(\$field, \$value);
            }
        }
        
        return \$query->first();
    }

    /**
     * 根据条件查找所有
     */
    public function findAllByCondition(array \$conditions)
    {
        \$query = \$this->query();
        
        foreach (\$conditions as \$field => \$value) {
            if (is_array(\$value)) {
                \$query->whereIn(\$field, \$value);
            } else {
                \$query->where(\$field, \$value);
            }
        }
        
        return \$query->get();
    }

    /**
     * 获取统计信息
     */
    public function getStats(array \$conditions = [])
    {
        \$query = \$this->query();
        
        foreach (\$conditions as \$field => \$value) {
            if (is_array(\$value)) {
                \$query->whereIn(\$field, \$value);
            } else {
                \$query->where(\$field, \$value);
            }
        }
        
        return [
            'total' => \$query->count(),
            'active' => \$query->where('deleted_at', 0)->count(),
            'inactive' => \$query->where('deleted_at', '>', 0)->count(),
        ];
    }
}";

        return $content;
    }

    /**
     * 生成服务内容
     */
    private function generateServiceContent($className, $table)
    {
        $content = "<?php

namespace app\service;

use app\repository\\{$className}Repository;

/**
 * {$className}服务类
 */
class {$className}Service extends BaseService
{
    public function __construct({$className}Repository \$repository)
    {
        parent::__construct(\$repository);
    }

    /**
     * 获取列表
     */
    public function getList(array \$conditions = [], \$page = 1, \$limit = 15)
    {
        try {
            return \$this->paginate(\$page, \$limit, \$conditions);
        } catch (Exception \$e) {
            \$this->logError('获取列表失败', [
                'conditions' => \$conditions,
                'page' => \$page,
                'limit' => \$limit,
                'error' => \$e->getMessage()
            ]);
            throw \$e;
        }
    }

    /**
     * 获取详情
     */
    public function getDetail(\$id)
    {
        try {
            return \$this->findOrFail(\$id);
        } catch (Exception \$e) {
            \$this->logError('获取详情失败', [
                'id' => \$id,
                'error' => \$e->getMessage()
            ]);
            throw \$e;
        }
    }

    /**
     * 创建记录
     */
    public function createItem(array \$data)
    {
        try {
            \$this->logInfo('创建记录开始', ['data' => \$data]);
            \$result = \$this->create(\$data);
            \$this->logInfo('创建记录成功', ['id' => \$result->getKey()]);
            return \$result;
        } catch (Exception \$e) {
            \$this->logError('创建记录失败', [
                'data' => \$data,
                'error' => \$e->getMessage()
            ]);
            throw \$e;
        }
    }

    /**
     * 更新记录
     */
    public function updateItem(\$id, array \$data)
    {
        try {
            \$this->logInfo('更新记录开始', ['id' => \$id, 'data' => \$data]);
            \$result = \$this->update(\$id, \$data);
            \$this->logInfo('更新记录成功', ['id' => \$id]);
            return \$result;
        } catch (Exception \$e) {
            \$this->logError('更新记录失败', [
                'id' => \$id,
                'data' => \$data,
                'error' => \$e->getMessage()
            ]);
            throw \$e;
        }
    }

    /**
     * 删除记录
     */
    public function deleteItem(\$id)
    {
        try {
            \$this->logInfo('删除记录开始', ['id' => \$id]);
            \$result = \$this->delete(\$id);
            \$this->logInfo('删除记录成功', ['id' => \$id]);
            return \$result;
        } catch (Exception \$e) {
            \$this->logError('删除记录失败', [
                'id' => \$id,
                'error' => \$e->getMessage()
            ]);
            throw \$e;
        }
    }

    /**
     * 软删除
     */
    public function softDeleteItem(\$id)
    {
        try {
            \$this->logInfo('软删除记录开始', ['id' => \$id]);
            \$result = \$this->softDelete(\$id);
            \$this->logInfo('软删除记录成功', ['id' => \$id]);
            return \$result;
        } catch (Exception \$e) {
            \$this->logError('软删除记录失败', [
                'id' => \$id,
                'error' => \$e->getMessage()
            ]);
            throw \$e;
        }
    }

    /**
     * 恢复软删除
     */
    public function restoreItem(\$id)
    {
        try {
            \$this->logInfo('恢复软删除记录开始', ['id' => \$id]);
            \$result = \$this->restore(\$id);
            \$this->logInfo('恢复软删除记录成功', ['id' => \$id]);
            return \$result;
        } catch (Exception \$e) {
            \$this->logError('恢复软删除记录失败', [
                'id' => \$id,
                'error' => \$e->getMessage()
            ]);
            throw \$e;
        }
    }

    /**
     * 批量操作
     */
    public function batchOperation(\$action, array \$ids, array \$data = [])
    {
        try {
            \$this->logInfo('批量操作开始', [
                'action' => \$action,
                'ids' => \$ids,
                'data' => \$data
            ]);
            \$result = \$this->batch(\$action, \$ids, \$data);
            \$this->logInfo('批量操作成功', [
                'action' => \$action,
                'ids' => \$ids,
                'result' => \$result
            ]);
            return \$result;
        } catch (Exception \$e) {
            \$this->logError('批量操作失败', [
                'action' => \$action,
                'ids' => \$ids,
                'data' => \$data,
                'error' => \$e->getMessage()
            ]);
            throw \$e;
        }
    }
}";

        return $content;
    }
}