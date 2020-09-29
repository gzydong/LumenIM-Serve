<?php
namespace App\Helpers;

use App\Support\Curl;

class RequestProxy
{

    public function send(string $url,$data = []){
        $host = config('config.proxy.host');
        $port = config('config.proxy.port');
        $curl = new Curl();
        $curl->post("http://{$host}:{$port}/{$url}", $data);
        $response = $curl->getBody();
        if(!$response){
            return false;
        }

        return json_decode($response,true);
    }
}
