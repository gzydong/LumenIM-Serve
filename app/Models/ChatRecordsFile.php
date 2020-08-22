<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatRecordsFile extends Model
{
    /**
     * 关联到模型的数据表
     *
     * @var string
     */
    protected $table = 'chat_records_file';

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
    protected $fillable = ['record_id', 'user_id', 'file_source', 'file_type', 'save_type', 'original_name', 'file_suffix', 'file_size', 'save_dir', 'is_delete', 'created_at'];

    /**
     * 表明模型是否应该被打上时间戳
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * 根据聊天记录ID获取聊天文件信息
     *
     * @param int $record_id 聊天记录ID
     * @param bool $is_cache 是否允许读取缓存（默认允许）
     * @return mixed
     */
    public static function getFileDetail(int $record_id, $is_cache = true)
    {
        $result = self::where('record_id', $record_id)->first([
            'id', 'file_source', 'file_type', 'save_type', 'original_name', 'file_suffix', 'file_size', 'save_dir'
        ]);

        return $result->toArray();
    }
}
