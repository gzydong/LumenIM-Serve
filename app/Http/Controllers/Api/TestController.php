<?php

namespace App\Http\Controllers\Api;

use App\Facades\SocketResourceHandle;
use App\Helpers\MobileInfo;
use App\Helpers\RedisLock;
use App\Helpers\SendEmailCode;
use App\Logic\ArticleLogic;
use App\Logic\FriendsLogic;
use App\Logic\GroupLogic;
use App\Models\User;
use App\Models\UsersFriends;

use App\Models\UsersGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Mail;

set_time_limit(0);

/**
 * 测试控制器
 *
 * Class TestController
 * @package App\Http\Controllers\Api
 */
class TestController extends CController
{
    public function test(FriendsLogic $friendsLogic)
    {
        $user_id = 3046;
        $friend_id = 3045;


        if($user_id > $friend_id){
            [$user_id,$friend_id] = [$friend_id,$user_id];
        }



        $result = $friendsLogic->delFriendApply(2054,119);
        dd($result);

        UsersFriends::where(function ($query) use($user_id,$friend_id) {
            $query->where('user1', $user_id)->where('user2', $friend_id);
        })->where(function ($query) use($user_id,$friend_id) {
            $query->where('user1', $user_id)->where('user2', $friend_id);
        });
        exit;
        $aa = $friendsLogic->friendApplyRecords(2054,1, 2, 10);
        dd($aa);
    }

    public function index(Request $request)
    {

    }
}


