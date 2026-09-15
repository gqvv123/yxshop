<?php

namespace app\command;

use support\Redis;
use app\common\Logger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Webman\RedisQueue\Client;

class LogCleanupCommand extends Command
{
    protected static $defaultName = 'log:cleanup';
    protected static $defaultDescription = '手动触发日志清理任务';

    protected function configure()
    {
        $this->setDescription('手动触发日志清理任务');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            // 发送清理任务到队列
            Client::send('Log-Cleanup', [
                'timestamp' => date('Y-m-d H:i:s'),
                'trigger_type' => 'manual'
            ]);

            $output->writeln('<info>日志清理任务已触发</info>');
            Logger::info('日志清理任务已手动触发', [
                'timestamp' => date('Y-m-d H:i:s')
            ], Logger::TYPE_SYSTEM);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $output->writeln('<error>日志清理任务触发失败: ' . $e->getMessage() . '</error>');
            Logger::error('日志清理任务手动触发失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], Logger::TYPE_SYSTEM);

            return Command::FAILURE;
        }
    }
} 