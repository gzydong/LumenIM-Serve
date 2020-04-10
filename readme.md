# Lumen IM 即时聊天系统
本次项目是用采用 Lumen 框架，并结合了 Composer laravel-swoole 组件、利用 laravel-swoole 组件中 websocket 服务开发开发即时消息通讯。


##### 项目环境依赖
- PHP7.2+
- Swoole4.4.4+
- Redis 3.2+


##### 项目安装

```shell
## 首先，需要将源码包克隆到本地：
git clone git@github.com:gzydong/lumenim.git lumen-im

## 切换项目根目录,安装项目composer依耐包 ：
composer install

## 赋予storage目录权限:
chmod -R 755 storage

##执行项目安装命令：
php artisan lumenim:install
```

###### 启动Swoole服务：
```shell
php artisan lumenim:swoole start
```
---
#### 项目相关参数配置

###### 配置文件上传保存路径：
修改config目录下filesystems.php 文件，自定义文件上传路径
```php
<?php
return [
    'disks' => [
        'uploads' => [
            'driver' => 'local',
             ## 这里配置文件上传保存的绝对路径，需要单独配置域名指向这个文件夹
            'root' => '/xxx/xxx/yourpath/' 
        ]
    ]
];
```

###### 配置文件访问域名：
修改config目录下config.php 文件，设置文件图片访问的域名
```php
<?php
return [
    //设置文件图片访问的域名
    'file_domain'=>'http://img.yourdomain.com'
];
```

---

##### 设置Nginx代理Swoole服务
swoole在官网也提到过：swoole_http_server对Http协议的支持并不完整，建议仅作为应用服务器。并且在前端增加Nginx作为代理。
那么，我们就增加需要配置nginx.conf里的server：
```shell
server {
    listen 80;
    server_name www.yourdomain.com;
    root /path/to/laravel/public;
    index index.php;

    location = /index.php {
        # Ensure that there is no such file named "not_exists"
        # in your "public" directory.
        try_files /not_exists @swoole;
    }

    location / {
        try_files $uri $uri/ @swoole;
    }

    location @swoole {
        set $suffix "";

        if ($uri = /index.php) {
            set $suffix "/";
        }

        proxy_set_header Host $host;
        proxy_set_header SERVER_PORT $server_port;
        proxy_set_header REMOTE_ADDR $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;

        # IF https
        # proxy_set_header HTTPS "on";

        proxy_pass http://127.0.0.1:9501$suffix;
    }
}
```

##### 设置文件域名地址
```shell
server {
    listen 80;
    server_name img.yourdomain.com;
    ### 此目录是文件上传的保存目录
    root /xxx/xxx/yourpath/;
    index  index.html;
     
    #禁止访问非图片文件
    location /files/ {
       deny all;
    }

    #禁止访问上传临时文件
    location /tmp/ {
       deny all;
    }

    location ~ .*\.(gif|jpg|jpeg|png|bmp|swf|flv|ico)$ {
      expires 30d;
      access_log on;
    }
}
```
---

### 前端资源
- 有关前端的相关源码请移步至 https://github.com/gzydong/lumenim-web

