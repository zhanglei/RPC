<?php

namespace Monitor;

class SwooleTable extends MonitorAbstract
{

    private static $instance;

    /**
     * @var \swoole_server
     */
    private $server;

    /**
     * @var \swoole_client
     */
    private $client;

    /**
     * @var \swoole_table
     */
    private $table;

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

    public function run()
    {
        $this->server = new \swoole_server($this->config['swooletable']['ip'], $this->config['swooletable']['port'], $this->config['swoole']['mode'], $this->config['swoole']['sock_type']);

        $this->server->set($this->config['swoole']);
        $this->server->on('packet', [$this, 'onPacket']);

        $this->table = [
            //节点信息
            'node_info'     => new \swoole_table(1024),
            //节点stats
            'node_stats'    => new \swoole_table(1024)
        ];

        $this->table['node_info']->column('name', \swoole_table::TYPE_STRING, 64);
        $this->table['node_info']->column('ip', \swoole_table::TYPE_STRING, 20);
        $this->table['node_info']->column('port', \swoole_table::TYPE_INT, 4);
        $this->table['node_info']->column('time', \swoole_table::TYPE_INT, 10);
        $this->table['node_info']->create();

        $this->table['node_stats']->column('name', \swoole_table::TYPE_STRING, 64);
        $this->table['node_stats']->column('ip', \swoole_table::TYPE_STRING, 20);
        $this->table['node_stats']->column('port', \swoole_table::TYPE_INT, 4);
        $this->table['node_stats']->column('time', \swoole_table::TYPE_INT, 10);
        $this->table['node_stats']->create();


        //$process = new \swoole_process([$this, 'serverConfig']);
        //$this->server->addProcess($process);

        $this->server->start();
    }

//    public function serverConfig(\swoole_process $process)
//    {
//        while (true) {
//            $server_list = [];
//
//            if (count($this->table['node_info']) > 0) {
//                foreach ($this->table['node_info'] as $key => $value) {
//                    var_dump($value);
//                }
//            }
//
//            sleep(10);
//        }
//    }

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

            $this->table['node_info']->set($data['node']['ip'] . '_' . $data['node']['port'], $node);
            $this->serverConfig();
        }
    }

    protected function serverConfig()
    {
        $server_list = [];
        $content = '[serverlist]' . PHP_EOL;

        if (count($this->table['node_info']) > 0) {
            foreach ($this->table['node_info'] as $key => $value) {
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

    public function report($data)
    {
        if (!$this->client instanceof \swoole_client) {
            $this->client = new \swoole_client(SWOOLE_SOCK_UDP, SWOOLE_SOCK_SYNC);
            $this->client->connect($this->config['swooletable']['ip'], $this->config['swooletable']['port']);
        }

        $this->client->send($data);
    }
}
