# liars-dice

基于`webman`和`vue`实现的大话骰游戏

### 项目安装

```shell
git clone https://github.com/d2gin/liars-dice
cd ./liars-dice
composer install
```

### 运行项目

#### 后端

默认启动后`8787`是`http`端口，`9778`是`websocket`端口

```shell
php start.php
# windows运行
# php windows.php
```

#### 前端

分别修改开发和生产环境的环境变量文件`.env.development`、`.env.production`

```shell
cd front/
npm run dev
# 或者构建静态资源
# npm run build
```

#### 配置nginx

这不是必需的配置，是为了兼顾传统的nginx架构。

通过以下配置，前端可以使用`localhost`域名同时连接`http`和`websocket`。

1. http代理

    ```conf
    server
    {
        listen 80;
        server_name localhost;
        # 省略其他配置
        location / {
            proxy_pass http://127.0.0.1:8787;
        }
    }
    ```
2. websocket代理

    ```conf
    http
    {
        map $http_upgrade $connection_upgrade {
            default upgrade;
            '' close;
        }
        # 省略其他配置
        server
        {
            listen 80;
            server_name localhost;
            # 省略其他配置
            location /socket.io {
                proxy_pass              http://127.0.0.1:9778;
                proxy_read_timeout 300s;
                proxy_send_timeout 300s;

                proxy_http_version 1.1;
                proxy_set_header Upgrade $http_upgrade;
                proxy_set_header Connection $connection_upgrade;
            }
        }
    }
    ```
#### 其他配置

1. `redis`配置文件中`config/redis.php`填写连接
2. `websocket`进程配置`config/process.php`
3. `webman`的配置可以参考官方文档

### 后端架构

1. webman一共会启动3个网络服务：框架http服务、游戏websocket服务、用于游戏websocket中间代理http服务。
2. 数据存储在redis，是简单的哈希数据，实验性的项目，不考虑使用mysql。
3. 框架http服务通过向代理http服务发送请求，即可实现推送websocket消息。
4. 用了很多实体类，这是数据驱动的基础。

### 游戏规则

开始游戏时，各玩家可以摇骰（系统会自动摇一次），然后自己看骰盅里面的骰子，不让其他人看到。
首局房主先“叫”，按顺序轮到下一家。叫骰起步个数为总玩家数的1倍，如4个人即4个起叫，6个人即6个起叫。
下一家需要在上家“叫”的骰子个数或者骰子点数基础上往上加，直到开骰。输者接受惩罚，游戏重新开始下一局，继承上局“叫骰”顺序。

### 游戏术语

以下是项目中已实现的术语功能

1. 【叫骰】：类似扑克游戏的“出牌”。需要在上家“叫”的骰子个数或者骰子点数基础上往上加。（标准叫法，例：“3个2”，“3个6”）
2. 【叫斋】：通常骰子的“1”点是可以当任何数使用，但是叫了斋，即“1”点便不当任何数使用。（标准叫法，例：“2个3斋”，“3个6斋”）
3. 【飞斋】：又叫破斋，在“斋”的时候，可以通过“飞”去掉“斋”。需要“叫”双倍或者以上的骰子数。（例：上家叫了“2个1斋”，下家就可以叫“4个2飞”，就可以破斋）
4. 【劈】：无视叫骰顺序直接“劈”指定的一名玩家。“劈”了，输方将会扣除分数。

未实现的术语玩法

1. 【反弹】：上家“叫”的骰子个数上加2，可改变原有叫骰顺序。反弹1次输方惩罚加一码起步。反弹2次加2码起步，如此类推。
2. 【反劈】：当被“劈”时，可以选择“反劈”，输者在“劈”的基础上再惩罚翻倍。
3. 【连开】：顾名思义，即连开多家“上家”。连开的人必须是自己的上家及上家的上家。。。最高境界叫“通杀”，即开骰者除自己外所有参加者。一般通杀3家以上，输者惩罚都翻倍，下一局赢者先叫。
4. 【投降】：所谓“投降输一半”，”被“开”者或者被“劈”者。可以选择不开骰“投降”，减半惩罚。也适用被“反劈”者。

### 其他说明

1. 实验性项目，请勿用于生产环境，请勿用于生产环境，请勿用于生产环境。
2. 后续没有计划提交mysql的版本

### 后续版本

1. 投降、开骰等未完成的功能。
2. 脏数据处理。
