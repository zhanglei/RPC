<?php

namespace Rpc;

use Packet\Format;
use Protocols\Serialize;

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
        
        $protocol = 'Protocols\\' . ucfirst($this->config['protocol']);
        $this->protocol = $protocol::PROTOCOLS_MODE;

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
        $this->currentService = 'userservice';

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
        $this->guid = $this->_generateGuid();

        $send_data = Format::packEncode(
            [
                'url'       => $url,
                'params'    => $params,
                'type'      => self::SYNC_MODE,
                'guid'      => $this->guid
            ],
            $this->protocol
        );

        $result = $this->doRequest($send_data, self::SYNC_MODE);

        if (isset($result['data'])) {
            return $result['data']['data'];
        } else {
            return null;
        }

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
        $this->guid = $this->_generateGuid();

        $send_data = Format::packEncode(
            [
                'url'       => $url,
                'params'    => $params,
                'type'      => self::ASYNC_MODE,
                'guid'      => $this->guid
            ],
            $this->protocol
        );

        $result = $this->doRequest($send_data, self::ASYNC_MODE);
        
        if (isset($result['data'])) {
            return $result['data']['guid'];
        } else {
            return null;
        }
        
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
                            $data = Format::packDecode($result, $this->protocol);

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

    private function doRequest($send_data, $mode)
    {
        $client = $this->getClientConnect();

        $res = $client->send($send_data);

        if (!$res) {
            $errCode = $client->errCode;
            throw new \Exception(socket_strerror($errCode), $errCode);
        }

        if ($mode == self::ASYNC_MODE) {
            self::$asynclist[$this->guid]['obj'] = $client;
            self::$asynclist[$this->guid]['service'] = $this->currentService;
        }
        
        $result = $this->resultData($client);
        $this->_close();
        
        return $result; 
    }
    
    private function getClientConnect()
    {
        if (empty($this->currentService)) {
            RpcException::invalidServices();
        }

        if (!isset($this->servicegroup[$this->currentService])) {

            if (!file_exists(PROJECT_ROOT . DS . 'config/serverlist.ini')) {
                RpcException::MissingServiceList();
            }

            $serverlist = (new \Yaf_Config_Ini(PROJECT_ROOT . DS . 'config/serverlist.ini', 'serverlist'))->toArray();

            if (!isset($serverlist[$this->currentService])) {
                RpcException::serviceNotExist();
            }

            $this->servicegroup[$this->currentService] = $serverlist[$this->currentService];
        }

        $key = array_rand($this->servicegroup[$this->currentService]);
        $connect_info = $this->servicegroup[$this->currentService][$key];

        if (!isset(self::$client[$key])) {
            $client = new \swoole_client(SWOOLE_SOCK_TCP | SWOOLE_KEEP);
            $client->set(isset($this->config['swoole']) ? $this->config['swoole'] : []);

            if (!$client->connect($connect_info['ip'], $connect_info['port'], $this->timeout)) {
                RpcException::connectFailed('swoole connect failed. Error: ' . socket_strerror($client->errCode));
            }

            self::$client[$key] = $client;
        }

        return self::$client[$key];
    }

    private function resultData($client)
    {
        while (true) {
            $result = $client->recv();

            if (!empty($result)) {
                $data = Format::packDecode($result, $this->protocol);

                if ($data['data']['guid'] != $this->guid) {
                    if (isset(self::$asynclist[$data['data']['guid']])) {

                        if ($data['code'] == 0) {
                            self::$asynresult[$data['data']['guid']] = $data['data'];
                        } else {
                            self::$asynresult[$data['data']['guid']] = Format::packFormat('', $data['message'], $data['code']);
                        }

                        unset(self::$asynclist[$data['data']['guid']]);

                    } else {
                        continue;
                    }
                } else {
                    return $data;
                }
            } else {
                $data = Format::packFormat('', 'Service_' . $this->guid . ' : recive wrong or timeout', 10005);
                return $data;
            }
        }
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