<?php

/**
 * 创建YXShop项目脚本
 * 用于快速创建yxshop-admin、yxshop-h5、yxshop-shared项目
 */

class ProjectCreator
{
    private $basePath;
    private $projects = [
        'yxshop-admin' => '后台管理系统',
        'yxshop-h5' => 'H5移动端',
        'yxshop-shared' => '共享组件'
    ];

    public function __construct()
    {
        $this->basePath = dirname(__DIR__);
    }

    /**
     * 创建所有项目
     */
    public function createAll()
    {
        echo "🚀 开始创建YXShop项目...\n\n";

        foreach ($this->projects as $project => $description) {
            echo "📦 创建项目: {$project} ({$description})\n";
            $this->createProject($project);
            echo "✅ {$project} 创建完成\n\n";
        }

        echo "🎉 所有项目创建完成！\n";
        echo "📋 下一步:\n";
        echo "1. 进入各项目目录安装依赖\n";
        echo "2. 配置数据库连接\n";
        echo "3. 运行数据库迁移\n";
        echo "4. 启动各项目服务\n";
    }

    /**
     * 创建单个项目
     */
    private function createProject($projectName)
    {
        $projectPath = $this->basePath . '/' . $projectName;
        
        // 创建项目目录
        if (!is_dir($projectPath)) {
            mkdir($projectPath, 0755, true);
        }

        // 根据项目类型创建不同的结构
        switch ($projectName) {
            case 'yxshop-admin':
                $this->createAdminProject($projectPath);
                break;
            case 'yxshop-h5':
                $this->createH5Project($projectPath);
                break;
            case 'yxshop-shared':
                $this->createSharedProject($projectPath);
                break;
        }
    }

