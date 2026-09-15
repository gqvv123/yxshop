<?php

namespace app\websocket;

use app\model\Order;
use app\model\Restaurant;

class OrderWebSocket
{
    /**
     * 连接创建时触发
     */
    public function onConnect($connection)
    {
        \support\Log::info("WebSocket连接: " . $connection->id);
    }
    
    /**
     * 收到消息时触发
     */
    public function onMessage($connection, $data)
    {
        $message = json_decode($data, true);
        
        if (!$message) {
            $connection->send(json_encode([
                'type' => 'error',
                'message' => '无效的消息格式'
            ]));
            return;
        }
        
        switch ($message['type']) {
            case 'subscribe':
                $this->handleSubscribe($connection, $message);
                break;
            case 'unsubscribe':
                $this->handleUnsubscribe($connection, $message);
                break;
            case 'ping':
                $connection->send(json_encode([
                    'type' => 'pong',
                    'timestamp' => time()
                ]));
                break;
            default:
                $connection->send(json_encode([
                    'type' => 'error',
                    'message' => '未知的消息类型'
                ]));
        }
    }
    
    /**
     * 连接断开时触发
     */
    public function onClose($connection)
    {
        \support\Log::info("WebSocket断开: " . $connection->id);
        
        // 清理连接订阅
        if (isset($connection->subscriptions)) {
            foreach ($connection->subscriptions as $restaurant_id) {
                $this->removeConnectionFromRoom($restaurant_id, $connection);
            }
        }
    }
    
    /**
     * 处理订阅请求
     */
    private function handleSubscribe($connection, $message)
    {
        $restaurant_id = $message['restaurant_id'] ?? null;
        
        if (!$restaurant_id) {
            $connection->send(json_encode([
                'type' => 'error',
                'message' => '缺少餐厅ID'
            ]));
            return;
        }
        
        // 验证餐厅是否存在
        $restaurant = Restaurant::find($restaurant_id);
        if (!$restaurant) {
            $connection->send(json_encode([
                'type' => 'error',
                'message' => '餐厅不存在'
            ]));
            return;
        }
        
        // 添加到房间
        $this->addConnectionToRoom($restaurant_id, $connection);
        
        // 初始化订阅列表
        if (!isset($connection->subscriptions)) {
            $connection->subscriptions = [];
        }
        $connection->subscriptions[] = $restaurant_id;
        
        $connection->send(json_encode([
            'type' => 'subscribed',
            'restaurant_id' => $restaurant_id,
            'message' => '订阅成功'
        ]));
        
        \support\Log::info("WebSocket订阅", ['connection' => $connection->id, 'restaurant_id' => $restaurant_id]);
    }
    
    /**
     * 处理取消订阅请求
     */
    private function handleUnsubscribe($connection, $message)
    {
        $restaurant_id = $message['restaurant_id'] ?? null;
        
        if (!$restaurant_id) {
            $connection->send(json_encode([
                'type' => 'error',
                'message' => '缺少餐厅ID'
            ]));
            return;
        }
        
        // 从房间移除
        $this->removeConnectionFromRoom($restaurant_id, $connection);
        
        // 从订阅列表中移除
        if (isset($connection->subscriptions)) {
            $connection->subscriptions = array_filter(
                $connection->subscriptions,
                function($id) use ($restaurant_id) {
                    return $id != $restaurant_id;
                }
            );
        }
        
        $connection->send(json_encode([
            'type' => 'unsubscribed',
            'restaurant_id' => $restaurant_id,
            'message' => '取消订阅成功'
        ]));
        
        \support\Log::info("WebSocket取消订阅", ['connection' => $connection->id, 'restaurant_id' => $restaurant_id]);
    }
    
    private static $rooms = [];

    private function addConnectionToRoom($restaurant_id, $connection)
    {
        if (!isset(self::$rooms[$restaurant_id])) {
            self::$rooms[$restaurant_id] = [];
        }
        self::$rooms[$restaurant_id][$connection->id] = $connection;
    }

    private function removeConnectionFromRoom($restaurant_id, $connection)
    {
        if (isset(self::$rooms[$restaurant_id][$connection->id])) {
            unset(self::$rooms[$restaurant_id][$connection->id]);
            if (empty(self::$rooms[$restaurant_id])) {
                unset(self::$rooms[$restaurant_id]);
            }
        }
    }

    private static function broadcastToRoom($restaurant_id, $message)
    {
        if (!isset(self::$rooms[$restaurant_id])) {
            return;
        }
        foreach (self::$rooms[$restaurant_id] as $conn) {
            if ($conn) {
                $conn->send($message);
            }
        }
    }

    /**
     * 广播订单状态更新
     */
    public static function broadcastOrderUpdate($restaurant_id, $order_data)
    {
        $message = [
            'type' => 'order_update',
            'restaurant_id' => $restaurant_id,
            'data' => $order_data,
            'timestamp' => time()
        ];
        
        self::broadcastToRoom($restaurant_id, json_encode($message));
    }
    
    /**
     * 广播新订单通知
     */
    public static function broadcastNewOrder($restaurant_id, $order_data)
    {
        $message = [
            'type' => 'new_order',
            'restaurant_id' => $restaurant_id,
            'data' => $order_data,
            'timestamp' => time()
        ];
        
        self::broadcastToRoom($restaurant_id, json_encode($message));
    }
    
    /**
     * 广播订单取消通知
     */
    public static function broadcastOrderCancel($restaurant_id, $order_data)
    {
        $message = [
            'type' => 'order_cancel',
            'restaurant_id' => $restaurant_id,
            'data' => $order_data,
            'timestamp' => time()
        ];
        
        self::broadcastToRoom($restaurant_id, json_encode($message));
    }
    
    /**
     * 广播支付成功通知
     */
    public static function broadcastPaymentSuccess($restaurant_id, $payment_data)
    {
        $message = [
            'type' => 'payment_success',
            'restaurant_id' => $restaurant_id,
            'data' => $payment_data,
            'timestamp' => time()
        ];
        
        self::broadcastToRoom($restaurant_id, json_encode($message));
    }
} 