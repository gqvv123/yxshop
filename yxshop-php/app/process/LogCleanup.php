<?php
namespace app\process;

use Workerman\Crontab\Crontab;
use Webman\RedisQueue\Client;
use support\Db;
use support\Log;
use support\Redis;
use app\common\Logger;
use Exception;

class LogCleanup
{
    public function onWorkerStart()
    {

        // // 每秒钟执行一次
        // new Crontab('*/1 * * * * *', function(){
        //     echo date('Y-m-d H:i:s')."\n";
        //     $this->handle();
        // });

        // // 每5秒执行一次
        // new Crontab('*/5 * * * * *', function(){
        //     echo date('Y-m-d H:i:s')."\n";   
        //     $this->handle();
        // });

        // // 每分钟执行一次
        // new Crontab('0 */1 * * * *', function(){
        //     echo date('Y-m-d H:i:s')."\n";
        //     $this->handle();
        // });

        // // 每5分钟执行一次
        // new Crontab('0 */5 * * * *', function(){
        //     echo date('Y-m-d H:i:s')."\n";
        //     $this->handle();
        // });

        // // 每分钟的第一秒执行
        // new Crontab('1 * * * * *', function(){
        //     echo date('Y-m-d H:i:s')."\n";
        //     $this->handle();
        // });

        new Crontab('0 2 * * *', function(){
            $this->handle();
        });

    }

    public function handle()
    {
        try {
            // 发送清理任务到队列
            Client::send('Log-Cleanup', json_encode([
                'timestamp' => date('Y-m-d H:i:s'),
                'trigger_type' => 'scheduled'
            ]));

            Logger::info('日志清理任务已触发', [
                'timestamp' => date('Y-m-d H:i:s')
            ], Logger::TYPE_SYSTEM);

        } catch (\Exception $e) {
            Logger::error('日志清理任务触发失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], Logger::TYPE_SYSTEM);
        }
    }
}