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

    private function getReids(){
        $redis = new \Redis();
        $redis->connect('47.105.180.123', 6379); //连接Redis
        $redis->auth('yd123456'); //密码验证
        $redis->select(3);//选择数据库2


        return $redis;
    }


    public function index(){
        $arr = [1,2,3];

        $redis = $this->getReids();
        $goods = [58=>0,59=>0,60=>0,61=>0,62=>0,63=>0,64=>0,65=>0,66=>0,67=>0,68=>0,69=>0];
        foreach ($goods as $k=>$v){
            $goods[$k] = $redis->lLen("seckill_{$k}_goods_queue");
        }
        dd($goods);

//        $redis->rPush($queue_name, 1);
//        $redis->rPush($queue_name, 1);
//        $redis->rPush($queue_name, 1);
//        exit;


//        for ($i=0;$i<3;$i++){
//            $redis->rPush("seckill_1_goods_queue", 1);
//        }
//        for ($i=0;$i<5;$i++){
//            $redis->rPush("seckill_2_goods_queue", 1);
//        }
//        for ($i=0;$i<2;$i++){
//            $redis->rPush("seckill_3_goods_queue", 1);
//        }
//
//
//        exit;

        $redis = $this->getReids();
        $getGoods = [];
        foreach ($arr as $goods_id){
            $queue_name = "seckill_{$goods_id}_goods_queue";
            if($redis->lPop($queue_name) == 1){
                $getGoods[] = $goods_id;
            }
        }


        if($getGoods){
            $str = implode(',',$getGoods);
            info("秒杀抢购 - 商品ID:{$str}");
        }

        exit;



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
