<?php

namespace Rpc;

use Packet\Format;

class Swoole extends Client
{
    /*
     * @var Swoole
     */
    private static $instances;

    /*
     * @var array
     */
    private $config;

    /*
     * 异步请求唯一标示
     * @var string
     */
    private $guid;

    /*
     * 协议
     * @var string
     */
    private $protocol;

    private function __construct()
    {
        $this->config = (new \Yaf_Config_Ini(PROJECT_ROOT . DS . 'config/client.ini', ENVIRON))->toArray();
        $common_config = isset($this->config['swoole']) ? $this->config['swoole'] : [];

        foreach ($this->config['servicegroup'] as $service => $config) {
            $client = new \swoole_client(SWOOLE_TCP | SWOOLE_KEEP);
            $group_config = isset($config['swoole']) ? $config['swoole'] : [];
            $sw_config = array_merge($common_config, $group_config);

            if (!empty($sw_config)) {
                $client->set($sw_config);
            }

            self::$client[ucfirst($service)] = $client;
        }
        
        $this->servicegroup = $this->config['servicegroup'];
        
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
        $this->currentService = [
            'name'  => 'User',
            'ip'    => $this->servicegroup['user']['ip'],
            'port'  => $this->servicegroup['user']['port']
        ];

        return $this;
    }

    public function MessageService()
    {
        $this->currentService = [
            'name'  => 'Message',
            'ip'    => $this->servicegroup['message']['ip'],
            'port'  => $this->servicegroup['message']['port']
        ];

        return $this;
    }

    /**
     * 同步请求
     * @param $url
     * @param array $params
     * @return string|array
     * @throws \Exception
     */
    public function syncRequest($url, $params = [])
    {
        $service = $this->currentService['name'];

        if (empty($this->currentService) || !isset(self::$client[$service])) {
            RpcException::invalidServices();
        }
        
        $send_data = $this->protocol->encode(
            [
                'service'   => $service,
                'url'       => $url,
                'params'    => $params,
                'type'      => self::SYNC_MODE
            ],
            true
        );

        $this->_send($send_data);

        return $this->_recv();
    }

    /**
     * 异步请求  返回guid
     * @param $url
     * @param $params
     * @return string
     * @throws RpcException
     * @throws \Exception
     */
    public function asyncRequest($url, $params = [])
    {
        $service = $this->currentService['name'];

        if (empty($this->currentService) || !isset(self::$client[$service])) {
            RpcException::invalidServices();
        }

        $this->guid = $this->_generateGuid();
        
        $send_data = $this->protocol->encode(
            [
                'service'   => $this->currentService['name'],
                'url'       => $url,
                'params'    => $params,
                'type'      => self::ASYNC_MODE,
                'guid'      => $this->guid
            ],
            true
        );

        self::$asynclist[$this->guid]['obj'] = self::$client[$service];
        self::$asynclist[$this->guid]['service'] = $service;
        
        $this->_send($send_data);

        return $this->_recv();
    }

    /**
     * 获取异步结果
     * @return array
     */
    public function getAsyncData()
    {
        while (true) {
            if (count(self::$asynclist) > 0) {
                foreach (self::$asynclist as $guid => $value) {
                    $client = $value['obj'];
                    if ($client->isConnected()) {
                        $result = $client->recv();
                        
                        if (!empty($result)) {
                            $data = $this->protocol->decode($result);

                            if (isset(self::$asynclist[$data['data']['guid']])) {
                                
                                if ($data['code'] == 0) {
                                    self::$asynresult[$guid] = $data['data'];
                                } else {
                                    self::$asynresult[$guid] = Format::packFormat('', $data['message'], $data['code']);
                                }
                                
                                unset(self::$asynclist[$guid]);
                                continue;
                            } else {
                                continue;
                            }
                        } else {
                            self::$asynresult[$guid] = Format::packFormat('', $client['_service'] . 'Service_' . $guid . ' : recive wrong or timeout', 10005);
                            unset(self::$asynclist[$guid]);
                            continue;
                        }
                    } else {
                        self::$asynresult[$guid] = Format::packFormat('', $client['_service'] . 'Service_' . $guid . ' : client closed', 10006);
                        unset(self::$asynclist[$guid]);
                        continue;
                    }
                }
            } else {
                break;
            }
        }

        return self::$asynresult;
    }

    /**
     * 发送数据给服务端
     * @param $send_data
     * @return int
     * @throws \Exception
     */
    protected function _send($send_data)
    {
        $client = $this->_connect();
        $res = $client->send($send_data);
        
        if (!$res) {
            $errCode = $client->errCode;
            throw new \Exception(socket_strerror($errCode), $errCode);
        }
        
        return $res;
    }

    /**
     * 获取服务端返回数据
     * @return string|array
     * @throws RpcException
     * @throws \Exception
     */
    protected function _recv()
    {
        $service = $this->currentService['name'];
        $result = self::$client[$service]->recv();
        $this->_close();

        if ($result) {
            $data = $this->protocol->decode($result);

            if ($data['code'] != 0) {
                throw new \Exception($data['message'], $data['code']);
            }

            return $data['data'];
        } else {
            RpcException::reciveFailed($service);
        }
    }

    /**
     * 连接服务端
     * @return \swoole_client
     * @throws RpcException
     */
    protected function _connect()
    {
        if (empty($this->currentService) || !isset(self::$client[$this->currentService['name']])) {
            RpcException::invalidServices();
        }

        $client = self::$client[$this->currentService['name']];

        if (!$client->connect($this->currentService['ip'], $this->currentService['port'], $this->timeout)) {
            RpcException::connectFailed("swoole connect failed. Error: {$client->errCode}\n");
        }

        return $client;
    }

    /**
     * 关闭服务端链接
     */
    protected function _close()
    {
        $this->currentService = null;
        //$this->client->close();
    }

    /**
     * @return string
     */
    private function _generateGuid()
    {
        while (true) {
            $guid = md5(microtime(true) . mt_rand(1, 1000000));
            if (!isset(self::$asynclist[$guid])) {
                return $guid;
            }
        }
    }
    
}