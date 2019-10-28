<?php
namespace App\Http\Controllers\Api;

use App\Logic\ChatLogic;
use Illuminate\Http\Request;
use App\Logic\UsersLogic;
use Illuminate\Support\Facades\DB;


/**
 * 测试控制器
 * Class TestController
 * @package App\Http\Controllers\Api
 */
class TestController
{
    public function index(){
        $arr = [1,2];

        dd(time());
        dd(date('Y-m-d '));
        DB::beginTransaction();
        try{
            $total = 0;
            $list = [];
            foreach ($arr as $gid){
                $goods = DB::table('six_goods')->where('id',$gid)->first();
                if($goods && $goods->num > 0){
                    try{
                        if(DB::table('six_goods')->where('id',$gid)->decrement('num')){
                            $total += $goods->front_money;
                            $list[] = $goods->id;
                        }
                    }catch (\Exception $exception){}
                }
            }

            if($list){
                $ordersn = date('Ymd') . substr(implode(null, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
                DB::table('test_order')->insert([
                    'order_no'=>$ordersn,
                    'sid'=>implode('-',$list),
                    'paytotal'=>$total
                ]);
            }

            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();
        }
    }
}
