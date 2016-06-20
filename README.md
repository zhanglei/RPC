# swoole_yaf_rpc
swoole结合yaf的rpc

启动用户服务
cd swoole_yaf_rpc/user_service/server
php swoole.php

启动信息服务
cd swoole_yaf_rpc/message_service/server
php swoole.php

客户端调用
curl http://localhost/swoole_yaf_rpc/client/public/index.php
