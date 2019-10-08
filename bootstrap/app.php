<?php
require_once __DIR__.'/../vendor/autoload.php';

//6.0 启动方式
//(new Laravel\Lumen\Bootstrap\LoadEnvironmentVariables(
//    dirname(__DIR__)
//))->bootstrap();


//5.7启动方式
try {
    (new Dotenv\Dotenv(dirname(__DIR__)))->load();
} catch (Dotenv\Exception\InvalidPathException $e) {}


//https://learnku.com/laravel/t/9582/new-wheel-php-cors-middleware-to-solve-cross-domain-problems-in-lumen-programs
//https://learnku.com/articles/20051
header('Access-Control-Allow-Origin:*');
header('Access-Control-Allow-Methods:GET,POST,PUT,DELETE');
header('Access-Control-Allow-Headers:Origin, Content-Type, Cookie, Accept, X-CSRF-TOKEN');
header('Access-Control-Allow-Credentials:true');

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

$app = new Laravel\Lumen\Application(
    dirname(__DIR__)
);

$app->configure('config');
$app->configure('database');

$app->withFacades(true,[
    'Tymon\JWTAuth\Facades\JWTAuth'             => 'JWTAuth',
    'Tymon\JWTAuth\Facades\JWTFactory'          => 'JWTFactory'
]);

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

//加载中间件
$app->routeMiddleware([
    'api.auth' => App\Http\Middleware\ApiSignAuth::class,
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

// $app->register(App\Providers\AppServiceProvider::class);
// $app->register(App\Providers\AuthServiceProvider::class);
// $app->register(App\Providers\EventServiceProvider::class);


$app->register(Illuminate\Redis\RedisServiceProvider::class);
$app->register(Tymon\JWTAuth\Providers\LumenServiceProvider::class);
//$app->register(Medz\Cors\Lumen\ServiceProvider::class);

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
$app->router->group([
    'namespace' => 'App\Http\Controllers',
], function ($router) {
    require __DIR__.'/../routes/web.php';
});

//加载接口路由
$app->router->group([
    'prefix'=>'api',
    'namespace' => 'App\Http\Controllers\Api',
], function ($router) {
    require __DIR__.'/../routes/api.php';
});


return $app;
