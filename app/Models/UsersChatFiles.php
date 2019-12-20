<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsersChatFiles extends Model
{
    /**
     * 关联到模型的数据表
     *
     * @var string
     */
    protected $table = 'users_chat_files';

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
    protected $fillable = ['user_id','chat_records_id','file_type','file_suffix','file_size','save_dir','original_name','created_at'];

    /**
     * 表明模型是否应该被打上时间戳
     *
     * @var bool
     */
    public $timestamps = false;


    public static $fileType = [
        1=>['jpg','png','jpeg','gif','webp'],//图片文件类型
        2=>['mp4','mv','wma','rmvb','flash'],//视频文件类型
        3=>[] //其它文件类型
    ];

    /**
     * @param $ext
     * @return int
     */
    public static function getFileType($ext){
        if(in_array($ext,self::$fileType)){
            return 1;
        }

        if(in_array($ext,self::$fileType)){
            return 1;
        }

        return 3;
    }



}
