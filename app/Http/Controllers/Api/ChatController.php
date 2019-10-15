<?php
namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

use App\Models\UsersFriends;

set_time_limit(0);


use App\Models\UsersChatRecords;
use App\Models\UsersChatRecordsMsg;
class ChatController extends CController
{
    public function getChatRecords(Request $request){
        $rows = UsersFriends::select('user1','user2')->limit(3000)->get()->toarray();
        foreach ($rows as $row){
            $info = UsersChatRecords::create([
                'source'=>1,
                'msg_type'=>'1',
                'user_id'=>$row['user1'],
                'receive_id'=>$row['user2'],
                'send_time'=>date('Y-m-d H:i:s'),
                'text_msg'=>mt_rand(5000,50000)
            ]);

            unset($info);
            unset($row);
        }
    }
}
