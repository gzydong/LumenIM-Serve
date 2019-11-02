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
    protected $fillable = ['user1','user2','active','status','agree_time','created_at','user1_remark','user2_remark'];

    /**
     * 表明模型是否应该被打上时间戳
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * 获取用户所有好友
     *
     * @param int $uid  用户ID
     * @return mixed
     */
    public static function getUserFriends(int $uid){
        $sql = <<<SQL
            SELECT lar_users.id,lar_users.nickname,lar_users.avatarurl,lar_users.gender,tmp_table.friend_remark from lar_users 
            INNER join
            (
              SELECT user2 as uid,user1_remark as friend_remark from lar_users_friends where user1 = {$uid} and `status` = 1
                UNION all 
              SELECT user1 as uid,user2_remark as friend_remark from lar_users_friends where user2 = {$uid} and `status` = 1
            ) tmp_table on tmp_table.uid = lar_users.id
SQL;

        return DB::select($sql);
    }

    /**
     * 判断用户之间是否存在好友关系
     *
     * @param int $user_id1  用户1
     * @param int $user_id2  用户2
     * @return bool
     */
    public static function checkFriends(int $user_id1,int $user_id2){
        $sql = <<<SQL
            (SELECT user2 as uid from lar_users_friends where user1 = {$user_id1} and user2 = {$user_id2} and `status` = 1 limit 1)
              UNION all
            (SELECT user1 as uid from lar_users_friends where user1 = {$user_id2} and user2 = {$user_id1} and `status` = 1 limit 1)
SQL;

        return DB::select($sql) ? true :false;
    }
}
