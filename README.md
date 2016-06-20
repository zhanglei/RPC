# swoole_yaf_rpc
swoole结合yaf的rpc

## Install
### Install composer
```
 curl -sS https://getcomposer.org/installer | php
 composer install
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

##启动用户服务
```
 cd swoole_yaf_rpc/user_service/server
 php swoole.php
```

##启动信息服务
```
 cd swoole_yaf_rpc/message_service/server
 php swoole.php
```

##客户端调试
```
 curl http://localhost/swoole_yaf_rpc/client/public/index.php
```


