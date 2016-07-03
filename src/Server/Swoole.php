<?php

namespace Server;

use Helper\Console;
use Monitor\Client;
use Packet\Format;

class Swoole
{

    private static $instance;

    /*
     * 配置
     */
    private $config;

    /**
     * @var \swoole_server
     */
    private $server;

    /**
     * @var \Yaf_Application
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
    //private $runPath = PROJECT_ROOT . DS . 'pid';

    /*
     *
     */
    private $processName;

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
        $this->config = (new \Yaf_Config_Ini(PROJECT_ROOT . DS . 'config/swoole.ini'))->toArray();

        $this->processName = 'swoole-server-' . $this->config['server']['ip'] . '-' . $this->config['server']['port'];

        if (!file_exists($this->config['server']['pid_path'])) {
            mkdir($this->config['server']['pid_path'], 0700);
        }

        $this->masterPidFile = $this->config['server']['pid_path'] . DS . $this->processName . '.master.pid';
        $this->managerPidFile = $this->config['server']['pid_path'] . DS . $this->processName . '.manager.pid';
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

    /**
     * 初始化服务
     */
    private function initServer()
    {
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

    /**
     * 启动服务
     */
    private function start()
    {
        //服务上报进程
        $process = new \swoole_process([$this, 'serviceReport']);
        $this->server->addProcess($process);

        $this->server->start();
    }

    /**
     * 关闭服务器
     * @return bool
     */
    private function shutdown()
    {
        if (Console::shutdown($this->masterPidFile, $this->processName)) {
            unlink($this->masterPidFile);
            unlink($this->managerPidFile);

            return true;
        }

        return false;
    }

    /**
     * 重启worker进程
     * @return bool
     */
    private function reload()
    {
        return Console::reload($this->managerPidFile, $this->processName);
    }

    /**
     * 上报服务信息
     * @param \swoole_process $process
     */
    public function serviceReport(\swoole_process $process)
    {
        while (true) {
            $data = [
                'node' => [
                    'name'  => PROJECT_NAME,
                    'ip'    => $this->getServerIP(),
                    'port'  => $this->config['server']['port'],
                    'time'  => time()
                    
                ],
                'stats' => $this->server->stats()
            ];

            Client::getInstance()->report(serialize($data));

            sleep(10);
        }
    }

    /**
     * 获取服务器真实ip
     * @return string
     */
    private function getServerIP()
    {
        if ($this->config['server']['ip'] == '0.0.0.0' || $this->config['server']['ip'] == '127.0.0.1') {
            $serverIps = swoole_get_local_ip();
            $patternArray = [
                '192\.168\.'
            ];
            
            foreach ($serverIps as $serverIp) {
                // 匹配内网IP
                if (preg_match('#^' . implode('|', $patternArray) . '#', $serverIp)) {
                    return $serverIp;
                }
            }
        }
        
        return $this->config['server']['ip'];
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
//        if (!$this->application instanceof \Yaf_Application) {
//            $this->application = new \Yaf_Application(PROJECT_ROOT . DS . 'config/application.ini');
//        }

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
        //数据格式
        $protocol = substr($data, 4, 4);
        $protocol_mode = unpack('N', $protocol)[1];
        $send_data = [];

        try {

            $requestInfo = Format::packDecode($data, $protocol_mode);
            if (empty($requestInfo['url']) || empty($requestInfo['type'])) {
                Exception::BadRequest();
            }

            switch ($requestInfo['type']) {
                case self::SYNC_MODE :
                    //分发请求
                    $this->doTask($server, $fd, $from_id, $requestInfo, $protocol_mode);
                    return true;
                    break;
                case self::ASYNC_MODE :
                    if (!isset($requestInfo['guid'])) {
                        Exception::EmptyGuid();
                    }

                    $send_data = $this->doTask($server, $fd, $from_id, $requestInfo, $protocol_mode);
                    break;
            }
        } catch (\Exception $e) {
            $exception = $e;
        }

        if (isset($exception) && $exception instanceof \Exception) {
            $send_data = Format::packFormat('', PROJECT_NAME . ' : ' . $exception->getMessage(), $exception->getCode());
        }

        return $this->responseClient($server, $fd, $send_data, $protocol_mode);

    }

    public function onTask(\swoole_server $server, $task_id, $from_id, $data)
    {
        if (!$this->application instanceof \Yaf_Application) {
            $this->application = new \Yaf_Application(PROJECT_ROOT . DS . 'config/application.ini', ENVIRON);
        }
        
        ob_start();

        try {
            //分发请求
            $this->dispatchRequest($data);
        } catch (\Exception $e) {
            $exception = $e;
        }

        $result = ob_get_contents();

        ob_end_clean();

        if (isset($exception) && $exception instanceof \Exception) {
            $send_data = Format::packFormat('', PROJECT_NAME . '_' . $data['guid'] . ':' . $exception->getMessage(), $exception->getCode());
        } else {
            $result = unserialize($result);
            $send_data = Format::packFormat($result['data'], $result['message'], $result['code']);
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

    private function doTask(\swoole_server $server, $fd, $from_id, $requestInfo, $protocol_mode)
    {
        $this->taskInfo[$fd][$requestInfo['guid']] = true;

        $task = [
            'guid'      => $requestInfo['guid'],
            'fd'        => $fd,
            'url'       => $requestInfo['url'],
            'params'    => $requestInfo['params'],
            'protocol'  => $protocol_mode
        ];

        $server->task($task);
        $send_data = [
            'guid' => $requestInfo['guid']
        ];

        return Format::packFormat($send_data, PROJECT_NAME . ' : task success');
    }

    /**
     * YAF分发请求
     * @param $data
     */
    private function dispatchRequest($data)
    {
        $path_info = explode('/', strtolower($data['url']));
        unset($path_info[0]);

        $module = $path_info[1];
        $controller = isset($path_info[2]) ? $path_info[2] : $this->application->getConfig()->get('yaf')->get('dispatcher')->get('defaultController');
        $action = isset($path_info[3]) ? $path_info[3] : $this->application->getConfig()->get('yaf')->get('dispatcher')->get('defaultAction');

        $request = new \Yaf_Request_Simple('SWOOLE_RPC', $module, $controller, $action, $data['params']);
        
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