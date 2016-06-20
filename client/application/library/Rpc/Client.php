<?php

namespace Rpc;

abstract class Client
{
    protected $ip;

    protected $port;

    /**
     * 发送数据和接收数据的超时时间 单位s
     * @var integer
     */
    protected $timeout = 1;

    /**
     * 链接服务端
     * @return mixed
     */
    abstract protected function _connect();

    /**
     * 发送数据给服务端
     * @param $send_data
     * @return mixed
     */
    abstract protected function _send($send_data);

    /**
     * 获取服务端返回的数据
     * @return array
     */
    abstract protected function _recv();

    /**
     * 关闭服务端链接
     * @return mixed
     */
    abstract protected function _close();

    /**
     * @return mixed
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * @param $ip
     */
    public function setIp($ip)
    {
        $this->ip = $ip;
    }

    /**
     * @return mixed
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @param $port
     */
    public function setPort($port)
    {
        $this->port = $port;
    }

    /**
     * @return int
     */
    public function getTimeout()
    {
        return $this->timeout;
    }
    
    /**
     * @param int $timeout
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
    }

}