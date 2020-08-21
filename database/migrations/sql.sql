CREATE TABLE `lar_users_chat_records` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '聊天记录ID',
  `source` tinyint(3) unsigned DEFAULT '1' COMMENT '消息来源  1:好友消息 2:群聊消息',
  `msg_type` tinyint(3) unsigned DEFAULT '1' COMMENT '消息类型 （1:文本消息 2:文件消息 3:入群消息/退群消息  4:会话记录消息  5:代码块消息）',
  `user_id` int(11) unsigned DEFAULT '0' COMMENT '发送者ID（0:代表系统消息 >0 用户ID）',
  `receive_id` int(11) unsigned DEFAULT '0' COMMENT '接收者ID（用户ID 或 群ID）',
  `content` text CHARACTER SET utf8mb4 COMMENT '文本消息',
  `is_revoke` tinyint(3) unsigned DEFAULT '0' COMMENT '是否撤回消息（0:否 1:是）',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_userid_receiveid` (`user_id`,`receive_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=6541 DEFAULT CHARSET=utf8 COMMENT='用户聊天记录表';

CREATE TABLE `lar_users_chat_files` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '文件ID',
  `record_id` bigint(20) unsigned DEFAULT '0' COMMENT '消息记录ID',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '上传文件的用户ID',
  `file_source` tinyint(3) unsigned DEFAULT '1' COMMENT '文件来源（1:用户上传 2:表情包）',
  `file_type` tinyint(3) unsigned DEFAULT '1' COMMENT '文件类型（1:图片 2:音频文件 3:视频文件 4:其它文件 ）',
  `save_type` tinyint(3) unsigned DEFAULT '0' COMMENT '文件保存方式（0:本地 1:第三方[七牛云] ）',
  `original_name` varchar(100) DEFAULT '' COMMENT '原文件名',
  `file_suffix` varchar(10) DEFAULT '' COMMENT '文件后缀名',
  `file_size` bigint(20) unsigned DEFAULT '0' COMMENT '文件大小（单位字节）',
  `save_dir` varchar(500) DEFAULT '' COMMENT '文件保存地址（相对地址/第三方网络地址）',
  `is_delete` tinyint(3) unsigned DEFAULT 0 COMMENT '文件是否已删除 （0:否 1:已删除）',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=547 DEFAULT CHARSET=utf8 COMMENT='用户聊天记录（文件消息）';

CREATE TABLE `lar_users_chat_records_forward` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '合并转发ID',
  `record_id` bigint(20) unsigned DEFAULT '0' COMMENT '消息记录ID',
  `user_id` int(11) unsigned DEFAULT '0' COMMENT '转发用户ID',
  `records_id` varchar(255) DEFAULT '' COMMENT '转发的聊天记录ID （多个用 , 拼接），最多只能30条记录信息',
  `text` json DEFAULT NULL COMMENT '记录快照（避免后端再次查询数据）',
  `created_at` datetime DEFAULT NULL COMMENT '转发时间',
  PRIMARY KEY (`id`),
  KEY `idx_user_id,records_id` (`user_id`,`records_id`)
) ENGINE=InnoDB AUTO_INCREMENT=83 DEFAULT CHARSET=utf8 COMMENT='用户聊天记录（会话记录转发消息）';

CREATE TABLE `lar_users_chat_records_code` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '合并转发ID',
  `record_id` bigint(20) unsigned DEFAULT '0' COMMENT '消息记录ID',
  `user_id` int(11) unsigned DEFAULT '0' COMMENT '转发用户ID',
  `code_lang` varchar(20) DEFAULT '' COMMENT '代码片段语言类型',
  `code` text CHARACTER SET utf8mb4 COMMENT '代码片段内容',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8 COMMENT='用户聊天记录（代码块消息）';

CREATE TABLE `lar_users_chat_records_notify` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '入群或退群通知ID',
  `record_id` bigint(20) unsigned DEFAULT '0' COMMENT '消息记录ID',
  `type` tinyint(3) unsigned DEFAULT '1' COMMENT '通知类型 （1:邀请入群通知  2:踢出群聊通知  3:自动退出群聊）',
  `operate_user_id` int(11) unsigned DEFAULT '0' COMMENT '操作人的用户ID（邀请人OR管理员ID）',
  `user_ids` varchar(255) DEFAULT NULL COMMENT '用户ID，多个用 , 分割',
  PRIMARY KEY (`id`),
  KEY `idx_recordid` (`record_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=89 DEFAULT CHARSET=utf8 COMMENT='用户聊天记录（入群/退群消息）';
