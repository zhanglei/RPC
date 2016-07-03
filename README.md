## RPC
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

##使用
RPC/service/config/swoole.ini 存放swoole运行时配置
```
[server]
;ip
ip = "0.0.0.0"
;端口
port = 9501
;pid存在目录
pid_path = PROJECT_ROOT'/pid'

[swoole]
;
mode = SWOOLE_PROCESS
;
sock_type = SWOOLE_SOCK_TCP
dispatch_mode = 3
;worker进程数
worker_num = 4
reactor_num = 4
package_length_type = N
package_length_offset = 0
package_body_offset = 8
package_max_length = 2000000
task_worker_num = 20
log_file = "/tmp/swoole-server-0.0.0.0_9501.log"
;守护进程改成true
daemonize = true
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

###运行服务监控
> * 服务注册/发现，通过扫描 swooletable/redis 获取到所有可用服务列表，并生成配置到指定路径
```
 cd RPC/service/server
 php monitor.php start
```

###运行服务
```
 cd RPC/service/server
 php swoole.php start
```

###客户端展示
```
 curl http://localhost/RPC/client/public/
```

##使用方法

###客户端(Client)

0. syncRequest 同步下发任务阻塞等待结果返回
1. asyncRequest 异步下发任务，成功返回guid(异步任务唯一标示)，可以在后续调用getAsyncData 获取所有下发的异步结果

```
 //同步调用
 $user_info = \Rpc\Swoole::instance()->UserService()->asyncRequest('/user/index/user', ['php' => 'hello']);
 var_dump($user_info);
 
 //异步调用，返回guid
 $guid = \Rpc\Swoole::instance()->MessageService()->syncRequest('/user/index/test');
 var_dump($guid);
 
 //获取所有异步结果，数组形式key为异步调用的guid
 var_dump(\Rpc\Swoole::instance()->getAsyncData());
```

