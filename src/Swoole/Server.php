<?php

namespace Swoole;

use Packet\Format;
use Packet\Response;

class Server
{

    private static $instance;
    
    /*
     * 配置
     */
    private $config;

    /*
     * @var \swoole_server
     */
    private $server;

    /*
     * 服务
     */
    private $application;

    /*
     * 进程名称
     */
    const WORK_NAME = 'swoole-worker-%d';

    private function __construct()
    {
        $this->config = parse_ini_file(PROJECT_ROOT . DS . 'config/swoole.ini', true);
        $this->server = new \swoole_server($this->config['server']['ip'], $this->config['server']['port'], $this->config['swoole']['mode'], $this->config['swoole']['sock_type']);
    }

    public static function getInstance()
    {
        if (!self::$instance instanceof self) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    public function run()
    {
        $this->server->set($this->config['swoole']);
        $this->server->on('start', [$this, 'onStart']);
        $this->server->on('connect', [$this, 'onConnect']);
        $this->server->on('managerstart', [$this, 'onManagerStart']);
        $this->server->on('workerstart', [$this, 'onWorkerStart']);
        $this->server->on('close', [$this, 'onClose']);
        $this->server->on('receive', [$this, 'onReceive']);

        $this->server->start();
    }

    public function onStart(\swoole_server $server)
    {
        echo "\033[1A\n\033[K-----------------------\033[47;30m " . PROJECT_NAME . " \033[0m-----------------------------\n\033[0m";
        echo 'swoole version:' . swoole_version() . '        PHP version:' . PHP_VERSION . '         yaf version:' . YAF_VERSION . "\n";
        echo "------------------------\033[47;30m WORKERS \033[0m---------------------------\n";
    }

    public function onConnect(\swoole_server $server, $fd, $from_id)
    {
        echo "Worker#{$server->worker_pid} Client[$fd@$from_id]: Connect.\r\n";
    }

    public function onClose($server, $fd, $from_id)
    {
        echo "Worker#{$server->worker_pid} Client[$fd@$from_id]: fd=$fd is closed\r\n";
    }

    public function onManagerStart(\swoole_server $server)
    {
    }

    public function onWorkerStart(\swoole_server $server, $workerId)
    {

        $processName = sprintf(self::WORK_NAME, $workerId);
//
//        //todo Mac 执行不了
//        //cli_set_process_title($processName);
//

        /*
         * worker分配yaf
         */
        $this->application = new \Yaf_Application(PROJECT_ROOT . DS . 'config/application.ini', $processName);
        $this->application->getDispatcher()->getRouter()->addConfig(
            (new \Yaf_Config_Ini(PROJECT_ROOT . '/config/routes.ini', ENVIRON))->get('routes')
        );

        //注册响应
        \Yaf_Registry::set('HTTP_RESPONSE', (new \Yaf_Response_Http()));

    }

    public function onReceive(\swoole_server $server, $fd, $from_id, $data)
    {
        //清除响应Body
        \Yaf_Registry::get('HTTP_RESPONSE')->clearBody();

        //数据格式
        $protocol = substr($data, 4, 4);
        $protocol_mode = unpack('Nprotocol', $protocol)['protocol'];

        $invalid_ip = false;
        foreach (swoole_get_local_ip() as $ip) {
            if (in_array($ip, $this->config['server']['licenseip'])) {
                $invalid_ip = true;
                break;
            }
        }

        //todo 只允许指定的ip访问rpc
        if (!$invalid_ip) {
            return $this->responseClient($server, $fd, Format::packFormat('', 'invalid ip', 10001), $protocol_mode);
        }

        $requestInfo = Format::packDecode($data, $protocol_mode, true);

        if (is_string($requestInfo) && strpos($requestInfo, ':') !== false) {
            $errorInfo = explode(':', $requestInfo);
            return $this->responseClient($server, $fd, Format::packFormat('', $errorInfo[1], $errorInfo[0]), $protocol_mode);
        }
        
        $request_uri = $requestInfo['service'] . $requestInfo['url'];
        $request = new \Yaf_Request_Http($request_uri);

        if (!empty($requestInfo['params'])) {
            foreach ($requestInfo['params'] as $name => $value) {
                $request->setParam($name, $value);
            }
        }

        try {
            /*
             * 关闭YAF的异常捕获
             * YAF的异常捕获只能捕获一次  之后的错误  不会触发ErrorController
             */
            $this->application->getDispatcher()->catchException(false);
            /*
             * 关闭自动输出给请求端
             */
            $this->application->getDispatcher()->returnResponse(true);
            /*
             * 分发请求
             */
            $this->application->bootstrap()->getDispatcher()->dispatch($request);
        } catch (\Yaf_Exception $exception) {
            Response::packFormat('', $exception->getMessage(), $exception->getCode());
        }

        $send_data = Format::packFormat(
            unserialize(\Yaf_Registry::get('HTTP_RESPONSE')->getBody('data')),
            \Yaf_Registry::get('HTTP_RESPONSE')->getBody('message'),
            \Yaf_Registry::get('HTTP_RESPONSE')->getBody('code')
        );

        return $this->responseClient($server, $fd, $send_data, $protocol_mode);
    }

    /**
     * 相应客户端
     * @param \swoole_server $server
     * @param $fd
     * @param $send_data
     * @param int $protocol_mode
     * @return bool
     */
    private function responseClient(\swoole_server $server, $fd, $send_data, $protocol_mode = 0)
    {
        $server->send($fd, Format::packEncode($send_data, $protocol_mode));

        return true;
    }

}