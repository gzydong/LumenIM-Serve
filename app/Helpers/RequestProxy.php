<?php
namespace App\Helpers;

use App\Helpers\Curl;

class RequestProxy
{

    public function send(string $url,$data = []){
        $host = config('config.swoole_proxy.host');
        $port = config('config.swoole_proxy.port');
        $curl = new Curl();
        $curl->post("http://{$host}:{$port}/{$url}", $data);
        $response = $curl->getBody();
        if(!$response){
            return false;
        }

        return json_decode($response,true);
    }
}
