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
     * 投递信息
     */
    private $taskInfo = [];

    /*
     * 主进程pid文件
     */
    private $masterPidFile;

    /*
     * 管理进程pid文件
     */
    private $managerPidFile;

    /*
     * 执行路径
     */
    private $runPath = '/tmp';

    /*
     *
     */
    private $processName;

    /*
     * 进程名称
     */
    const WORK_NAME = 'swoole-worker-%d';
    
    /*
     * 同步模式
     */
    const SYNC_MODE = 1;
    
    /*
     * 异步模式
     */
    const ASYNC_MODE = 2;

    private function __construct()
    {
        $this->processName = sprintf('swoole-server-%s', PROJECT_NAME);

        $this->masterPidFile = $this->runPath . DS . $this->processName . '.master.pid';
        $this->managerPidFile = $this->runPath . DS . $this->processName . '.manager.pid';
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
        $cmd = isset($_SERVER['argv'][1]) ? strtolower($_SERVER['argv'][1]) : 'help';
        switch ($cmd) {
            case 'stop':
                $this->shutdown();
                break;
            case 'start':
                $this->initServer();
                $this->start();
                break;
            case 'reload':
                $this->reload();
                break;
            case 'restart':
                $this->shutdown();
                sleep(2);
                $this->initServer();
                $this->start();
                break;
            default:
                echo 'Usage:php swoole.php start | stop | reload | restart | help' . PHP_EOL;
                break;
        }
    }

    private function initServer()
    {
        $this->config = parse_ini_file(PROJECT_ROOT . DS . 'config/swoole.ini', true);
        $this->server = new \swoole_server($this->config['server']['ip'], $this->config['server']['port'], $this->config['swoole']['mode'], $this->config['swoole']['sock_type']);

        $this->server->set($this->config['swoole']);
        $this->server->on('start', [$this, 'onStart']);
        $this->server->on('connect', [$this, 'onConnect']);
        $this->server->on('managerstart', [$this, 'onManagerStart']);
        $this->server->on('workerstart', [$this, 'onWorkerStart']);
        $this->server->on('close', [$this, 'onClose']);
        $this->server->on('receive', [$this, 'onReceive']);
        $this->server->on('task', [$this, 'onTask']);
        $this->server->on('finish', [$this, 'onFinish']);
    }

    private function start()
    {
        $this->server->start();
    }

    private function shutdown()
    {
        $masterId = $this->getPidFromFile($this->masterPidFile);

        if (!$masterId) {
            $this->log($this->processName . ': can not find master pid file');
            $this->log($this->processName . ': stop [FAIL]');
            return false;

        //SIGTERM  15  mac 9
        } elseif (!posix_kill($masterId, 9)) {
            $this->log($this->processName . ': send signal to master failed');
            $this->log($this->processName . ': stop [FAIL]');
            return false;
        }

        unlink($this->masterPidFile);
        unlink($this->managerPidFile);

        $this->log($this->processName . ": stop [OK]");

        return true;
    }

    /**
     * reload worker
     * @return bool
     */
    private function reload()
    {
        $managerId = $this->getPidFromFile($this->managerPidFile);

        if (!$managerId) {
            $this->log($this->processName . ': can not find manager pid file');
            $this->log($this->processName . ': reload [FAIL]');
            return false;

        //SIGUSR1
        } elseif (!posix_kill($managerId, 10)) {
            $this->log($this->processName . ': send signal to manager failed');
            $this->log($this->processName . ': stop [FAIL]');
            return false;
        }
        $this->log($this->processName . ': reload [OK]');
        return true;
    }

    public function onStart(\swoole_server $server)
    {
        file_put_contents($this->masterPidFile, $server->master_pid);
        file_put_contents($this->managerPidFile, $server->manager_pid);
        //echo "\033[1A\n\033[K-----------------------\033[47;30m " . PROJECT_NAME . " \033[0m-----------------------------\n\033[0m";
        //echo 'swoole version:' . swoole_version() . '        PHP version:' . PHP_VERSION . '         yaf version:' . YAF_VERSION . "\n";
        //echo "------------------------\033[47;30m WORKERS \033[0m---------------------------\n";
    }

    public function onConnect(\swoole_server $server, $fd, $from_id)
    {
        //echo "Worker#{$server->worker_pid} Client[$fd@$from_id]: Connect.\r\n";
    }

    public function onClose($server, $fd, $from_id)
    {
        //echo "Worker#{$server->worker_pid} Client[$fd@$from_id]: fd=$fd is closed\r\n";
    }

    public function onManagerStart(\swoole_server $server)
    {
    }

    public function onWorkerStart(\swoole_server $server, $workerId)
    {
        if (!$this->application instanceof \Yaf_Application) {
            $this->application = new \Yaf_Application(PROJECT_ROOT . DS . 'config/application.ini');
            $this->application->getDispatcher()->getRouter()->addConfig(
                (new \Yaf_Config_Ini(PROJECT_ROOT . '/config/routes.ini', ENVIRON))->get('routes')
            );

            //注册响应
            \Yaf_Registry::set('HTTP_RESPONSE', (new \Yaf_Response_Http()));
        }

//        $istask = $server->taskworker;
//
//        if (!$istask) {
//            $processName = sprintf(self::WORK_NAME, $workerId);
//
//            //cli_set_process_title($processName);
//
//            /*
//             * worker分配yaf
//             */
//            $this->application = new \Yaf_Application(PROJECT_ROOT . DS . 'config/application.ini', $processName);
//            $this->application->getDispatcher()->getRouter()->addConfig(
//                (new \Yaf_Config_Ini(PROJECT_ROOT . '/config/routes.ini', ENVIRON))->get('routes')
//            );
//
//            //注册响应
//            \Yaf_Registry::set('HTTP_RESPONSE', (new \Yaf_Response_Http()));
//        }
    }

    public function onReceive(\swoole_server $server, $fd, $from_id, $data)
    {
        //清除响应Body
        \Yaf_Registry::get('HTTP_RESPONSE')->clearBody();

        //数据格式
        $protocol = substr($data, 4, 4);
        $protocol_mode = unpack('Nprotocol', $protocol)['protocol'];

        $requestInfo = Format::packDecode($data, $protocol_mode, true);
        
        try {

            // 判断数据是否正确
            if (empty($requestInfo['service']) || empty($requestInfo['url']) || empty($requestInfo['type'])) {
                // 发送数据给客户端，请求包错误
                return $this->responseClient($server, $fd, Format::packFormat('', PROJECT_NAME . ' : bad request', 10001), $protocol_mode);
            }

            switch ($requestInfo['type']) {
                case self::SYNC_MODE :
                    //分发请求
                    $this->dispatchRequest($requestInfo);

                    $send_data = Format::packFormat(
                        unserialize(\Yaf_Registry::get('HTTP_RESPONSE')->getBody()),
                        \Yaf_Registry::get('HTTP_RESPONSE')->getBody('message'),
                        \Yaf_Registry::get('HTTP_RESPONSE')->getBody('code')
                    );

                    return $this->responseClient($server, $fd, $send_data, $protocol_mode);

                    break;
                case self::ASYNC_MODE :
                    $this->doTask($server, $fd, $from_id, $requestInfo, $protocol_mode);

                    return true;
                    break;
            }

        } catch (\Exception $exception) {
            return $this->responseClient($server, $fd, Format::packFormat('', PROJECT_NAME . ' : ' . $exception->getMessage(), $exception->getCode()), $protocol_mode);
        }
        
    }

    public function onTask(\swoole_server $server, $task_id, $from_id, $data)
    {
        //清除响应Body
        \Yaf_Registry::get('HTTP_RESPONSE')->clearBody();

        try {
            //分发请求
            $this->dispatchRequest($data);

            $send_data = Format::packFormat(
                unserialize(\Yaf_Registry::get('HTTP_RESPONSE')->getBody()),
                \Yaf_Registry::get('HTTP_RESPONSE')->getBody('message'),
                \Yaf_Registry::get('HTTP_RESPONSE')->getBody('code')
            );
        } catch (\Exception $exception) {
            $send_data = Format::packFormat(
                '',
                PROJECT_NAME . '_' . $data['guid'] . ':' . $exception->getMessage(),
                $exception->getCode()
            );
        }

        $send_data['fd'] = $data['fd'];
        $send_data['guid'] = $data['guid'];
        $send_data['protocol'] = $data['protocol'];

        return $send_data;
    }

    public function onFinish(\swoole_server $server, $task_id, $data)
    {
        $fd = $data['fd'];
        $guid = $data['guid'];

        if (!isset($this->taskInfo[$fd][$guid])) {
            return true;
        }

        unset($this->taskInfo[$fd][$guid]);

        $send_data = [
            'code'      => $data['code'],
            'message'   => $data['message'],
            'data'      => [
                'guid'  => $data['guid'],
                'data'  => $data['data']
            ]
        ];

        return $this->responseClient($server, $fd, $send_data, $data['protocol']);
    }

    private function log($msg)
    {
        echo $msg . PHP_EOL;
    }

    private function getPidFromFile($file)
    {
        $pid = false;
        if (file_exists($file)) {
            $pid = file_get_contents($file);
        }

        return $pid;
    }
    
    private function doTask(\swoole_server $server, $fd, $from_id, $requestInfo, $protocol_mode)
    {
        $this->taskInfo[$fd][$requestInfo['guid']] = true;

        $task = [
            'guid'      => $requestInfo['guid'],
            'fd'        => $fd,
            'service'   => $requestInfo['service'],
            'url'       => $requestInfo['url'],
            'params'    => $requestInfo['params'],
            'protocol'  => $protocol_mode
        ];

        $server->task($task);
        $this->responseClient($server, $fd, Format::packFormat($requestInfo['guid'], PROJECT_NAME . ' : task success'), $protocol_mode);
    }

    /**
     * YAF分发请求
     * @param $data
     */
    private function dispatchRequest($data)
    {
        $request_uri = $data['service'] . $data['url'];
        $request = new \Yaf_Request_Http($request_uri);

        if (!empty($data['params'])) {
            foreach ($data['params'] as $name => $value) {
                $request->setParam($name, $value);
            }
        }

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

//        $send_data = Format::packFormat(
//            unserialize(\Yaf_Registry::get('HTTP_RESPONSE')->getBody('data')),
//            \Yaf_Registry::get('HTTP_RESPONSE')->getBody('message'),
//            \Yaf_Registry::get('HTTP_RESPONSE')->getBody('code')
//        );
//
//        return $this->responseClient($server, $fd, $send_data, $protocol_mode);
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