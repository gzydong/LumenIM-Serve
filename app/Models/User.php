<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Auth\Authorizable;

use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Model implements AuthenticatableContract, AuthorizableContract ,JWTSubject
{
    use Authenticatable, Authorizable;

    /**
     * 关联到模型的数据表
     *
     * @var string
     */
    protected $table = 'users';


    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'mobile', 'nickname','avatarurl','gender','password','created_at'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * 表明模型是否应该被打上时间戳
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * 判断用户ID是否存在
     *
     * @param int $user_id 用户ID
     * @return bool
     */
    public static function checkUserExist(int $user_id){
        return self::where('id',$user_id)->exists() ? true :false;
    }

    /**
     * 用户修改密码
     *
     * @param int $user_id  用户ID
     * @param string $old_password  旧密码
     * @param $new_password  新密码
     * @return array
     */
    public static function changePassword(int $user_id,string $old_password,$new_password){
        $info = self::select(['id','password'])->where('id',$user_id)->first();
        if(!$info){
            return [false,'用户不存在'];
        }

        if(!Hash::check($old_password,$info->password)){
            return [false,'旧密码验证失败'];
        }

        if(!self::where('id',$user_id)->update(['password'=>Hash::make($new_password)])){
            return [false,'密码修改失败'];
        }

        return [true,'密码修改成功'];
    }

    /**
     * 修改用户昵称
     *
     * @param int $user_id 用户ID
     * @param string $nickname 昵称
     * @return array
     */
    public static function editNickname(int $user_id,string $nickname){
        if(!self::where('id',$user_id)->update(['nickname'=>$nickname])){
            return [false,'昵称修改失败'];
        }

        return [true,'昵称修改成功'];
    }

    /**
     * 修改用户头像
     *
     * @param int $user_id 用户ID
     * @param string $img 头像地址
     * @return array
     */
    public static function editHeadPortrait(int $user_id,string $img){
        if(!self::where('id',$user_id)->update(['avatarurl'=>$img])){
            return [false,'头像修改失败'];
        }

        return [true,'头像修改成功'];
    }
}
