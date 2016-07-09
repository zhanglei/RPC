<?php

use \Swoole\Server\Server;

/*
 * 目录斜杆
 */
define('DS', DIRECTORY_SEPARATOR);

/*
 * 环境
 */
define('ENVIRON', 'develop');

/*
 * 网站根目录
 */
define('PROJECT_ROOT', realpath(dirname(__DIR__)));

/*
 * YAF应用程序所在目录
 */
define('APPLICATION_PATH', PROJECT_ROOT . DS . 'application');

include PROJECT_ROOT . '/../vendor/autoload.php';

//class YafServer extends Server
//{
//    /**
//     * 同步模式
//     * @var int
//     */
//    const SYNC_MODE = 1;
//
//    /**
//     * 异步模式
//     * @var int
//     */
//    const ASYNC_MODE = 2;
//    
//    /**
//     * @param \swoole_server $server
//     * @param int $fd
//     * @param int $from_id
//     * @param string $data
//     * @param array $header
//     * @return mixed
//     */
//    public function doWork(\swoole_server $server, $fd, $from_id, $data, $header)
//    {
//        //$server->send($fd, $data, $from_id);
//        //$server->close($fd);
//    }
//    
//    public function doTask(\swoole_server $server, $task_id, $from_id, $data)
//    {
//        
//    }
//}

class DemoServer extends Server
{

    public function doWork(\swoole_server $server, $fd, $from_id, $data, $header)
    {
        $this->sendMessage($fd, \Swoole\Packet\Format::packFormat($data['params']), $header['type'], $header['guid']);
    }

    public function doTask(\swoole_server $server, $task_id, $from_id, $data)
    {
        return $data['params'];
    }
}

//DemoServer::getInstance(PROJECT_ROOT . DS . 'config/swoole.ini', 'userService')->run();

$server = new DemoServer(PROJECT_ROOT . DS . 'config/swoole.ini');
$server->run();

