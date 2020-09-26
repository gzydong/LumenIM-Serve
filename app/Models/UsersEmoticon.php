<?php

namespace App\Models;

class UsersEmoticon extends BaseModel
{
    /**
     * 关联到模型的数据表
     *
     * @var string
     */
    protected $table = 'users_emoticon';

    /**
     * 不能被批量赋值的属性
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     *
     * @param  string $value
     * @return string
     */
    public function getEmoticonIdsAttribute($value)
    {
        return explode(',', $value);
    }
}