    /**
     * 创建后台管理项目
     */
    private function createAdminProject($projectPath)
    {
        // 创建目录结构
        $dirs = [
            'app/controller',
            'app/middleware',
            'app/view',
            'app/view/layout',
            'app/view/user',
            'app/view/product',
            'app/view/order',
            'app/view/marketing',
            'app/view/system',
            'config',
            'public/admin',
            'public/assets',
            'runtime/logs',
            'runtime/cache'
        ];

        foreach ($dirs as $dir) {
            $fullPath = $projectPath . '/' . $dir;
            if (!is_dir($fullPath)) {
                mkdir($fullPath, 0755, true);
            }
        }

        // 创建composer.json
        $composerJson = [
            'name' => 'yxshop/admin',
            'description' => 'YXShop后台管理系统',
            'type' => 'project',
            'require' => [
                'php' => '>=8.0',
                'workerman/webman' => '^1.5',
                'workerman/webman-framework' => '^1.5',
                'illuminate/database' => '^10.0',
                'illuminate/redis' => '^10.0',
                'monolog/monolog' => '^3.0'
            ],
            'autoload' => [
                'psr-4' => [
                    'app\\' => 'app/',
                    'YxshopShared\\' => '../yxshop-shared/'
                ]
            ]
        ];

        file_put_contents(
            $projectPath . '/composer.json',
            json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        // 创建基础配置文件
        $this->createConfigFiles($projectPath, 'admin');
        
        // 创建基础控制器
        $this->createBaseControllers($projectPath);
        
        // 创建基础视图
        $this->createBaseViews($projectPath);
    }

    /**
     * 创建H5项目
     */
    private function createH5Project($projectPath)
    {
        // 创建目录结构
        $dirs = [
            'app/controller',
            'app/middleware',
            'app/view',
            'app/view/layout',
            'app/view/home',
            'app/view/product',
            'app/view/cart',
            'app/view/order',
            'app/view/user',
            'config',
            'public/h5',
            'public/assets',
            'runtime/logs',
            'runtime/cache'
        ];

        foreach ($dirs as $dir) {
            $fullPath = $projectPath . '/' . $dir;
            if (!is_dir($fullPath)) {
                mkdir($fullPath, 0755, true);
            }
        }

        // 创建composer.json
        $composerJson = [
            'name' => 'yxshop/h5',
            'description' => 'YXShop H5移动端',
            'type' => 'project',
            'require' => [
                'php' => '>=8.0',
                'workerman/webman' => '^1.5',
                'workerman/webman-framework' => '^1.5',
                'illuminate/database' => '^10.0',
                'illuminate/redis' => '^10.0',
                'monolog/monolog' => '^3.0'
            ],
            'autoload' => [
                'psr-4' => [
                    'app\\' => 'app/',
                    'YxshopShared\\' => '../yxshop-shared/'
                ]
            ]
        ];

        file_put_contents(
            $projectPath . '/composer.json',
            json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        // 创建基础配置文件
        $this->createConfigFiles($projectPath, 'h5');
        
        // 创建基础控制器
        $this->createH5Controllers($projectPath);
        
        // 创建基础视图
        $this->createH5Views($projectPath);
    }

    /**
     * 创建共享组件项目
     */
    private function createSharedProject($projectPath)
    {
        // 创建目录结构
        $dirs = [
            'models',
            'services',
            'repositories',
            'utils',
            'config',
            'tests'
        ];

        foreach ($dirs as $dir) {
            $fullPath = $projectPath . '/' . $dir;
            if (!is_dir($fullPath)) {
                mkdir($fullPath, 0755, true);
            }
        }

        // 创建composer.json
        $composerJson = [
            'name' => 'yxshop/shared',
            'description' => 'YXShop共享组件',
            'type' => 'library',
            'require' => [
                'php' => '>=8.0',
                'illuminate/database' => '^10.0',
                'illuminate/redis' => '^10.0'
            ],
            'autoload' => [
                'psr-4' => [
                    'YxshopShared\\' => './'
                ]
            ]
        ];

        file_put_contents(
            $projectPath . '/composer.json',
            json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        // 创建基础服务
        $this->createSharedServices($projectPath);
    }

    /**
     * 创建配置文件
     */
    private function createConfigFiles($projectPath, $type)
    {
        // 创建基础配置
        $config = [
            'app' => [
                'name' => 'YXShop ' . ucfirst($type),
                'debug' => true,
                'timezone' => 'Asia/Shanghai'
            ],
            'database' => [
                'default' => 'mysql',
                'connections' => [
                    'mysql' => [
                        'driver' => 'mysql',
                        'host' => env('DB_HOST', '127.0.0.1'),
                        'port' => env('DB_PORT', 3306),
                        'database' => env('DB_DATABASE', 'yxshop'),
                        'username' => env('DB_USERNAME', 'root'),
                        'password' => env('DB_PASSWORD', ''),
                        'charset' => 'utf8mb4',
                        'collation' => 'utf8mb4_unicode_ci'
                    ]
                ]
            ],
            'redis' => [
                'default' => 'default',
                'connections' => [
                    'default' => [
                        'host' => env('REDIS_HOST', '127.0.0.1'),
                        'port' => env('REDIS_PORT', 6379),
                        'password' => env('REDIS_PASSWORD', ''),
                        'database' => 0
                    ]
                ]
            ]
        ];

        file_put_contents(
            $projectPath . '/config/app.php',
            "<?php\n\nreturn " . var_export($config, true) . ";\n"
        );

        // 创建路由配置
        $routes = $this->getRoutes($type);
        file_put_contents(
            $projectPath . '/config/route.php',
            "<?php\n\nuse Webman\\Route;\n\n" . $routes
        );
    }

    /**
     * 获取路由配置
     */
    private function getRoutes($type)
    {
        switch ($type) {
            case 'admin':
                return "
// 后台管理路由
Route::group('/admin', function () {
    Route::get('/', [app\\controller\\IndexController::class, 'index']);
    Route::get('/login', [app\\controller\\AuthController::class, 'login']);
    Route::post('/login', [app\\controller\\AuthController::class, 'doLogin']);
    Route::get('/logout', [app\\controller\\AuthController::class, 'logout']);
    
    // 用户管理
    Route::group('/user', function () {
        Route::get('/', [app\\controller\\UserController::class, 'index']);
        Route::get('/create', [app\\controller\\UserController::class, 'create']);
        Route::post('/store', [app\\controller\\UserController::class, 'store']);
        Route::get('/{id}', [app\\controller\\UserController::class, 'show']);
        Route::get('/{id}/edit', [app\\controller\\UserController::class, 'edit']);
        Route::put('/{id}', [app\\controller\\UserController::class, 'update']);
        Route::delete('/{id}', [app\\controller\\UserController::class, 'destroy']);
    });
    
    // 商品管理
    Route::group('/product', function () {
        Route::get('/', [app\\controller\\ProductController::class, 'index']);
        Route::get('/create', [app\\controller\\ProductController::class, 'create']);
        Route::post('/store', [app\\controller\\ProductController::class, 'store']);
        Route::get('/{id}', [app\\controller\\ProductController::class, 'show']);
        Route::get('/{id}/edit', [app\\controller\\ProductController::class, 'edit']);
        Route::put('/{id}', [app\\controller\\ProductController::class, 'update']);
        Route::delete('/{id}', [app\\controller\\ProductController::class, 'destroy']);
    });
    
    // 订单管理
    Route::group('/order', function () {
        Route::get('/', [app\\controller\\OrderController::class, 'index']);
        Route::get('/{id}', [app\\controller\\OrderController::class, 'show']);
        Route::put('/{id}/status', [app\\controller\\OrderController::class, 'updateStatus']);
    });
});
";
            case 'h5':
                return "
// H5移动端路由
Route::get('/', [app\\controller\\HomeController::class, 'index']);
Route::get('/product', [app\\controller\\ProductController::class, 'index']);
Route::get('/product/{id}', [app\\controller\\ProductController::class, 'show']);
Route::get('/cart', [app\\controller\\CartController::class, 'index']);
Route::get('/order', [app\\controller\\OrderController::class, 'index']);
Route::get('/order/{id}', [app\\controller\\OrderController::class, 'show']);
Route::get('/user', [app\\controller\\UserController::class, 'index']);
Route::get('/user/profile', [app\\controller\\UserController::class, 'profile']);
";
            default:
                return "// 路由配置\n";
        }
    }

    /**
     * 创建基础控制器
     */
    private function createBaseControllers($projectPath)
    {
        // IndexController
        $indexController = "<?php

namespace app\\controller;

use support\\Request;
use support\\Response;

class IndexController
{
    public function index(Request \$request): Response
    {
        return view('index', [
            'title' => 'YXShop后台管理'
        ]);
    }
}
";
        file_put_contents($projectPath . '/app/controller/IndexController.php', $indexController);

        // AuthController
        $authController = "<?php

namespace app\\controller;

use support\\Request;
use support\\Response;

class AuthController
{
    public function login(Request \$request): Response
    {
        return view('auth.login');
    }
    
    public function doLogin(Request \$request): Response
    {
        // 登录逻辑
        return json(['code' => 0, 'msg' => '登录成功']);
    }
    
    public function logout(Request \$request): Response
    {
        // 登出逻辑
        return redirect('/admin/login');
    }
}
";
        file_put_contents($projectPath . '/app/controller/AuthController.php', $authController);
    }

    /**
     * 创建H5控制器
     */
    private function createH5Controllers($projectPath)
    {
        // HomeController
        $homeController = "<?php

namespace app\\controller;

use support\\Request;
use support\\Response;

class HomeController
{
    public function index(Request \$request): Response
    {
        return view('home.index', [
            'title' => 'YXShop商城'
        ]);
    }
}
";
        file_put_contents($projectPath . '/app/controller/HomeController.php', $homeController);
    }

    /**
     * 创建基础视图
     */
    private function createBaseViews($projectPath)
    {
        // 布局模板
        $layout = "<!DOCTYPE html>
<html lang=\"zh-CN\">
<head>
    <meta charset=\"UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title><?= \$title ?? 'YXShop后台管理' ?></title>
    <link href=\"https://cdn.jsdelivr.net/npm/element-plus@2.4.0/dist/index.css\" rel=\"stylesheet\">
</head>
<body>
    <div id=\"app\">
        <?= \$content ?? '' ?>
    </div>
    <script src=\"https://cdn.jsdelivr.net/npm/vue@3.3.0/dist/vue.global.js\"></script>
    <script src=\"https://cdn.jsdelivr.net/npm/element-plus@2.4.0/dist/index.full.js\"></script>
</body>
</html>";
        file_put_contents($projectPath . '/app/view/layout/admin.html', $layout);
    }

    /**
     * 创建H5视图
     */
    private function createH5Views($projectPath)
    {
        // 布局模板
        $layout = "<!DOCTYPE html>
<html lang=\"zh-CN\">
<head>
    <meta charset=\"UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title><?= \$title ?? 'YXShop商城' ?></title>
    <link href=\"https://cdn.jsdelivr.net/npm/vant@4.6.0/lib/index.css\" rel=\"stylesheet\">
</head>
<body>
    <div id=\"app\">
        <?= \$content ?? '' ?>
    </div>
    <script src=\"https://cdn.jsdelivr.net/npm/vue@3.3.0/dist/vue.global.js\"></script>
    <script src=\"https://cdn.jsdelivr.net/npm/vant@4.6.0/lib/vant.min.js\"></script>
</body>
</html>";
        file_put_contents($projectPath . '/app/view/layout/h5.html', $layout);
    }

    /**
     * 创建共享服务
     */
    private function createSharedServices($projectPath)
    {
        // 基础服务
        $baseService = "<?php

namespace YxshopShared\\Services;

class BaseService
{
    protected \$model;
    
    public function __construct(\$model = null)
    {
        \$this->model = \$model;
    }
    
    public function find(\$id)
    {
        return \$this->model::find(\$id);
    }
    
    public function create(array \$data)
    {
        return \$this->model::create(\$data);
    }
    
    public function update(\$id, array \$data)
    {
        \$model = \$this->find(\$id);
        if (\$model) {
            \$model->update(\$data);
        }
        return \$model;
    }
    
    public function delete(\$id)
    {
        \$model = \$this->find(\$id);
        if (\$model) {
            \$model->delete();
        }
        return \$model;
    }
}
";
        file_put_contents($projectPath . '/services/BaseService.php', $baseService);
    }
}

// 运行脚本
if (php_sapi_name() === 'cli') {
    \$creator = new ProjectCreator();
    \$creator->createAll();
} else {
    echo "请在命令行中运行此脚本\n";
}
