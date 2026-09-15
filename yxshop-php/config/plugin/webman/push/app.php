<?php
return [
    'enable'       => true,
    'websocket'    => 'websocket://0.0.0.0:'.env('websocket_port', 3131),
    'api'          => 'http://0.0.0.0:'.env('api_port', 3232),
    'app_key'      => env('PUSH_APP_KEY', ''),
    'app_secret'   => env('PUSH_APP_SECRET', ''),
    'channel_hook' => env('push.channel_hook', 'http://127.0.0.1:'.env('port', 8777).'/plugin/webman/push/hook'),
    'auth'         => env('push.auth', '/plugin/webman/push/auth')
];