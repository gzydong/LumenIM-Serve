<?php
namespace App\Helpers;


/**
 * Class Curl 基础的curl 请求类
 *
 * @method array getHeaders() 获取请求header信息
 * @method int getTimeout() 获取设置超时时间
 * @method bool getIsAjax() 获取是否设置isAjax
 * @method bool getIsJson() 获取是否设置isJson
 * @method mixed getReferer() 获取设置的referer信息
 * @method bool getSslVerify() 获取是否设置sslVerify
 * @method string getSslCertFile() 获取设置的sslCertFile文件地址
 * @method string getSslKeyFile() 获取设置的sslKeyFile文件地址
 * @method int|mixed getError() 获取错误编号
 * @method null|string getErrorInfo() 获取错误信息
 * @method mixed getBody() 获取请求结果
 * @method array getOptions() 获取curl设置的配置项
 * @method int getRetryNumber() 获取重试的次数
 * @method array getCurlOptions() 获取curl的附加配置
 * @method Curl setTimeout(int $time) 设置超时时间
 * @method Curl setIsAjax(boolean $isAjax) 设置是否为ajax请求
 * @method Curl setIsJson(boolean $isJson) 设置是否为json请求(header 添加json, 请求数组会转json)
 * @method Curl setReferer(mixed $referer) 在HTTP请求头中"Referer: "的内容
 * @method Curl setSslVerify(boolean $sslVerify) 设置是否设置sslVerify
 * @method Curl setSslCertFile(string $certFile) 设置的sslCertFile文件地址
 * @method Curl setSslKeyFile(string $keyFile) 设置的sslKeyFile文件地址
 * @method Curl setCurlOptions(array $options) 设置的curl的附加配置，优先级最高
 * @link https://github.com/myloveGy/curl
 * @package Verypay\Utils
 */
class Curl
{
    /**
     * @var array 请求header
     */
    private $headers = [];

    /**
     * @var int 超时时间
     */
    private $timeout = 5;

    /**
     * @var bool 是否AJAX 请求
     */
    private $isAjax = false;

    /**
     * @var bool 是否 json 请求
     */
    private $isJson = false;

    /**
     * @var null
     */
    private $referer = null;

    /**
     * @var bool 开启ssl
     */
    private $sslVerify = false;

    /**
     * @var string ssl cert 文件地址
     */
    private $sslCertFile = '';

    /**
     * @var string ssl key 文件地址
     */
    private $sslKeyFile = '';

    /**
     * @var curl
     */
    private $ch;

    /**
     * @var string|int|mixed 错误编号
     */
    private $error;

    /**
     * @var string|mixed 错误信息
     */
    private $errorInfo;

    /**
     * @var string|mixed 响应数据
     */
    private $body;

    /**
     * @var curl 句柄信息
     */
    private $info;

    /**
     * @var string 请求地址
     */
    private $url;

    /**
     * @var string 请求方法
     */
    private $method;

    /**
     * @var array|mixed 请求数据
     */
    private $requestData;

    /**
     * @var int 重试次数
     */
    private $retryNumber = 0;

    /**
     * @var array 不允许赋值的属性
     */
    private $guarded = [
        'ch', 'error', 'errorInfo',      // curl 相关
        'body', 'info',                  // 响应相关
        'url', 'method', 'requestData',  // 请求相关
        'retryNumber', 'guarded', '_defaultOptions',
    ];

    /**
     * @var array 默认配置属性
     */
    private $_defaultOptions = [
        CURLOPT_USERAGENT => 'Mozilla/4.0+(compatible;+MSIE+6.0;+Windows+NT+5.1;+SV1)',   // 用户访问代理 User-Agent
        CURLOPT_HEADER => 0,
        CURLOPT_FOLLOWLOCATION => 0, // 跟踪301
        CURLOPT_RETURNTRANSFER => 1, // 返回结果
        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4, // 默认使用IPV4
    ];

    /**
     * @var array 配置项
     */
    private $options = [];

    /**
     * @var array curl 额外参数，优先级最高
     */
    private $curlOptions = [];

    /**
     * 允许在初始化的时候设置属性信息
     *
     * Curl constructor.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        foreach ($config as $attribute => $value) {
            // 特殊属性不允许设置
            if (in_array($attribute, $this->guarded, true)) {
                continue;
            }

            // 存在的属性，设置
            if (property_exists($this, $attribute)) {
                $this->$attribute = $value;
            }
        }

        // 没有设置配置选项，那么使用默认的配置属性
        if (empty($this->options)) {
            $this->options = $this->_defaultOptions;
        }
    }

    /**
     * 发送get 请求
     *
     * @param string $url 请求地址
     * @param array $params 请求参数
     *
     * @return $this
     */
    public function get($url, $params = [])
    {
        // 拼接请求参数
        if (!empty($params)) {
            $params = is_array($params) ? http_build_query($params) : $params;
            // 判断不存在?
            if (strrpos($url, '?') === false) {
                $url .= '?';
            } else if (!in_array(substr($url, -1, 1), ['?', '&'], true)) {
                $url .= '&';
            }

            $url .= $params;
        }

        return $this->request($url);
    }

