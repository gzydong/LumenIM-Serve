# Lumen IM 即时聊天系统

本次项目是用采用 Lumen 框架，并使用了 Composer laravel-swoole 组件进行开发、利用 laravel-swoole 组件中 websocket 服务开发开发即时消息通讯。

##### 项目环境依赖
- PHP7.2+
- Swoole4.4.4+
- Redis 3.2+


#####  项目部署
1. 使用git下载项目源码:  git clone git@github.com:gzydong/lumenim.git lumenim
2. 切换到项目根目录 执行 composer install  安装项目composer依赖包
3. 执行 chmod -R 755 storage 赋予storage目录权限
4. 执行 php artisan lumenim:swoole start 启动项目

##### 设置Nginx代理

swoole在官网也提到过：swoole_http_server对Http协议的支持并不完整，建议仅作为应用服务器。并且在前端增加Nginx作为代理。
那么，我们就增加需要配置nginx.conf里的server：
```
server {
    listen 80;
    server_name your.domain.com;
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

        proxy_pass http://127.0.0.1:1215$suffix;
    }
}
```
