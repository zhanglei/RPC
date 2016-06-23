## swoole_yaf_rpc
swoole结合yaf的rpc  可部署多个端口作为不同服务

----------
##环境依赖
> * Swoole 1.8.x+
> * PHP 5.4+
> * Yaf 2.3.x+
> * Composer

## Install

### Install composer
```
 curl -sS https://getcomposer.org/installer | php
```

### Install Yaf
```
cd yaf
phpize
./configure --with-php-config=/path/to/php-config
make && make install
```

### Install swoole
```
cd swoole-src
phpize
./configure
make && make install
```
----------

#快速开始
```
 composer install
```
##运行服务指令
```
 start | stop | reload | restart | help
```

###运行用户服务
```
 cd swoole_yaf_rpc/user_service/server
 php swoole.php start
```

###运行信息服务
```
 cd swoole_yaf_rpc/message_service/server
 php swoole.php start
```

###客户端展示
```
 curl http://localhost/swoole_yaf_rpc/client/public/index.php
```

##使用方法

###客户端(Client)

0. syncRequest 同步下发任务阻塞等待结果返回
1. asyncRequest 异步下发任务，成功返回guid(异步任务唯一标示)，可以在后续调用getAsyncData 获取所有下发的异步结果

```
 $user_info = \Rpc\Swoole::instance()->UserService()->asyncRequest('/index/user', ['php' => 'hello']);
 var_dump($user_info);

 $message_info = \Rpc\Swoole::instance()->MessageService()->syncRequest('/index/message/');
 var_dump($message_info);

 var_dump(\Rpc\Swoole::instance()->getAsyncData());
```

