<?php

namespace App\Helpers;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Console\Command;

class InstallDatabase
{
    public $command;

    // 本次安装数据表数
    public $installNum = 0;

    // 数据表总数
    public $tableNum = 22;

    public function __construct(Command $command)
    {
        $this->command = $command;
    }

    /**
     * 安装数据库表
     */
    public function init()
    {
        $this->command->line('初始化安装数据表中,请稍等...');

        $prefix = DB::getConfig('prefix');

        if (!Schema::hasTable('article')) {
            Schema::create('article', function (Blueprint $table) {
                $table->unsignedInteger('id', true)->comment('文章ID');
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

                $table->charset = 'utf8';
                $table->collation = 'utf8_general_ci';
                $table->engine = 'InnoDB';

                //创建索引
                $table->index(['user_id', 'class_id', 'title'], 'idx_user_id_class_id_title');
            });

            DB::statement("ALTER TABLE `{$prefix}article` comment '用户文章表'");
            $this->command->info("{$prefix}article 数据表安装成功...");
            $this->installNum++;
        }

        if (!Schema::hasTable('article_annex')) {
            Schema::create('article_annex', function (Blueprint $table) {
                $table->unsignedBigInteger('id', true)->comment('文件ID');
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

                $table->index(['user_id', 'article_id'], 'idx_user_id_article_id');
            });
            DB::statement("ALTER TABLE `{$prefix}article_annex` comment '文章附件信息表'");
            $this->command->info("{$prefix}article_annex 数据表安装成功...");
            $this->installNum++;
        }

        if (!Schema::hasTable('article_class')) {
            Schema::create('article_class', function (Blueprint $table) {
                $table->unsignedInteger('id', true)->comment('文章分类ID');
                $table->unsignedInteger('user_id')->default(0)->comment('用户ID');
                $table->string('class_name', 20)->default('')->comment('分类名');
                $table->unsignedTinyInteger('sort')->default(0)->comment('排序');
                $table->unsignedTinyInteger('is_default')->default(0)->comment('默认分类1:是 0:不是');
                $table->unsignedInteger('created_at')->default(0)->comment('创建时间');

                $table->charset = 'utf8';
                $table->collation = 'utf8_general_ci';
                $table->engine = 'InnoDB';

                $table->index(['user_id', 'sort'], 'idx_user_id_sort');
            });
            DB::statement("ALTER TABLE `{$prefix}article_class` comment '文章分类表'");
            $this->command->info("{$prefix}article_class 数据表安装成功...");
            $this->installNum++;
        }

        if (!Schema::hasTable('article_detail')) {
            Schema::create('article_detail', function (Blueprint $table) {
                $table->unsignedInteger('id', true)->comment('文章详情ID');
                $table->unsignedInteger('article_id')->nullable(false)->comment('文章ID');
                $table->longtext('md_content')->charset('utf8mb4')->comment('Markdown 内容');
                $table->longtext('content')->charset('utf8mb4')->comment('Markdown 解析HTML内容');

                $table->charset = 'utf8';
                $table->collation = 'utf8_general_ci';
                $table->engine = 'InnoDB';

                $table->unique('article_id', 'unique_article_id');
            });
            DB::statement("ALTER TABLE `{$prefix}article_detail` comment '文章详情表'");

            $this->command->info("{$prefix}article_detail 数据表安装成功...");
            $this->installNum++;
        }

        if (!Schema::hasTable('article_tags')) {
            Schema::create('article_tags', function (Blueprint $table) {
                $table->unsignedInteger('id', true)->comment('文章标签ID');
                $table->unsignedInteger('user_id')->default(0)->comment('用户ID');
                $table->string('tag_name', 20)->default('')->comment('标签名');
                $table->unsignedTinyInteger('sort')->default(0)->comment('排序');
                $table->unsignedInteger('created_at')->default(0)->comment('创建时间');

                $table->charset = 'utf8';
                $table->collation = 'utf8_general_ci';
                $table->engine = 'InnoDB';
                $table->index(['user_id'], 'idx_user_id');
            });

            DB::statement("ALTER TABLE `{$prefix}article_tags` comment '文章标签表'");
            $this->command->info("{$prefix}article_tags 数据表安装成功...");
            $this->installNum++;
        }

        if (!Schema::hasTable('emoticon')) {
            Schema::create('emoticon', function (Blueprint $table) {
                $table->unsignedInteger('id', true)->comment('表情分组ID');
                $table->string('name', 100)->default('')->comment('表情分组名称');
                $table->string('url', 255)->default('')->comment('图片地址');
                $table->unsignedInteger('created_at')->default(0)->comment('创建时间');

                $table->charset = 'utf8';
                $table->collation = 'utf8_general_ci';
            });

            DB::statement("ALTER TABLE `{$prefix}emoticon` comment '表情包'");
            $this->command->info("{$prefix}emoticon 数据表安装成功...");
            $this->installNum++;
        }

        if (!Schema::hasTable('emoticon_details')) {
            Schema::create('emoticon_details', function (Blueprint $table) {
                $table->unsignedInteger('id', true)->comment('表情包ID');
                $table->unsignedInteger('emoticon_id')->default(0)->comment('表情分组ID');
                $table->unsignedInteger('user_id')->default(0)->comment('用户ID（0：代码系统表情包）');
                $table->string('describe', 20)->default('')->comment('表情关键字描述');
                $table->string('url', 255)->default('')->comment('表情链接');
                $table->string('file_suffix', 10)->default('')->comment('文件后缀名');
                $table->unsignedBigInteger('file_size')->default(0)->comment('文件大小（单位字节）');
                $table->unsignedInteger('created_at')->default(0)->comment('添加时间');

                $table->charset = 'utf8';
                $table->collation = 'utf8_general_ci';
            });

            DB::statement("ALTER TABLE `{$prefix}emoticon_details` comment '聊天表情包'");
            $this->command->info("{$prefix}emoticon_details 数据表安装成功...");
            $this->installNum++;
        }

        if (!Schema::hasTable('file_split_upload')) {
            Schema::create('file_split_upload', function (Blueprint $table) {
                $table->unsignedInteger('id', true)->comment('临时文件ID');
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

                $table->index(['user_id', 'hash_name'], 'idx_user_id_hash_name');
            });
            DB::statement("ALTER TABLE `{$prefix}file_split_upload` comment '文件拆分上传'");

            $this->command->info("{$prefix}file_split_upload 数据表安装成功...");
            $this->installNum++;
        }

        if (!Schema::hasTable('user_login_log')) {
            Schema::create('user_login_log', function (Blueprint $table) {
                $table->unsignedInteger('id', true)->comment('登录日志ID');
                $table->unsignedInteger('user_id')->default(0)->comment('用户ID');
                $table->string('ip', 20)->comment('登录地址IP');
                $table->dateTime('created_at')->comment('登录时间');

                $table->charset = 'utf8';
                $table->collation = 'utf8_general_ci';
            });

            DB::statement("ALTER TABLE `{$prefix}user_login_log` comment '用户登录日志表'");
            $this->command->info("{$prefix}user_login_log 数据表安装成功...");
            $this->installNum++;
        }

        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->unsignedInteger('id', true)->comment('用户ID');
                $table->string('mobile', 11)->default('')->unique()->comment('手机号');
                $table->string('nickname', 20)->default('')->comment('用户昵称');
                $table->string('avatar', 255)->default('')->comment('用户头像地址');
                $table->unsignedTinyInteger('gender')->default(0)->unsigned()->comment('用户性别  0:未知  1:男   2:女');
                $table->string('password', 255)->default('')->comment('用户密码');
                $table->string('invite_code', 6)->default('')->comment('邀请码');
                $table->string('motto', 100)->default('')->comment('用户座右铭');
                $table->string('email', 30)->default('')->comment('用户邮箱');
                $table->dateTime('created_at')->nullable()->comment('注册时间');

                $table->charset = 'utf8';
                $table->collation = 'utf8_general_ci';
                $table->engine = 'InnoDB';

                $table->unique(['mobile'], 'idx_mobile');
            });

            DB::statement("ALTER TABLE `{$prefix}users` comment '用户表'");
            $this->command->info("{$prefix}users 数据表安装成功...");
            $this->installNum++;
        }

        if (!Schema::hasTable('users_chat_files')) {
            Schema::create('users_chat_files', function (Blueprint $table) {
                $table->unsignedInteger('id', true)->comment('文件ID');
                $table->unsignedInteger('user_id')->default(0)->comment('上传文件的用户ID');
                $table->tinyInteger('file_source')->default(1)->unsigned()->comment('文件来源(1:用户上传 2:表情包 )');
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

            DB::statement("ALTER TABLE `{$prefix}users_chat_files` comment '用户聊天文件记录表'");
            $this->command->info("{$prefix}users_chat_files 数据表安装成功...");
            $this->installNum++;
        }

        if (!Schema::hasTable('users_chat_list')) {
            Schema::create('users_chat_list', function (Blueprint $table) {
                $table->unsignedInteger('id', true)->comment('聊天列表ID');
                $table->unsignedTinyInteger('type')->default(1)->comment('聊天类型  1:好友  2:群聊');
                $table->unsignedInteger('uid')->default(0)->comment('用户ID');
                $table->unsignedInteger('friend_id')->default(0)->comment('朋友的用户ID');
                $table->unsignedInteger('group_id')->default(0)->comment('聊天分组ID');
                $table->unsignedInteger('status')->default(1)->default(1)->comment('状态 1:正常 0:已删除');
                $table->unsignedTinyInteger('is_top')->default(0)->comment('是否置顶 0:否  1:是');
                $table->unsignedTinyInteger('not_disturb')->default(0)->comment('是否消息免打扰 0:否  1:是');
                $table->dateTime('created_at')->comment('创建时间');
                $table->dateTime('updated_at')->comment('更新时间');

                $table->charset = 'utf8';
                $table->collation = 'utf8_general_ci';
                $table->engine = 'InnoDB';

                $table->index(['uid', 'friend_id', 'group_id', 'type'], 'idx_uid_type_friend_id_group_id');
            });

            DB::statement("ALTER TABLE `{$prefix}users_chat_list` comment '用户聊天列表'");
            $this->command->info("{$prefix}users_chat_list 数据表安装成功...");
            $this->installNum++;
        }

        if (!Schema::hasTable('users_chat_records')) {
            Schema::create('users_chat_records', function (Blueprint $table) {
                $table->unsignedInteger('id', true)->comment('聊天记录ID');
                $table->tinyInteger('source')->unsigned()->default(1)->comment('消息来源  1:好友消息 2:群聊消息');
                $table->tinyInteger('msg_type')->unsigned()->default(1)->comment('消息类型 (1:文本消息   2:文件消息   3:系统提示好友入群消息 或系统提示好友退群消息  4:会话记录转发) ');
                $table->unsignedInteger('user_id')->default(0)->comment('发送消息的用户ID（0:代表系统消息）');
                $table->unsignedInteger('receive_id')->default(0)->comment('接收消息的用户ID或群聊ID');
                $table->unsignedInteger('file_id')->default(0)->comment('聊天文件ID或表情包ID');
                $table->unsignedInteger('forward_id')->default(0)->comment('消息转发ID');
                $table->text('content')->charset('utf8mb4')->comment('文本消息');
                $table->unsignedInteger('is_code')->default(0)->comment('是否属于代码片段');
                $table->string('code_lang', 20)->default('')->comment('代码片段语言类型');
                $table->tinyInteger('is_revoke')->default(0)->comment('是否撤回消息（0:否 1:是）');
                $table->dateTime('send_time')->comment('发送消息的时间');

                $table->charset = 'utf8';
                $table->collation = 'utf8_general_ci';
                $table->engine = 'InnoDB';

                $table->index(['user_id', 'receive_id'], 'idx_userid_receiveid');
            });

            DB::statement("ALTER TABLE `{$prefix}users_chat_records` comment '用户聊天记录表'");
            $this->command->info("{$prefix}users_chat_records 数据表安装成功...");
            $this->installNum++;
        }

        if (!Schema::hasTable('users_chat_records_del')) {
            Schema::create('users_chat_records_del', function (Blueprint $table) {
                $table->unsignedInteger('id', true)->comment('聊天删除记录ID');
                $table->unsignedInteger('record_id')->default(0)->comment('聊天记录ID');
                $table->unsignedInteger('user_id')->default(0)->comment('用户ID');
                $table->dateTime('created_at')->comment('删除时间');

                $table->charset = 'utf8';
                $table->collation = 'utf8_general_ci';
                $table->engine = 'InnoDB';

                $table->index(['record_id', 'user_id'], 'idx_record_user_id');
            });

            DB::statement("ALTER TABLE `{$prefix}users_chat_records_del` comment '聊天记录删除记录表'");
            $this->command->info("{$prefix}users_chat_records_del 数据表安装成功...");
            $this->installNum++;
        }

        if (!Schema::hasTable('users_chat_records_forward')) {
            Schema::create('users_chat_records_forward', function (Blueprint $table) {
                $table->unsignedInteger('id', true)->comment('合并转发ID');
                $table->unsignedInteger('user_id')->default(0)->comment('转发用户ID');
                $table->string('records_id', 255)->default('')->comment("转发的聊天记录ID，多个用','分割");
                $table->json('text')->default(null)->comment('记录快照');
                $table->dateTime('created_at')->comment('转发时间');

                $table->charset = 'utf8';
                $table->collation = 'utf8_general_ci';
                $table->engine = 'InnoDB';

                $table->index(['user_id', 'records_id'], 'idx_user_id_records_id');
            });

            DB::statement("ALTER TABLE `{$prefix}users_chat_records_forward` comment '用户聊天记录转发信息表'");
            $this->command->info("{$prefix}users_chat_records_forward 数据表安装成功...");
            $this->installNum++;
        }

        if (!Schema::hasTable('users_chat_records_group_notify')) {
            Schema::create('users_chat_records_group_notify', function (Blueprint $table) {
                $table->unsignedInteger('id', true)->comment('入群或退群通知ID');
                $table->unsignedInteger('record_id')->default(0)->comment('消息记录ID');
                $table->tinyInteger('type')->default(1)->comment('通知类型 (1:邀请入群通知  2:踢出群聊通知  3:自动退出群聊)');
                $table->unsignedInteger('operate_user_id')->default(0)->comment('操作人的用户ID(邀请人)');
                $table->string('user_ids', 255)->default('')->comment("用户ID，多个用','分割");

                $table->charset = 'utf8';
                $table->collation = 'utf8_general_ci';
                $table->engine = 'InnoDB';

                $table->index(['record_id'], 'idx_recordid');
            });

            DB::statement("ALTER TABLE `{$prefix}users_chat_records_group_notify` comment '聊天记录入群或退群通知'");
            $this->command->info("{$prefix}users_chat_records_group_notify 数据表安装成功...");
            $this->installNum++;
        }

        if (!Schema::hasTable('users_emoticon')) {
            Schema::create('users_emoticon', function (Blueprint $table) {
                $table->unsignedInteger('id', true)->comment('表情包收藏ID');
                $table->unsignedInteger('user_id')->default(0)->unique()->comment('用户ID');
                $table->string('emoticon_ids', 255)->default('')->comment('表情包ID');

                $table->charset = 'utf8';
                $table->collation = 'utf8_general_ci';

                $table->index(['user_id'], 'idx_user_id');
            });

            DB::statement("ALTER TABLE `{$prefix}users_emoticon` comment '用户收藏表情包'");
            $this->command->info("{$prefix}users_emoticon 数据表安装成功...");
            $this->installNum++;
        }

        if (!Schema::hasTable('users_friends')) {
            Schema::create('users_friends', function (Blueprint $table) {
                $table->unsignedInteger('id', true)->comment('关系ID');
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

                $table->index(['user1', 'user2'], 'idx_user1_user2');
            });

            DB::statement("ALTER TABLE `{$prefix}users_friends` comment '用户好友关系表'");
            $this->command->info("{$prefix}users_friends 数据表安装成功...");
            $this->installNum++;
        }

        if (!Schema::hasTable('users_friends_apply')) {
            Schema::create('users_friends_apply', function (Blueprint $table) {
                $table->unsignedInteger('id', true)->comment('申请ID');
                $table->unsignedInteger('user_id')->default(0)->comment('申请人ID');
                $table->unsignedInteger('friend_id')->default(0)->comment('被申请人');
                $table->unsignedTinyInteger('status')->default(0)->comment('申请状态(0:等待处理  1:已同意');
                $table->string('remarks', 50)->default('')->comment('申请人备注信息');
                $table->dateTime('created_at')->nullable()->comment('申请时间');
                $table->dateTime('updated_at')->nullable()->comment('处理时间');

                $table->charset = 'utf8';
                $table->collation = 'utf8_general_ci';
                $table->engine = 'InnoDB';

                $table->index(['user_id'], 'idx_user_id');
                $table->index(['friend_id'], 'idx_friend_id');
            });

            DB::statement("ALTER TABLE `{$prefix}users_friends_apply` comment '用户添加好友申请表'");
            $this->command->info("{$prefix}users_friends_apply 数据表安装成功...");
            $this->installNum++;
        }

        if (!Schema::hasTable('users_group')) {
            Schema::create('users_group', function (Blueprint $table) {
                $table->unsignedInteger('id', true)->comment('群ID');
                $table->unsignedInteger('user_id')->default(0)->comment('用户ID');
                $table->string('group_name', 30)->default('')->charset('utf8mb4')->comment('群名称');
                $table->string('group_profile', 100)->default('')->comment('群介绍');
                $table->tinyInteger('status')->default(0)->comment('群状态 0:正常 1:已解散');
                $table->string('avatar', 255)->default('')->comment('群头像');
                $table->dateTime('created_at')->nullable()->comment('创建时间');
            });

            DB::statement("ALTER TABLE `{$prefix}users_group` comment '用户聊天群'");
            $this->command->info("{$prefix}users_group 数据表安装成功...");
            $this->installNum++;
        }

        if (!Schema::hasTable('users_group_member')) {
            Schema::create('users_group_member', function (Blueprint $table) {
                $table->unsignedInteger('id', true)->comment('自增ID');
                $table->unsignedInteger('group_id')->default(0)->comment('群ID');
                $table->unsignedInteger('user_id')->default(0)->comment('用户ID');
                $table->tinyInteger('group_owner')->nullable()->comment('是否为群主 0:否  1:是');
                $table->tinyInteger('status')->default(0)->comment('退群状态  0: 正常状态  1:已退群');
                $table->string('visit_card', 20)->default('')->comment('用户群名片');
                $table->dateTime('created_at')->nullable()->comment('入群时间');

                $table->charset = 'utf8';
                $table->collation = 'utf8_general_ci';
                $table->engine = 'InnoDB';

                $table->index(['group_id', 'status'], 'idx_group_id_status');
            });

            DB::statement("ALTER TABLE `{$prefix}users_group_member` comment '群聊成员'");
            $this->command->info("{$prefix}users_group_member 数据表安装成功...");
            $this->installNum++;
        }

        if (!Schema::hasTable('users_group_notice')) {
            Schema::create('users_group_notice', function (Blueprint $table) {
                $table->unsignedInteger('id', true)->comment('公告ID');
                $table->unsignedInteger('group_id')->default(0)->comment('群ID');
                $table->unsignedInteger('user_id')->default(0)->comment('创建者用户ID');
                $table->string('title', 30)->default('')->charset('utf8mb4')->comment('公告标题');
                $table->text('title')->charset('utf8mb4')->comment('公告内容');
                $table->tinyInteger('is_delete')->default(0)->comment('是否删除  0: 否  1:已删除');
                $table->dateTime('created_at')->nullable()->comment('创建时间');
                $table->dateTime('updated_at')->nullable()->comment('更新时间');
                $table->dateTime('updated_at')->nullable()->comment('删除时间');

                $table->charset = 'utf8';
                $table->collation = 'utf8_general_ci';
                $table->engine = 'InnoDB';

                $table->index(['group_id'], 'idx_group_id');
            });

            DB::statement("ALTER TABLE `{$prefix}users_group_notice` comment '群组公告表'");
            $this->command->info("{$prefix}users_group_notice 数据表安装成功...");
            $this->installNum++;
        }

        $this->command->info("\n数据库已安装完成。 共({$this->tableNum})张数据表，本次安装({$this->installNum})张数据表...");
    }
}
