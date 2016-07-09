## RPC
swoole RPC

----------
##环境依赖
> * Swoole 1.8.x+
> * PHP 5.4+
> * Composer

## Install

### Install composer
```
 curl -sS https://getcomposer.org/installer | php
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
;地址
host = "0.0.0.0"
;端口
port = 9501
;运行模式
mode = SWOOLE_PROCESS
;socket类型
sock_type = SWOOLE_SOCK_TCP
;pid存放路径
pid_path = PROJECT_ROOT'/run'

[monitor]
;服务上报地址
host = "127.0.0.1"
;端口
port = 9569
;;socket类型
sock_type = SWOOLE_SOCK_UDP

[swoole]

dispatch_mode = 3
;worker进程数
worker_num = 4
max_request = 0
open_length_check = 1
package_length_type = "N"
package_length_offset = 0
package_max_length = 2000000
task_worker_num = 20
log_file = "/tmp/swoole-server-0.0.0.0_9501.log"
;守护进程改成1
daemonize = 0

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
> * 服务注册/发现，通过扫描redis获取到所有可用服务列表，并生成配置到指定路径
```
 cd RPC/service/server
 php discovery.php start
```

###运行服务
```
 cd RPC/service/server
 php swoole.php start
```

###客户端展示
> * 需要配置服务发现生成的ip地址文件
```
 cd RPC/service/client
 php swoole.php start
```

##使用方法

###客户端(Client)

0. call 下发任务
1. task 下发task任务，适合用于处理逻辑时间长的业务，而不关心结果

```
$client = new \Swoole\Client\SOA();
$client->setServiceList(PROJECT_ROOT . DS . 'client/config/serverlist.ini');
//设置调用的服务ip
//$client->setService('userservice');
$client->setConfig([
    'open_length_check' => true,
    'package_max_length' => 2000000,
    'package_length_type' => 'N',
    'package_body_offset' => 12,
    'package_length_offset' => 0,
]);


$call1 = $client->call('11', ['test1']);
$call2 = $client->call('22', ['test2']);
$call3 = $client->call('33', ['test3']);
$client->resultData();
var_dump($call1->data, $call2->data, $call3->data);

$task_call = $client->task('11', ['test1']);
var_dump($task_call->getTaskResult());
```

