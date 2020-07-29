# Lumen IM 即时聊天系统(服务端)
## 1、简介
Lumen IM 是一个网页版在线即时聊天项目，前端使用 Element-ui + Vue ，后端使用 PHP+Swoole 进行开发。项目后端采用 Lumen 框架，并结合了 Composer laravel-swoole 组件、让 Luemn 框架支持Swoole运行环境。

- 基于Swoole WebSocket服务做消息即时推送
- 支持私聊及群聊
- 支持聊天消息类型有文本、代码块、图片及其它类型文件，并支持文件下载
- 支持聊天消息撤回、删除或批量删除、转发消息（逐条转发、合并转发）
- 支持编写个人笔记、支持笔记分享(好友或群)

## 2、项目Demo
- 地址： [http://im.gzydong.club](http://im.gzydong.club)
- 账号： 18798272054 或 18798272055
- 密码： admin123

## 3、环境要求
- PHP7.2+
- MySQL5.7+
- Swoole4.4.5+
- Redis 3.2+

## 4、项目安装
#### 4.1 下载及安装依赖包
请确保已安装 composer 管理工具
```shell
## 首先，需要将源码包克隆到本地：
git clone git@github.com:gzydong/LumenIM-Serve.git lumen-im-serve

## 切换项目根目录,安装项目composer依耐包 ：
composer install

## 赋予storage目录权限:
chmod -R 755 storage
```

#### 4.2 初始化创建数据库
配置 mysql 连接信息
```bash
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=xxxxxxx
DB_PREFIX=lar_
DB_USERNAME=root
DB_PASSWORD=passsword
```
初始化数据库之前请确保上面配置参数正确，否则安装失败
```bash
# 执行数据库安装命令
php artisan lumen-im:install
```

#### 4.3 项目相关参数配置

###### 4.3.1 配置上传文件保存路径
修改config目录下filesystems.php 文件，自定义文件上传路径
```php
<?php
return [
    'disks' => [
        'uploads' => [
            'driver' => 'local',

	     # 文件保存目录建议不要放在项目目录中
             # 这里配置文件上传保存的绝对路径，需要单独配置域名指向这个文件夹
            'root' => '/xxx/xxx/yourpath/'
        ]
    ]
];
```

###### 4.3.2 配置图片访问域名
修改config目录下config.php 文件，设置文件图片访问的域名
```php
<?php
return [
    //设置文件图片访问的域名
    'file_domain'=>'http://img.yourdomain.com',
];
```

## 5、项目部署nginx配置
#### 5.1 服务端
```php
map $http_upgrade $connection_upgrade {
    default upgrade;
    ''      close;
}

upstream websocket {
    # swoole websocket 服务
    server 127.0.0.1:9501;
}

server {
    listen       80;
    server_name  www.yourdomain.com;

    # 项目根目录下的puclic文件夹
    root /to/project/puclic;
    index  index.php;

    # 匹配 websocket url 设置代理 
    location = /socket.io {
        proxy_pass http://websocket;
        proxy_http_version 1.1;
        proxy_connect_timeout 10s;               #配置点1
        proxy_read_timeout 30s;                  #配置点2，如果没效，可以考虑这个时间配置长一点
        proxy_send_timeout 20s;                  #配置点3   

        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Real-PORT $remote_port;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header Host $http_host;
        proxy_set_header Scheme $scheme;
        proxy_set_header Server-Protocol $server_protocol;
        proxy_set_header Server-Name $server_name;
        proxy_set_header Server-Addr $server_addr;
        proxy_set_header Server-Port $server_port;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection $connection_upgrade;
    }

    # 路由重写
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP 代理
    location ~ \.php$ {
        fastcgi_pass   127.0.0.1:9000;
        fastcgi_index  index.php;
        fastcgi_param  SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include        fastcgi_params;
    }

    # 文件缓存
    location ~ .*\.(gif|jpg|jpeg|png|bmp|swf|flv|ico)$ {
        expires 7d;
        access_log off;
    }
}
```

#### 5.2 图片站点
```php
server {
    listen 80;
    # 图片访问域名与项目配置文件中的域名一致
    server_name  img.yourdomain.com;
    index  index.html;
    
    # 默认禁止访问
    location / {
        deny all;
    }

    # 只允许 访问 images 文件夹下的文件 
    location ^~ /images/{
        # 设置目录别名
        alias /xxx/xxx/yourpath/images/;
        
        # 设置网站图片防盗链
        valid_referers none blocked *.gzydong.club;
        if ($invalid_referer) {
            return 403;
        }

        # 设置缓存时间(3天)
        expires 3d;

        # 关闭访问日志
        access_log off;
    }
}
```
#### 5.3 启动 WebSocket 服务 
5.3.1 配置说明

在 .env 文件中有以下关于swoole的常用的环境变量 
```bash
# WebSocket 服务绑定的IP地址(0.0.0.0:任何IP都能访问 、127.0.0.1:只能本地主机访问)
SWOOLE_HTTP_HOST=0.0.0.0

# 服务端口号
SWOOLE_HTTP_PORT=9501

# 是否开启 websocket 服务（这里必须为true,项目中是基于websocket 推送聊天消息） 
SWOOLE_HTTP_WEBSOCKET=true

# 是否是后台运行（默认后台运行，开发环境中false更方便与开发调试）
SWOOLE_HTTP_DAEMONIZE=true
```
Swoole其它相关配置请查看配置文件夹下 swoole_http.php、swoole_websocket.php文件

启动服务
```bash
php artisan lumen-im:swoole start
```
其它相关命令
```bash
php artisan lumen-im:swoole stop     # 停止服务
php artisan lumen-im:swoole restart  # 重启服务
php artisan lumen-im:swoole reload   # 平滑重启
php artisan lumen-im:swoole infos    # 查看Swoole服务相关信息


[root@127.0.0.1 lumenim]# php artisan lumen-im:swoole infos
+-----------------+-------------------------------------------+
| Name            | Value                                     |
+-----------------+-------------------------------------------+
| PHP Version     | 7.2.12                                    |
| Swoole Version  | 4.4.12                                    |
| Laravel Version | Lumen (5.7.8) (Laravel Components 5.7.*)  |
| Listen IP       | 0.0.0.0                                   |
| Listen Port     | 9501                                      |
| Server Status   | Online                                    |
| Reactor Num     | 1                                         |
| Worker Num      | 1                                         |
| Task Worker Num | 1                                         |
| Websocket Mode  | On                                        |
| Master PID      | 6613                                      |
| Manager PID     | 6614                                      |
| Log Path        | /www/lumenim/storage/logs/swoole_http.log |
+-----------------+-------------------------------------------+ 
```

## 前端资源
有关前端的相关源码请移步至 [https://github.com/gzydong/LumenIM](https://github.com/gzydong/LumenIM)


项目中若有不足的地方，请多多指教。
