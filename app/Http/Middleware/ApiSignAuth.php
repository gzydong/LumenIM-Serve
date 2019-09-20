<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;

/**
 * 接口签名验证中间件
 *
 * @https://www.awaimai.com/2061.html  前端签名方式请查看这篇文章
 * Class ApiSignAuth
 * @package App\Http\Middleware
 */
class ApiSignAuth
{

    /**
     * 不需要签名验证的url白名单 (例如: api/index/get-home-page)
     *
     * @var array
     */
    protected $except = [

    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $this->except = array_merge($this->except,config('conf.signature.except',[]));

        if(!in_array($request->path(),$this->except) &&  !$this->check($request)){
            return response()->json(['code' => 403,'msg' => '非法访问:签名验证失败!']);
        }

        return $next($request);
    }

    /**
     * 签名验证
     * @param Request $request
     * @return bool
     */
    public function check(Request $request){
        if(!$request->hasHeader('Signature') || empty($request->header('Signature'))){
            return false;
        }

        //说明：这里需要把JavaScript加密的数据稍作处理，把空格替换成+，否则会有乱码
        $signData = base64_decode(str_replace(' ', '+',$request->header('Signature')));
        if(is_null(json_decode($signData))){
            return false;
        }

        $signData = json_decode($signData,true);

        if(!array_has($signData,['appid','platform','time','sign'])){
            return false;
        }

        $platform = config('config.signature.platform',[]);
        if(!array_key_exists($signData['platform'],$platform)){
            return false;
        }

        if($platform[$signData['platform']]['appid'] !== $signData['appid']){
            return false;
        }

        //此处添加时间判断
        $requestTime = intval($signData['time']);
        if(($requestTime > (time() + 60*5)) || $requestTime < (time() - 60*5)){
            return false;
        }

        return $signData['sign'] == $this->makeSignature($signData,$platform[$signData['platform']]['secretkey']);
    }

    /**
     * 生成签名
     *
     * @param array $args        签名参数
     * @param string $key        签名私钥
     * @return string
     */
    private function makeSignature(array $args,string $key)
    {
        if(isset($args['sign'])) {
            unset($args['sign']);
        }

        ksort($args);

        $requestString = '';
        foreach($args as $k => $v) {
            $requestString .= $k . '=' . urlencode($v);
        }

        return hash_hmac("md5",strtolower($requestString) , $key);
    }
}