    /**
     * 发送post 请求
     *
     * @param string $url 请求地址
     * @param array|string $data 请求数据
     *
     * @return $this
     */
    public function post($url, $data)
    {
        return $this->request($url, 'POST', $data);
    }

    /**
     * 发送 DELETE 请求
     *
     * @param string $url 请求地址
     *
     * @return $this
     */
    public function delete($url)
    {
        return $this->request($url, 'DELETE');
    }

    /**
     * 发送PUT 请求
     *
     * @param string $url 请求地址
     * @param array $data 请求数据
     *
     * @return $this
     */
    public function put($url, $data)
    {
        return $this->request($url, 'PUT', $data);
    }

    /**
     * 重试次数 $this->get()->retry(2)
     *
     * @param integer $num 重试次数
     * @param bool $emptyRetry 响应结果为空是否重试
     * @param int $milliseconds 响应错误暂停多少毫秒
     *
     * @return $this
     */
    public function retry($num, $emptyRetry = false, $milliseconds = 0)
    {
        $this->retryNumber = 0;
        // 存在错误或者响应为空也要重试，并且重试次数 > 0, 重新发送请求
        while (($this->error || ($emptyRetry && empty($this->body))) && $this->retryNumber < $num) {

            // 设置了暂停时间
            if ($milliseconds > 0) {
                usleep($milliseconds * 1000);
            }

            // 重新发送请求
            $this->request($this->url, $this->method, $this->requestData);
            $this->retryNumber++;
        }

        return $this;
    }

    /**
     * 自定义重试条件重试
     *
     * @param int $num 重试次数
     * @param callable $when 自定义调用结构
     * @param int $milliseconds 响应错误暂停多少毫秒
     *
     * @return $this
     */
    public function whenRetry($num, $when, $milliseconds = 0)
    {
        $this->retryNumber = 0;

        // 不是可调用结构
        if (!is_callable($when)) {
            return $this;
        }

        // 存在错误或者响应为空也要重试，并且重试次数 > 0, 重新发送请求
        while ($when($this) && $this->retryNumber < $num) {

            // 设置了暂停时间
            if ($milliseconds > 0) {
                usleep($milliseconds * 1000);
            }

            // 重新发送请求
            $this->request($this->url, $this->method, $this->requestData);
            $this->retryNumber++;
        }

        return $this;
    }

    /**
     * 批量发送请求
     *
     * @param array $urls 请求地址
     *
     * @return array
     */
    public function multi($urls)
    {
        $mh = curl_multi_init();
        $conn = $contents = [];

        // 初始化
        foreach ($urls as $i => $url) {
            $conn[$i] = curl_init($url);
            $this->defaultOptions($conn[$i], $url);
            curl_multi_add_handle($mh, $conn[$i]);
        }

        // 执行
        do {
            curl_multi_exec($mh, $active);
        } while ($active);

        foreach ($urls as $i => $url) {
            $contents[$i] = curl_multi_getcontent($conn[$i]);
            curl_multi_remove_handle($mh, $conn[$i]);
            curl_close($conn[$i]);
        }

        // 结束清理
        curl_multi_close($mh);
        return $contents;
    }

    /**
     * 发送请求信息
     *
     * @param string $url 请求地址
     * @param string $method 请求方法
     * @param string|array $data 请求数据
     *
     * @return $this
     */
    public function request($url, $method = 'GET', $data = '')
    {
        if (!$url) {
            throw new \RuntimeException('CURL url is null:' . __FILE__);
        }

        // 初始化CURL
        $method = strtoupper($method);
        $this->ch = curl_init();            // CURL
        $this->options[CURLOPT_CUSTOMREQUEST] = $method;                // 请求方法

        // 存在数据
        if ($data) {
            // 数组的话、需要转为字符串
            if (is_array($data)) {
                $postFields = $this->isJson ? json_encode($data, 320) : http_build_query($data);
            } else {
                $postFields = $data;
            }

            $this->options[CURLOPT_POSTFIELDS] = $postFields;
        }

        // 一次性设置
        $this->defaultOptions($this->ch, $url);

        // 赋值
        $this->url = $url;
        $this->method = $method;
        $this->requestData = $data;
        $this->body = curl_exec($this->ch);
        $this->error = curl_errno($this->ch);
        $this->errorInfo = curl_error($this->ch);
        $this->info = curl_getinfo($this->ch);

        if (is_resource($this->ch)) {
            curl_close($this->ch);
        }

        return $this;
    }

    /**
     * 设置请求头信息
     *
     * @param array|string $headers 设置的信息
     *
     * @return Curl
     */
    public function setHeaders($headers)
    {
        if (!is_array($headers)) {
            $headers = func_get_args();
        }

        foreach ($headers as $header) {
            if (in_array($header, $this->headers, true)) {
                continue;
            }

            $this->headers[] = $header;
        }

        return $this;
    }

