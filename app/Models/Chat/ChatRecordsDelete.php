<?php

namespace App\Models\Chat;

use App\Models\BaseModel;
class ChatRecordsDelete extends BaseModel
{
    /**
     * 关联到模型的数据表
     *
     * @var string
     */
    protected $table = 'chat_records_delete';

    /**
     * 不能被批量赋值的属性
     *
     * @var array
     */
    protected $guarded = ['id'];
}
