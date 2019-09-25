<?php

namespace App\Logic;

use App\User;

use Illuminate\Support\Facades\Hash;


/**
 * 用户逻辑处理层
 * Class UsersLogic
 * @package App\Logic
 */
class UsersLogic extends Logic
{

    /**
     * 用户注册逻辑
     */
    public function register(array $data){
        try{

            $data['password']    = Hash::make($data['password']);
            $data['created_at']  = date('Y-m-d H:i:s');
            $isTrue = User::create($data);
        }catch (\Exception $e){
            $isTrue = false;
        }

        return $isTrue;
    }
}