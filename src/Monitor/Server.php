<?php

namespace Monitor;

use Helper\Console;

class Server
{

    private static $instance;

    /**
     * @var \swoole_server
     */
    private $server;
    
    private $handle;

    private function __construct($storage)
    {
        $this->config = parse_ini_file(PROJECT_ROOT . DS . 'config/monitor.ini', true);

        $this->processName = 'swoole-monitor-' . $this->config['server']['ip'] . '-' . $this->config['server']['port'];

        if (!file_exists($this->config['server']['pid_path'])) {
            mkdir($this->config['server']['pid_path'], 0700);
        }

        $this->masterPidFile = $this->config['server']['pid_path'] . DS . $this->processName . '.master.pid';
        $this->managerPidFile = $this->config['server']['pid_path'] . DS . $this->processName . '.manager.pid';

        $class_name = '\Monitor\Container\\' . ucfirst(strtolower($storage));
        $this->handle = $class_name::getInstance();

    }

    public static function getInstance($storage = 'redis')
    {
        if (!self::$instance instanceof self) {
            self::$instance = new self($storage);
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
                $this->initMonitor();
                $this->start();
                break;
            case 'reload':
                $this->reload();
                break;
            case 'restart':
                $this->shutdown();
                sleep(2);
                $this->initMonitor();
                $this->start();
                break;
            default:
                echo 'Usage:php monitor.php start | stop | reload | restart | help' . PHP_EOL;
                break;
        }
        
    }

    /**
     * 初始化服务
     */
    private function initMonitor()
    {
        $this->server = new \swoole_server($this->config['server']['ip'], $this->config['server']['port'], $this->config['swoole']['mode'], $this->config['swoole']['sock_type']);

        $this->server->set($this->config['swoole']);
        $this->server->on('packet', [$this, 'onPacket']);
        $this->server->on('start', [$this, 'onStart']);
    }

    /**
     * 启动服务
     */
    private function start()
    {
        $this->handle->create();
        $this->server->start();
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

    public function onStart(\swoole_server $server)
    {
        file_put_contents($this->masterPidFile, $server->master_pid);
        file_put_contents($this->managerPidFile, $server->manager_pid);
    }
    
    public function onPacket(\swoole_server $server, $data, $client_info)
    {
        $data = unserialize($data);

        //服务注册
        if (isset($data['node'])) {
            $node = [
                'name'  => $data['node']['name'],
                'ip'    => $data['node']['ip'],
                'port'  => $data['node']['port'],
                'time'  => $data['node']['time']
            ];

            $this->handle->set($data['node']['ip'] . '_' . $data['node']['port'], $node);
            $this->handle->serverConfig();
        }

        //服务stats
        if (isset($data['stats'])) {

        }
    }

}
