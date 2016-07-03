<?php

namespace Monitor\Container;

class SwooleTable implements ContainerInterface
{

    private static $instance;

    /**
     * @var \swoole_table
     */
    private $table;

    private function __construct()
    {
        
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
        if (!$this->table instanceof \swoole_table) {
            $this->table = new \swoole_table(1024);
            $this->table->column('name', \swoole_table::TYPE_STRING, 64);
            $this->table->column('ip', \swoole_table::TYPE_STRING, 20);
            $this->table->column('port', \swoole_table::TYPE_INT, 4);
            $this->table->column('time', \swoole_table::TYPE_INT, 10);
            $this->table->create();
        }
    }
    
    public function set($key, $value)
    {
        $this->table->set($key, $value);
    }

    public function serverConfig()
    {
        $server_list = [];
        $content = '[serverlist]' . PHP_EOL;

        if (count($this->table) > 0) {
            foreach ($this->table as $key => $value) {
                if (time() - $value['time'] > 20) {
                    continue;
                }
                
                $server_list[] = $value;
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
