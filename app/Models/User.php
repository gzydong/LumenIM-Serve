<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Laravel\Lumen\Auth\Authorizable;

/**
 * Class User
 * @package App\Models
 *
 */
class User extends BaseModel implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable;

    // 性别状态:未知
    const GENDER_UNKNOWN = 0;

    // 性别状态:男
    const GENDER_MAN = 1;

    // 性别状态:女
    const GENDER_WOMAN = 2;

    /**
     * 关联到模型的数据表
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * 可批量赋值属性
     *
     * @var array
     */
    protected $fillable = [
        'mobile',
        'nickname',
        'avatar',
        'gender',
        'password',
        'invite_code',
        'motto',
        'email',
        'created_at',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];
}
