<?php

namespace app\common;

class Error
{
    // 系统错误 (1000-1999)
    const SYSTEM_ERROR = 1000;
    const DB_CONNECTION_ERROR = 1001;
    const REDIS_CONNECTION_ERROR = 1002;
    const INVALID_PARAMETER = 1003;
    const METHOD_NOT_FOUND = 1004;
    const CLASS_NOT_FOUND = 1005;
    const FILE_NOT_FOUND = 1006;
    const PERMISSION_DENIED = 1007;

    // 用户相关错误 (2000-2999)
    const USER_NOT_FOUND = 2000;
    const USER_BALANCE_INSUFFICIENT = 2001;
    const USER_ACCOUNT_LOCKED = 2002;
    const USER_ACCOUNT_DISABLED = 2003;
    const USER_LOGIN_FAILED = 2004;
    const USER_TOKEN_EXPIRED = 2005;
    const USER_TOKEN_INVALID = 2006;

    // 事务相关错误 (3000-3999)
    const EXCHANGE_ORDER_NOT_FOUND = 3000;
    const EXCHANGE_ORDER_CANCELLED = 3001;
    const EXCHANGE_ORDER_EXPIRED = 3002;
    const EXCHANGE_ORDER_PROCESSING = 3003;
    const EXCHANGE_ORDER_COMPLETED = 3004;
    const EXCHANGE_RATE_INVALID = 3005;
    const EXCHANGE_AMOUNT_INVALID = 3006;
    const EXCHANGE_CURRENCY_NOT_SUPPORTED = 3007;
    const EXCHANGE_BALANCE_FREEZE_FAILED = 3008;
    const EXCHANGE_BALANCE_UNFREEZE_FAILED = 3009;
    const EXCHANGE_TRADE_ACCOUNT_NOT_FOUND = 3010;
    const EXCHANGE_TRADE_ACCOUNT_BALANCE_INSUFFICIENT = 3011;

    // 余额相关错误 (4000-4999)
    const BALANCE_RECORD_NOT_FOUND = 4000;
    const BALANCE_UPDATE_FAILED = 4001;
    const BALANCE_FREEZE_FAILED = 4002;
    const BALANCE_UNFREEZE_FAILED = 4003;
    const BALANCE_INSUFFICIENT = 4004;
    const BALANCE_CURRENCY_NOT_SUPPORTED = 4005;

    // 通知相关错误 (5000-5999)
    const NOTIFICATION_SEND_FAILED = 5000;
    const NOTIFICATION_TEMPLATE_NOT_FOUND = 5001;
    const NOTIFICATION_RECIPIENT_INVALID = 5002;

    // 错误描述映射
    public static $messages = [
        // 系统错误
        self::SYSTEM_ERROR => '系统错误',
        self::DB_CONNECTION_ERROR => '数据库连接失败',
        self::REDIS_CONNECTION_ERROR => 'Redis连接失败',
        self::INVALID_PARAMETER => '无效的参数',
        self::METHOD_NOT_FOUND => '方法不存在',
        self::CLASS_NOT_FOUND => '类不存在',
        self::FILE_NOT_FOUND => '文件不存在',
        self::PERMISSION_DENIED => '权限不足',

        // 用户相关错误
        self::USER_NOT_FOUND => '用户不存在',
        self::USER_BALANCE_INSUFFICIENT => '用户余额不足',
        self::USER_ACCOUNT_LOCKED => '账户已被锁定',
        self::USER_ACCOUNT_DISABLED => '账户已被禁用',
        self::USER_LOGIN_FAILED => '登录失败',
        self::USER_TOKEN_EXPIRED => '登录已过期',
        self::USER_TOKEN_INVALID => '无效的登录凭证',

        // 事务相关错误
        self::EXCHANGE_ORDER_NOT_FOUND => '订单不存在',
        self::EXCHANGE_ORDER_CANCELLED => '订单已取消',
        self::EXCHANGE_ORDER_EXPIRED => '订单已过期',
        self::EXCHANGE_ORDER_PROCESSING => '订单处理中',
        self::EXCHANGE_ORDER_COMPLETED => '订单已完成',
        self::EXCHANGE_RATE_INVALID => '无效的汇率',
        self::EXCHANGE_AMOUNT_INVALID => '无效的事务金额',
        self::EXCHANGE_CURRENCY_NOT_SUPPORTED => '不支持的货币',
        self::EXCHANGE_BALANCE_FREEZE_FAILED => '余额冻结失败',
        self::EXCHANGE_BALANCE_UNFREEZE_FAILED => '余额解冻失败',
        self::EXCHANGE_TRADE_ACCOUNT_NOT_FOUND => '事务账户不存在',
        self::EXCHANGE_TRADE_ACCOUNT_BALANCE_INSUFFICIENT => '事务账户余额不足',

        // 余额相关错误
        self::BALANCE_RECORD_NOT_FOUND => '余额记录不存在',
        self::BALANCE_UPDATE_FAILED => '余额更新失败',
        self::BALANCE_FREEZE_FAILED => '余额冻结失败',
        self::BALANCE_UNFREEZE_FAILED => '余额解冻失败',
        self::BALANCE_INSUFFICIENT => '余额不足',
        self::BALANCE_CURRENCY_NOT_SUPPORTED => '不支持的货币',

        // 通知相关错误
        self::NOTIFICATION_SEND_FAILED => '通知发送失败',
        self::NOTIFICATION_TEMPLATE_NOT_FOUND => '通知模板不存在',
        self::NOTIFICATION_RECIPIENT_INVALID => '无效的通知接收者',
    ];

    /**
     * 获取错误信息
     * @param int $code 错误代码
     * @return string 错误信息
     */
    public static function getMessage($code)
    {
        return self::$messages[$code] ?? '未知错误';
    }

    /**
     * 抛出业务异常
     * @param int $code 错误代码
     * @param string $message 自定义错误信息
     * @throws \Exception
     */
    public static function throwException($code, $message = '')
    {
        $errorMessage = $message ?: self::getMessage($code);
        throw new \app\exception\BusinessException($errorMessage, $code);
    }
}