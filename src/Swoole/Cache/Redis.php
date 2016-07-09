<?php

namespace Swoole\Cache;

class Redis implements CacheInterface
{
    /**
     * @var Redis
     */
    protected static $instance;

    /**
     * @var \Redis
     */
    protected $redis;
    
    private function __construct($host, $port)
    {
        try {
            $this->redis = new \Redis();
            $this->redis->connect($host, $port);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), $e->getCode());
        }
    }

    /**
     * @param array $config
     * @return Redis
     */
    public static function getInstance(array $config)
    {
        $host = $config['host'];
        $port = $config['port'];
        
        $key = $host . '_' . $port;
        
        if (!isset(self::$instance[$key])) {
            self::$instance[$key] = new self($host, $port);
        }

        return self::$instance[$key];
    }

    /**
     * @return \Redis
     */
    public function getHandle() {
        return $this->redis;
    }

    public function get($key) {
        return $this->redis->get($key);
    }

    public function set($key, $val, $ttl = 0) {
        return $this->redis->set($key, $val, $ttl);
    }

    public function del($key) {
        return $this->redis->del($key);
    }
    
}