    /**
     * 设置选项
     *
     * @param string|array $options 设置项
     * @param null|mixed $value
     *
     * @return Curl
     */
    public function setOptions($options, $value = null)
    {
        if (!is_array($options)) {
            $options = [$options => $value];
        }

        // 设置选项
        foreach ($options as $option => $value) {
            $this->curlOptions[$option] = $value;
        }

        return $this;
    }

    /**
     * 设置SSL文件
     *
     * @param string $certFile 证书文件
     * @param string $keyFile 秘钥文件
     *
     * @return $this
     */
    public function setSSLFile($certFile, $keyFile)
    {
        $this->sslVerify = true;
        if (is_file($certFile)) {
            $this->sslCertFile = $certFile;
        }

        if (is_file($keyFile)) {
            $this->sslKeyFile = $keyFile;
        }

        return $this;
    }

    /**
     * 获取curl info 信息
     *
     * @param null $key 获取的字段信息
     *
     * @return mixed|null
     */
    public function getInfo($key = null)
    {
        if ($key !== null) {
            return isset($this->info[$key]) ? $this->info[$key] : null;
        }

        return $this->info;
    }

    /**
     * 获取状态码
     *
     * @return mixed
     */
    public function getStatusCode()
    {
        return $this->getInfo('http_code');
    }

    /**
     * 获取请求时间
     *
     * @param string $timeKey
     *
     * @return mixed
     */
    public function getRequestTime($timeKey = 'total_time')
    {
        return $this->getInfo($timeKey);
    }

    /**
     * 获取整个请求的数组信息
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'url' => $this->url,
            'method' => $this->method,
            'request_data' => $this->requestData,
            'body' => $this->body,
            'error' => $this->error,
            'error_info' => $this->errorInfo,
            'info' => $this->info,
        ];
    }

    /**
     * 运行方法
     *
     * @param $name
     * @param $arguments
     *
     * @return $this
     */
    public function __call($name, $arguments)
    {
        // 方法前缀和属性名称
        $prefix = substr($name, 0, 3);
        $attribute = lcfirst(substr($name, 3));

        // 存在属性才处理
        if (property_exists($this, $attribute)) {
            // 获取指定属性直接返回
            if ($prefix === 'get') {
                return $this->$attribute;
            } elseif ($prefix === 'set' && !in_array($attribute, $this->guarded)) {
                $this->$attribute = $arguments[0];
                return $this;
            }
        }

        throw new \RuntimeException('Curl does not exist method: ' . $name);
    }

    /**
     * 对象属性重置
     *
     * @return Curl
     */
    public function reset()
    {
        $this->headers = [];
        $this->timeout = 5;
        $this->isAjax = false;
        $this->isJson = false;
        $this->referer = null;
        $this->sslVerify = false;
        $this->sslCertFile = '';
        $this->sslKeyFile = '';
        $this->ch = null;
        $this->error = 0;
        $this->errorInfo = '';
        $this->body = null;
        $this->info = [];
        $this->url = '';
        $this->method = null;
        $this->requestData = null;
        $this->retryNumber = 0;
        $this->options = $this->_defaultOptions;
        $this->curlOptions = [];
        return $this;
    }

    /**
     * 设置默认选项
     *
     * @param resource $ch curl 资源
     * @param string $url 请求地址
     */
    private function defaultOptions($ch, $url)
    {
        // 设置 referer
        if ($this->referer) {
            $this->options[CURLOPT_REFERER] = $this->referer;
        }

        $this->options[CURLOPT_URL] = $url;           // 设置访问的url地址
        $this->options[CURLOPT_TIMEOUT] = $this->timeout; // 设置超时

        // Https 关闭 ssl 验证
        if (substr($url, 0, 5) == 'https') {
            $this->options[CURLOPT_SSL_VERIFYPEER] = false;
            $this->options[CURLOPT_SSL_VERIFYHOST] = false;
        }

        // 设置ajax
        if ($this->isAjax) {
            $this->setHeaders('X-Requested-With: XMLHttpRequest', 'X-Prototype-Version: 1.5.0');
        }

        // 设置 json 请求
        if ($this->isJson) {
            $this->setHeaders('Content-Type: application/json');
        }

        // 设置证书 使用证书：cert 与 key 分别属于两个.pem文件
        if ($this->sslVerify && $this->sslCertFile && $this->sslKeyFile) {
            // 默认格式为PEM，可以注释
            $this->options[CURLOPT_SSLCERTTYPE] = 'PEM';
            $this->options[CURLOPT_SSLCERT] = $this->sslCertFile;

            // 默认格式为PEM，可以注释
            $this->options[CURLOPT_SSLKEYTYPE] = 'PEM';
            $this->options[CURLOPT_SSLKEY] = $this->sslKeyFile;
        }

        // 设置HTTP header 信息
        if ($this->headers) {
            $this->options[CURLOPT_HTTPHEADER] = $this->headers;
        }

        // 存在curlOptions
        if ($this->curlOptions) {
            foreach ($this->curlOptions as $option => $value) {
                $this->options[$option] = $value;
            }
        }

        // 一次性设置属性
        curl_setopt_array($ch, $this->options);
    }
}
