<?php

namespace app\command;

use support\Db;
use Illuminate\Database\Schema\Blueprint;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateCommand extends Command
{
    protected static $defaultName = 'migrate';
    protected static $defaultDescription = '执行数据库迁移';

    protected function configure()
    {
        $this->setDescription('执行数据库迁移')
            ->addOption('rollback', 'r', InputOption::VALUE_NONE, '回滚迁移');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            // 获取所有迁移文件
            $files = glob(__DIR__ . '/../database/migrations/*.php');
            sort($files);

            if ($input->getOption('rollback')) {
                // 回滚迁移
                $files = array_reverse($files);
                foreach ($files as $file) {
                    $className = $this->getClassNameFromFile($file);
                    require_once $file;
                    
                    $migration = new $className();
                    $output->writeln("<info>回滚迁移: {$className}</info>");
                    
                    $migration->down();
                    $output->writeln("<info>回滚完成: {$className}</info>");
                }
                $output->writeln('<info>所有迁移回滚完成</info>');
            } else {
                // 执行迁移
                foreach ($files as $file) {
                    $className = $this->getClassNameFromFile($file);
                    require_once $file;
                    
                    $migration = new $className();
                    $output->writeln("<info>执行迁移: {$className}</info>");
                    
                    $migration->up();
                    $output->writeln("<info>迁移完成: {$className}</info>");
                }
                $output->writeln('<info>所有迁移执行完成</info>');
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $output->writeln('<error>操作失败: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }

    /**
     * 从文件名获取类名
     * @param string $file
     * @return string
     */
    private function getClassNameFromFile(string $file): string
    {
        $content = file_get_contents($file);
        if (preg_match('/class\s+(\w+)/', $content, $matches)) {
            return $matches[1];
        }
        throw new \Exception("无法从文件 {$file} 中获取类名");
    }
} 