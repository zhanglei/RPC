<?php

namespace Monitor\Container;

class Redis implements ContainerInterface
{

    private static $instance;

    /**
     * @var \Redis
     */
    private $redis;

    private $config;

    private function __construct()
    {
        $this->config = parse_ini_file(PROJECT_ROOT . DS . 'config/monitor.ini', true);
    }

    public static function getInstance()
    {
        if (!self::$instance instanceof self) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * 创建容器
     */
    public function create()
    {
        if (!$this->redis instanceof \Redis) {
            try {
                $this->redis = new \Redis();
                $this->redis->connect($this->config['redis']['ip'], $this->config['redis']['port']);
            } catch (\Exception $e) {
                throw new \Exception($e->getMessage(), $e->getCode());
            }
        }
    }

    public function set($key, $value)
    {
        $this->redis->sAdd('serverlist', json_encode($value));
    }

    public function serverConfig()
    {
        $server_list = [];
        $content = '[serverlist]' . PHP_EOL;
        $redis_list = $this->redis->smembers('serverlist');
        
        if ($redis_list) {
            foreach ($redis_list as $node) {
                $info = json_decode($node, true);

                if (time() - $info['time'] > 20) {
                    continue;
                }

                $server_list[] = $info;
            }

            if (count($server_list) > 0) {
                foreach ($server_list as $node) {
                    $_ip = str_replace('.', '_', $node['ip']);
                    $content .= strtolower($node['name']) . '.' . $_ip . '_' . $node['port'] . '.ip = ' . $node['ip'] . PHP_EOL;
                    $content .= strtolower($node['name']) . '.' . $_ip . '_' . $node['port'] . '.port = ' . $node['port'] . PHP_EOL;
                }

                file_put_contents(PROJECT_ROOT . '/../client/config/serverlist.ini', $content);
            }
        }
        
    }
    
}
