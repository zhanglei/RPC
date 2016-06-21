<?php

namespace Rpc;

class Swoole extends Client
{
    /**
     * @var Swoole
     */
    private static $instances;

    private $client;

    private $config;

    private $serverGroup = [];

    private $currentServer;

    /**
     * 协议
     * @var string
     */
    private $protocol;

    private function __construct()
    {
        $this->config = (new \Yaf_Config_Ini(PROJECT_ROOT . DS . 'config/client.ini', ENVIRON))->toArray();

        $this->client = new \swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_SYNC);
        if (isset($this->config['swoole'])) {
            $this->client->set($this->config['swoole']);
        }
        
        $this->serverGroup = $this->config['servergroup'];
        
        $protocol = 'Protocols\\' . ucfirst($this->config['protocol']);
        $this->protocol = new $protocol;
    }

    /**
     * @return Swoole
     */
    public static function instance()
    {
        if (empty(self::$instances)) {
            self::$instances = new self;
        }

        return self::$instances;
    }

    /**
     * 指定服务
     * @return $this
     */
    public function UserService()
    {
        $this->currentServer = [
            'name'  => 'User',
            'ip'    => $this->serverGroup['user']['ip'],
            'port'  => $this->serverGroup['user']['port']
        ];

        return $this;
    }

    public function MessageService()
    {
        $this->currentServer = [
            'name'  => 'Message',
            'ip'    => $this->serverGroup['message']['ip'],
            'port'  => $this->serverGroup['message']['port']
        ];

        return $this;
    }

    /**
     * 指定服务
     * @param $name
     * @return $this
     * @throws RpcException
     */
    public function setService($name)
    {
        if (!isset($this->serverGroup[$name])) {
            RpcException::invalidServices();
        }

        $this->currentServer = [
            'name'  => ucfirst($name),
            'ip'    => $this->serverGroup[$name]['ip'],
            'port'  => $this->serverGroup[$name]['port']
        ];
        
        return $this;
    }
    
    public function doRequest($url, $params = [])
    {
        $send_data = $this->protocol->encode(
            [
                'service'   => $this->currentServer['name'],
                'url'       => $url,
                'params'    => $params
            ],
            true
        );
        
        //同步发送接收
        $this->_send($send_data);

        return $this->_recv();
    }

    /**
     * rpc调用
     * @param $method
     * @param $arguments
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        $send_data = $this->protocol->encode(
            [
                'service'   => $this->currentServer['name'],
                'method'    => $method,
                'params'    => $arguments
            ],
            true
        );
        
        //同步发送接收
        $this->_send($send_data);
        
        return $this->_recv();
    }

    /**
     * 发送数据给服务端
     * @param $send_data
     * @return bool
     */
    protected function _send($send_data)
    {
        $this->_connect();

        return $this->client->send($send_data);
    }

    /**
     * 获取服务端返回的数据
     * @return null
     * @throws \Exception
     */
    protected function _recv()
    {
        $res = $this->client->recv();
        $this->_close();

        if ($res) {
            $data = $this->protocol->decode($res);

            if (is_string($data) && strpos($data, ':') !== false) {
                $errorInfo = explode(':', $data);
                throw new \Exception($errorInfo[1], $errorInfo[0]);
            }

            if ($data['code'] != 0) {
                throw new \Exception($data['message'], $data['code']);
            }

            return $data['data'];
        }

        return null;
    }

    /**
     * 链接服务端
     * @throws RpcException
     */
    protected function _connect()
    {
        if (empty($this->currentServer)) {
            RpcException::invalidServices();
        }

//        try {
//            $this->swoole->connect($this->currentServer['ip'], $this->currentServer['port'], $this->timeout);
//        } catch (\Exception $exception) {
//            var_dump($exception);
//        }
        
        if (!$this->client->connect($this->currentServer['ip'], $this->currentServer['port'], $this->timeout)) {
            RpcException::connectFailed("swoole connect failed. Error: {$this->client->errCode}\n");
        }
    }

    /**
     * 关闭服务端链接
     */
    protected function _close()
    {
        $this->currentServer = null;
        $this->client->close();
    }
    
}