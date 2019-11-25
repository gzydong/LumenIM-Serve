<?php
namespace App\Exceptions;

use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     * @param Exception $exception
     * @throws Exception
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function render($request, Exception $exception)
    {
        //友好的出输出授权验证异常错误
        if ($exception instanceof \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException) {
//            return response()->json(['code'=>401,'msg'=>$exception->getMessage()]);

//            $preException = $exception->getPrevious();
//            if ($preException instanceof
//                \Tymon\JWTAuth\Exceptions\TokenExpiredException) {
//                return response()->json(['error' => 'TOKEN_EXPIRED']);
//            } else if ($preException instanceof
//                \Tymon\JWTAuth\Exceptions\TokenInvalidException) {
//                return response()->json(['error' => 'TOKEN_INVALID']);
//            } else if ($preException instanceof
//                \Tymon\JWTAuth\Exceptions\TokenBlacklistedException) {
//                return response()->json(['error' => 'TOKEN_BLACKLISTED']);
//            }
//            if ($exception->getMessage() === 'Token not provided') {
//                return response()->json(['error' => 'Token not provided']);
//            }
        }

        return parent::render($request, $exception);
    }
}
