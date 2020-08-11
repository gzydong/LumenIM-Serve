<?php


namespace App\Helpers;

use App\Helpers\Curl;

class MobileInfo
{

    public static function info(string $mobile){

        $curl = new Curl();
        $curl->get("http://apis.juhe.cn/mobile/get",[
            'key'=>'dbb0f3ac61c102c78465596a8e3f3abe',
            'phone'=>$mobile,
            'dtype'=>'json'
        ]);

        $response = $curl->getBody();
        if(!$response){
            return [];
        }

        $data = json_decode($response,true);
        if($data['resultcode'] == '200' && $data['error_code'] == 0){
            $email = '';
            if ($data['result']['company'] == '电信') {
                $email = $mobile . '@189.com';
            } else if ($data['result']['company'] == '移动') {
                $email = $mobile . '@139.com';
            } else if ($data['result']['company'] == '联通') {
                $email = $mobile . '@wo.cn';
            }

            $data['result']['email'] = $email;
            return $data['result'];
        }

        return [];
    }

}
