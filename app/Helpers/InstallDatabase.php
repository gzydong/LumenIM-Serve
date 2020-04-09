<?php
namespace App\Helpers;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InstallDatabase
{

    /**
     * 安装数据库表
     */
    public function init(){
        if (!Schema::hasTable('article')) {
            Schema::create('article', function(Blueprint $table) {
                $table->unsignedInteger('id',true)->comment('文章ID');
                $table->unsignedInteger('user_id')->default(0)->comment('用户ID');
                $table->unsignedInteger('class_id')->default(0)->comment('分类ID');
                $table->string('tags_id', 20)->default('')->comment('笔记关联标签');
                $table->string('title', 80)->default('')->charset('utf8mb4')->comment('文章标题');
                $table->string('abstract', 200)->default('')->charset('utf8mb4')->comment('文章摘要');
                $table->string('image', 255)->default('')->comment('文章首图');
                $table->unsignedTinyInteger('is_asterisk')->default(0)->comment('是否星标文章(0:否  1:是)');
                $table->unsignedTinyInteger('status')->default(1)->comment('笔记状态 1:正常 2:已删除');

                $table->dateTime('created_at')->comment('添加时间');
                $table->dateTime('updated_at')->comment('最后一次更新时间');
                $table->dateTime('deleted_at')->comment('笔记删除时间');

                //创建索引
                $table->index(['user_id', 'class_id','title'],'idx_user_id_class_id_title');

                $table->charset = 'utf8';
                $table->collation = 'utf8_general_ci';
                $table->engine = 'InnoDB';
            });

            DB::statement("ALTER TABLE `lar_article` comment '用户文章表'");
        }

        if (!Schema::hasTable('article_annex')) {
            Schema::create('article_annex', function(Blueprint $table) {
                $table->unsignedBigInteger('id',true)->comment('文件ID');
                $table->unsignedInteger('user_id')->unsigned()->comment('上传文件的用户ID');
                $table->unsignedInteger('article_id')->default(0)->comment('笔记ID');
                $table->string('file_suffix', 10)->default('')->comment('文件后缀名');
                $table->bigInteger('file_size')->default(0)->unsigned()->comment('文件大小（单位字节）');
                $table->string('save_dir', 500)->nullable()->comment('文件保存地址（相对地址）');
                $table->string('original_name', 100)->nullable()->comment('原文件名');
                $table->tinyInteger('status')->default(1)->unsigned()->comment('附件状态 1:正常 2:已删除');
                $table->dateTime('created_at')->comment('附件上传时间');
                $table->dateTime('deleted_at')->comment('附件删除时间');

                $table->charset = 'utf8';
                $table->collation = 'utf8_general_ci';
                $table->engine = 'InnoDB';

                $table->index(['user_id', 'article_id'],'idx_user_id_article_id');
            });
            DB::statement("ALTER TABLE `lar_article_annex` comment '文章附件信息表'");
        }

        if (!Schema::hasTable('article_class')) {
            Schema::create('article_class', function(Blueprint $table) {
                $table->unsignedInteger('id',true)->comment('文章分类ID');
                $table->unsignedInteger('user_id')->default(0)->comment('用户ID');
                $table->string('class_name', 20)->default('')->comment('分类名');
                $table->unsignedTinyInteger('sort')->default(0)->comment('排序');
                $table->unsignedTinyInteger('is_default')->default(0)->comment('默认分类1:是 0:不是');
                $table->unsignedInteger('created_at')->default(0)->comment('创建时间');

                $table->charset = 'utf8';
                $table->collation = 'utf8_general_ci';
                $table->engine = 'InnoDB';

                $table->index(['user_id', 'sort'],'idx_user_id_sort');
            });
            DB::statement("ALTER TABLE `lar_article_class` comment '文章分类表'");
        }

        if (!Schema::hasTable('article_detail')) {
            Schema::create('article_detail', function(Blueprint $table) {
                $table->unsignedInteger('id',true)->comment('文章详情ID');
                $table->unsignedInteger('article_id')->unique();
                $table->longtext('md_content')->charset('utf8mb4')->comment('Markdown 内容');
                $table->longtext('content')->charset('utf8mb4')->comment('Markdown 解析HTML内容');

                $table->charset = 'utf8';
                $table->collation = 'utf8_general_ci';
                $table->engine = 'InnoDB';
            });
            DB::statement("ALTER TABLE `lar_article_detail` comment '文章详情表'");
        }

        if (!Schema::hasTable('article_tags')) {
            Schema::create('article_tags', function(Blueprint $table) {
                $table->unsignedInteger('id',true)->comment('文章标签ID');
                $table->unsignedInteger('user_id')->default(0)->comment('用户ID');
                $table->string('tag_name', 20)->default('')->comment('标签名');
                $table->unsignedInteger('created_at')->default(0)->comment('创建时间');

                $table->charset = 'utf8';
                $table->collation = 'utf8_general_ci';
                $table->engine = 'InnoDB';
                $table->index(['user_id'],'idx_user_id');
            });

            DB::statement("ALTER TABLE `lar_article_tags` comment '文章标签表'");
        }

        if (!Schema::hasTable('emoticon')) {
            Schema::create('emoticon', function(Blueprint $table) {
                $table->unsignedInteger('id',true)->comment('表情分组ID');
                $table->string('name', 255)->default('')->comment('表情分组名称');
                $table->string('url', 255)->default();
                $table->unsignedInteger('created_at')->default(0)->comment('创建时间');

                $table->charset = 'utf8';
                $table->collation = 'utf8_general_ci';
            });
            DB::statement("ALTER TABLE `lar_emoticon` comment '表情包'");
        }

        if (!Schema::hasTable('emoticon_details')) {
            Schema::create('emoticon_details', function(Blueprint $table) {
                $table->unsignedInteger('id',true)->comment('表情包ID');
                $table->unsignedInteger('emoticon_id')->comment('表情分组ID');
                $table->string('describe', 20)->default('')->comment('表情关键字描述');
                $table->string('url', 255)->default('')->comment('表情链接');
                $table->string('file_suffix', 10)->default('')->comment('文件后缀名');
                $table->unsignedBigInteger('file_size')->default(0)->comment('文件大小（单位字节）');
                $table->unsignedInteger('created_at')->default(0)->comment('添加时间');

                $table->charset = 'utf8';
                $table->collation = 'utf8_general_ci';
            });
            DB::statement("ALTER TABLE `lar_emoticon_details` comment '聊天表情包'");
        }

        if (!Schema::hasTable('file_split_upload')) {
            Schema::create('file_split_upload', function(Blueprint $table) {
                $table->unsignedInteger('id',true)->comment('临时文件ID');
                $table->unsignedTinyInteger('file_type')->default(2)->comment('1:合并文件  2:拆分文件');
                $table->unsignedInteger('user_id')->default(0)->comment('上传的用户ID');
                $table->string('hash_name', 30)->default('')->comment('临时文件hash名');
                $table->string('original_name', 100)->default('')->comment('原文件名');
                $table->unsignedTinyInteger('split_index')->default(0)->comment('当前索引块');
                $table->unsignedTinyInteger('split_num')->default(0)->comment('总上传索引块');
                $table->string('save_dir', 255)->default('')->comment('文件的临时保存路径');
                $table->string('file_ext', 10)->default('')->comment('文件后缀名');
                $table->unsignedInteger('file_size')->default(0)->comment('临时文件大小');
                $table->unsignedTinyInteger('is_delete')->default(0)->comment('文件是否已被删除(1:是 0:否)');
                $table->unsignedInteger('upload_at')->comment('文件上传时间');

                $table->charset = 'utf8';
                $table->collation = 'utf8_general_ci';
            });
            DB::statement("ALTER TABLE `lar_file_split_upload` comment '文件拆分上传'");
        }

        if (!Schema::hasTable('user_login_log')) {
            Schema::create('user_login_log', function(Blueprint $table) {
                $table->unsignedInteger('id',true)->comment('登录日志ID');
                $table->unsignedInteger('user_id')->default(0)->comment('用户ID');
                $table->unsignedInteger('ip')->unsigned()->comment('登录地址IP');
                $table->dateTime('created_at')->comment('登录时间');

                $table->charset = 'utf8';
                $table->collation = 'utf8_general_ci';
            });

            DB::statement("ALTER TABLE `lar_user_login_log` comment '用户登录日志表'");
        }

        if (!Schema::hasTable('users')) {
            Schema::create('users', function(Blueprint $table) {
                $table->unsignedInteger('id',true)->comment('用户ID');
                $table->string('mobile', 11)->default('')->unique()->comment('手机号');
                $table->string('nickname', 20)->default('')->comment('用户昵称');
                $table->string('avatarurl', 255)->default('')->comment('用户头像地址');
                $table->unsignedTinyInteger('gender')->default(0)->unsigned()->comment('用户性别  0:未知  1:男   2:女');
                $table->string('password', 255)->default('')->comment('用户密码');
                $table->string('invite_code', 6)->default('')->comment('邀请码');
                $table->string('motto', 100)->default('')->comment('用户座右铭');
                $table->string('email', 30)->default('')->comment('用户邮箱');
                $table->dateTime('created_at')->nullable()->comment('注册时间');

                $table->charset = 'utf8';
                $table->collation = 'utf8_general_ci';
                $table->engine = 'InnoDB';


            });
            DB::statement("ALTER TABLE `lar_users` comment '用户表'");
        }

        if (!Schema::hasTable('users_chat_files')){
            Schema::create('users_chat_files', function(Blueprint $table) {
                $table->unsignedInteger('id',true)->comment('聊天文件ID');
                $table->unsignedInteger('user_id')->default(0)->comment('用户ID');
                $table->tinyInteger('flie_source')->default(1)->unsigned()->comment('文件来源(1:用户上传 2:表情包 )');
                $table->tinyInteger('file_type')->default(1)->unsigned()->comment('消息类型  1:图片   2:视频   3:文件');
                $table->string('file_suffix', 10)->default('')->comment('文件后缀名');
                $table->unsignedBigInteger('file_size')->default(0)->comment('文件大小（单位字节）');
                $table->string('save_dir', 500)->default('')->comment('文件保存地址（相对地址）');
                $table->string('original_name', 100)->default('')->comment('原文件名');
                $table->dateTime('created_at')->comment('创建时间');

                $table->charset = 'utf8';
                $table->collation = 'utf8_general_ci';
                $table->engine = 'InnoDB';
            });

            DB::statement("ALTER TABLE `lar_users_chat_files` comment '用户聊天文件记录表'");
        }

        if (!Schema::hasTable('users_chat_list')){
            Schema::create('users_chat_list', function(Blueprint $table) {
                $table->unsignedInteger('id',true)->comment('聊天列表ID');
                $table->unsignedTinyInteger('type')->default(1)->comment('聊天类型  1:好友  2:群聊');
                $table->unsignedInteger('uid')->default(0)->comment('用户ID');
                $table->unsignedInteger('friend_id')->default(0)->comment('朋友的用户ID');
                $table->unsignedInteger('group_id')->default(0)->comment('聊天分组ID');
                $table->unsignedInteger('status')->default(1)->default(1)->comment('状态 1:正常 0:已删除');
                $table->dateTime('created_at')->comment('创建时间');
                $table->dateTime('updated_at')->comment('更新时间');

                $table->charset = 'utf8';
                $table->collation = 'utf8_general_ci';
                $table->engine = 'InnoDB';

                $table->index(['uid', 'type','friend_id','group_id'],'idx_uid_type_friend_id_group_id');
            });
            DB::statement("ALTER TABLE `lar_users_chat_list` comment '用户聊天列表'");
        }

        if (!Schema::hasTable('users_chat_records')){
            Schema::create('users_chat_records', function(Blueprint $table) {
                $table->unsignedInteger('id',true)->comment('聊天记录ID');
                $table->tinyInteger('source')->unsigned()->default(1)->comment('消息来源  1:好友消息 2:群聊消息');
                $table->tinyInteger('msg_type')->unsigned()->default(1)->comment('消息类型 (1:文本消息   2:文件消息   3:系统提示好友入群消息  4:系统提示好友退群消息)');
                $table->unsignedInteger('user_id')->default(0)->comment('发送消息的用户ID（0:代表系统消息）');
                $table->unsignedInteger('receive_id')->default(0)->comment('接收消息的用户ID或群聊ID');
                $table->unsignedInteger('file_id')->default(0)->comment('聊天文件ID或表情包ID');
                $table->text('content')->charset('utf8mb4')->comment('文本消息');
                $table->dateTime('send_time')->comment('发送消息的时间');

                $table->charset = 'utf8';
                $table->collation = 'utf8_general_ci';
                $table->engine = 'InnoDB';

                $table->index(['file_id', 'msg_type'],'idx_file_id_msg_type');
                $table->index(['user_id', 'receive_id','send_time'],'idx_user_id_receive_id_send_time');
            });
            DB::statement("ALTER TABLE `lar_users_chat_records` comment '用户聊天记录表'");
        }

        if (!Schema::hasTable('users_emoticon')){
            Schema::create('users_emoticon', function(Blueprint $table) {
                $table->unsignedInteger('id',true)->comment('表情包收藏ID');
                $table->unsignedInteger('user_id')->default(0)->unique()->comment('用户ID');
                $table->string('emoticon_ids', 255)->default('')->comment('表情包ID');

                $table->charset = 'utf8';
                $table->collation = 'utf8_general_ci';
            });
            DB::statement("ALTER TABLE `lar_users_emoticon` comment '用户收藏表情包'");
        }

        if (!Schema::hasTable('users_friends')){
            Schema::create('users_friends', function(Blueprint $table) {
                $table->unsignedInteger('id',true)->comment('关系ID');
                $table->unsignedInteger('user1')->default(0)->comment('用户1(user1 一定比 user2 小)');
                $table->unsignedInteger('user2')->default(0)->comment('用户2(user1 一定比 user2 小)');
                $table->string('user1_remark', 20)->default('')->comment('好友备注');
                $table->string('user2_remark', 20)->default('')->comment('好友备注');
                $table->unsignedTinyInteger('active')->default(1)->default(1)->comment('主动邀请方  1:user1   2:user2');
                $table->unsignedTinyInteger('status')->default(1)->comment('好友状态  1: 好友状态  0:已解除好友关系');
                $table->dateTime('agree_time')->comment('成为好友时间');
                $table->dateTime('created_at')->comment('创建时间');

                $table->charset = 'utf8';
                $table->collation = 'utf8_general_ci';
                $table->engine = 'InnoDB';

                $table->index(['user1', 'user2','user1_remark'],'idx_user1_active_status');
            });
            DB::statement("ALTER TABLE `lar_users_friends` comment '用户好友关系表'");
        }

        if (!Schema::hasTable('users_friends_apply')){
            Schema::create('users_friends_apply', function(Blueprint $table) {
                $table->unsignedInteger('id',true)->comment('好友申请ID');
                $table->unsignedInteger('user_id')->default(0)->comment('申请人ID');
                $table->unsignedInteger('friend_id')->default(0)->comment('被申请人');
                $table->unsignedTinyInteger('status')->default(0)->comment('申请状态(0:等待处理  1:已同意  2:已拒绝)');
                $table->string('remarks', 50)->default('')->comment('申请人备注信息');
                $table->string('reason', 30)->default('')->comment('拒绝理由');
                $table->dateTime('created_at')->nullable()->comment('申请时间');
                $table->dateTime('updated_at')->nullable()->comment('处理时间');

                $table->charset = 'utf8';
                $table->collation = 'utf8_general_ci';
                $table->engine = 'InnoDB';

                $table->index(['user_id'],'idx_user_id');
                $table->index(['friend_id'],'idx_friend_id');
            });
            DB::statement("ALTER TABLE `lar_users_friends_apply` comment '用户添加好友申请表'");
        }

        if (!Schema::hasTable('users_group')){
            Schema::create('users_group', function(Blueprint $table) {
                $table->unsignedInteger('id',true)->comment('用户组ID');
                $table->unsignedInteger('user_id')->default(0)->comment('用户ID');
                $table->string('group_name', 20)->default('')->charset('utf8mb4')->comment('群名称');
                $table->string('group_profile', 100)->default('')->comment('群介绍');
                $table->unsignedSmallInteger('people_num')->default(1)->comment('群人数');
                $table->tinyInteger('status')->default(0)->comment('群状态 0:正常 1:已解散');
                $table->string('avatarurl', 255)->default('')->comment('群头像');
                $table->dateTime('created_at')->nullable()->comment('创建时间');
            });
            DB::statement("ALTER TABLE `lar_users_group` comment '用户聊天群'");
        }

        if (!Schema::hasTable('users_group_member')){
            Schema::create('users_group_member', function(Blueprint $table) {
                $table->unsignedInteger('id',true)->comment('用户组成员ID');
                $table->unsignedInteger('group_id')->default(0)->comment('用户组ID');
                $table->unsignedInteger('user_id')->default(0)->comment('用户ID');
                $table->tinyInteger('group_owner')->nullable()->comment('是否为群主 0:否  1:是');
                $table->tinyInteger('status')->default(0)->comment('退群状态  0: 正常状态  1:已退群');
                $table->string('visit_card', 20)->default('')->comment('用户群名片');
                $table->unsignedTinyInteger('not_disturb')->default(0)->comment('消息免打扰 0:正常接收 1:接收但不提醒');
                $table->dateTime('created_at')->nullable()->comment('入群时间');

                $table->charset = 'utf8';
                $table->collation = 'utf8_general_ci';
                $table->engine = 'InnoDB';

                $table->index(['group_id','user_id','status'],'idx_group_id_uid_status');
            });
            DB::statement("ALTER TABLE `lar_users_group_member` comment '群聊成员'");
        }
    }
}
