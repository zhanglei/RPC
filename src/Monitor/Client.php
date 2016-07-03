<?php

namespace Monitor;

class Client
{

    private static $instance;

    /**
     * @var \swoole_client
     */
    private $client;
    
    private $config;

    private function __construct()
    {
        $this->config = parse_ini_file(PROJECT_ROOT . DS . 'config/monitor.ini', true);
        
        $this->client = new \swoole_client(\SWOOLE_SOCK_UDP, \SWOOLE_SOCK_SYNC);
        $this->client->connect($this->config['server']['ip'], $this->config['server']['port']);
    }

    public static function getInstance()
    {
        if (!self::$instance instanceof self) {
            self::$instance = new self;
        }

        return self::$instance;
    }
    
    public function report($data)
    {
        $this->client->send($data);
    }
}
