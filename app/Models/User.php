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
        'mobile', 'nickname','avatarurl','gender','password','created_at','motto'
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
     * 修改用户信息
     *
     * @param int $user_id 用户ID
     * @param array $data 修改数据
     * @return array
     */
    public static function editUserDetail(int $user_id,array $data){
        if(!self::where('id',$user_id)->update($data)){
            return [false,'信息修改失败'];
        }

        return [true,'信息修改成功'];
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
