<?php

namespace Monitor;

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

//        $class_name = '\Monitor\Container\\' . ucfirst(strtolower($storage));
//        $this->handle = $class_name::getInstance();
//
//        $this->config = parse_ini_file(PROJECT_ROOT . DS . 'config/monitor.ini', true);
//
//        $this->server = new \swoole_server($this->config['server']['ip'], $this->config['server']['port'], $this->config['swoole']['mode'], $this->config['swoole']['sock_type']);
//
//        $this->server->set($this->config['swoole']);
//        $this->server->on('packet', [$this, 'onPacket']);
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

    private function initMonitor()
    {
        $this->server = new \swoole_server($this->config['server']['ip'], $this->config['server']['port'], $this->config['swoole']['mode'], $this->config['swoole']['sock_type']);

        $this->server->set($this->config['swoole']);
        $this->server->on('packet', [$this, 'onPacket']);
        $this->server->on('start', [$this, 'onStart']);
    }
    
    private function start()
    {
        $this->handle->create();
        $this->server->start();
    }

    private function reload()
    {
        $managerId = $this->getPidFromFile($this->managerPidFile);

        if (!$managerId) {
            $this->log($this->processName . ': can not find manager pid file');
            $this->log($this->processName . ': reload [FAIL]');
            return false;

        //SIGUSR1 10
        } else if (!posix_kill($managerId, SIGUSR1)) {
            $this->log($this->processName . ': send signal to manager failed');
            $this->log($this->processName . ': stop [FAIL]');
            return false;
        }
        
        $this->log($this->processName . ': reload [OK]');
        return true;
    }

    private function shutdown()
    {
        $masterId = $this->getPidFromFile($this->masterPidFile);

        if (!$masterId) {
            $this->log($this->processName . ': can not find master pid file');
            $this->log($this->processName . ': stop [FAIL]');
            return false;

        //SIGTERM  15  SIGKILL 9
        } else if (!posix_kill($masterId, SIGKILL)) {
            $this->log($this->processName . ': send signal to master failed');
            $this->log($this->processName . ': stop [FAIL]');
            return false;
        }

        unlink($this->masterPidFile);
        unlink($this->managerPidFile);

        $this->log($this->processName . ": stop [OK]");

        return true;
    }

    private function getPidFromFile($file)
    {
        $pid = false;
        if (file_exists($file)) {
            $pid = file_get_contents($file);
        }

        return $pid;
    }

    private function log($msg)
    {
        echo $msg . PHP_EOL;
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
