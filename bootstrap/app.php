<?php
require_once __DIR__.'/../vendor/autoload.php';

try {
    (new Dotenv\Dotenv(dirname(__DIR__)))->load();
} catch (Dotenv\Exception\InvalidPathException $e) {}

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| Here we will load the environment and create the application instance
| that serves as the central piece of this framework. We'll use this
| application as an "IoC" container and router for this framework.
|
*/

$app = new Laravel\Lumen\Application(dirname(__DIR__));

//加载配置文件
$app->configure('config');
$app->configure('database');
$app->configure('cors');
$app->configure('mail');
$app->configure('filesystems');

//允许使用门面
$app->withFacades();

//允许使用ORM Model类
$app->withEloquent();

/*
|--------------------------------------------------------------------------
| Register Container Bindings
|--------------------------------------------------------------------------
|
| Now we will register a few bindings in the service container. We will
| register the exception handler and the console kernel. You may add
| your own bindings here if you like or you can make another file.
|
*/

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

/*
|--------------------------------------------------------------------------
| Register Middleware
|--------------------------------------------------------------------------
|
| Next, we will register the middleware with the application. These can
| be global middleware that run before and after each request into a
| route or middleware that'll be assigned to some specific routes.
|
*/

//加载中间件组
$app->routeMiddleware([
    'jwt'=>\App\Http\Middleware\JwtAuth::class
]);

//加载默认中间件
$app->middleware([
    \Barryvdh\Cors\HandleCors::class
]);


/*
|--------------------------------------------------------------------------
| Register Service Providers
|--------------------------------------------------------------------------
|
| Here we will register all of the application's service providers which
| are used to bind services into the container. Service providers are
| totally optional, so you are not required to uncomment this line.
|
*/

$app->register(App\Providers\AppServiceProvider::class);

$app->register(App\Providers\EventServiceProvider::class);


//注册支持Swoole服务
$app->register(App\Providers\LumenIMServiceProvider::class);

//注册redis服务
$app->register(Illuminate\Redis\RedisServiceProvider::class);

//注册支持跨域服务
$app->register(Barryvdh\Cors\ServiceProvider::class);

//注册邮件服务提供者
$app->register(Illuminate\Mail\MailServiceProvider::class);

/*
|--------------------------------------------------------------------------
| Load The Application Routes
|--------------------------------------------------------------------------
|
| Next we will include the routes file so that they can all be added to
| the application. This will provide all of the URLs the application
| can respond to, as well as the controllers that may handle them.
|
*/

//加载Web路由
$app->router->group(['namespace' => 'App\Http\Controllers'], function ($router) {
    require __DIR__.'/../routes/web.php';
});

//加载接口路由
$app->router->group(['prefix'=>'api','namespace' => 'App\Http\Controllers\Api'], function ($router) {
    require __DIR__.'/../routes/api.php';
});

return $app;
