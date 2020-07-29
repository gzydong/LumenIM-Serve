<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class UsersFriends extends Model
{
    /**
     * 关联到模型的数据表
     *
     * @var string
     */
    protected $table = 'users_friends';

    /**
     * 不能被批量赋值的属性
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * 可以被批量赋值的属性.
     *
     * @var array
     */
    protected $fillable = ['user1', 'user2', 'active', 'status', 'agree_time', 'created_at', 'user1_remark', 'user2_remark'];

    /**
     * 表明模型是否应该被打上时间戳
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * 获取用户所有好友
     *
     * @param int $uid 用户ID
     * @return mixed
     */
    public static function getUserFriends(int $uid)
    {
        $prefix = DB::getConfig('prefix');

        $sql = <<<SQL
            SELECT users.id,users.nickname,users.avatar,users.motto,users.gender,tmp_table.friend_remark from {$prefix}users users
            INNER join
            (
              SELECT id as rid,user2 as uid,user1_remark as friend_remark from {$prefix}users_friends where user1 = {$uid} and `status` = 1
                UNION all 
              SELECT id as rid,user1 as uid,user2_remark as friend_remark from {$prefix}users_friends where user2 = {$uid} and `status` = 1
            ) tmp_table on tmp_table.uid = users.id  order by tmp_table.rid desc
SQL;

        return DB::select($sql);
    }

    /**
     * 判断用户之间是否存在好友关系
     *
     * @param int $user_id1 用户1
     * @param int $user_id2 用户2
     * @return bool
     */
    public static function checkFriends(int $user_id1, int $user_id2)
    {
        return self::where('user1', $user_id1 < $user_id2 ? $user_id1 : $user_id2)->where('user2', $user_id1 < $user_id2 ? $user_id2 : $user_id1)->where('status', 1)->exists();
    }

    /**
     * 获取指定用户的所有朋友的用户ID
     *
     * @param int $user_id 指定用户ID
     * @return array
     */
    public static function getFriendIds(int $user_id)
    {
        $prefix = DB::getConfig('prefix');
        $sql = "SELECT user2 as uid from {$prefix}users_friends where user1 = {$user_id} and `status` = 1 UNION all SELECT user1 as uid from {$prefix}users_friends where user2 = {$user_id} and `status` = 1";
        return array_map(function ($item) {
            return $item->uid;
        }, DB::select($sql));
    }
}
