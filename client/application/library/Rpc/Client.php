<?php

namespace Rpc;

abstract class Client
{

    /*
     * 当前调用的服务
     * @var array
     */
    protected $currentService;

    /*
     * 服务群组
     * @var array
     */
    protected $servicegroup = [];

    /*
     * 服务端连接
     * @var array|\swoole_client
     */
    protected static $client = [];

    /*
     * 异步列表
     * @var array
     */
    protected static $asynclist = [];

    /*
     * 异步请求返回的结果
     * @var array
     */
    protected static $asynresult = [];

    /*
     * 发送数据和接收数据的超时时间 单位s
     * @var integer
     */
    protected $timeout = 1;

    /*
     * 同步模式
     */
    const SYNC_MODE = 1;

    /*
     * 异步模式
     */
    const ASYNC_MODE = 2;

    /**
     * 同步请求
     * @param $url
     * @param $params
     * @return mixed
     */
    abstract public function syncRequest($url, $params);

    /**
     * 异步请求
     * @param $url
     * @param $params
     * @return mixed
     */
    abstract public function asyncRequest($url, $params);

    /**
     * 关闭服务端链接
     * @return mixed
     */
    abstract protected function _close();

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

    /**
     * 指定服务
     * @param $name
     * @return $this
     * @throws RpcException
     */
    public function setService($name)
    {
        if (!isset($this->servicegroup[$name])) {
            RpcException::invalidServices();
        }

        $this->currentService = [
            'name'  => ucfirst($name),
            'ip'    => $this->servicegroup[$name]['ip'],
            'port'  => $this->servicegroup[$name]['port']
        ];

        return $this;
    }

    public function getCurrentService()
    {
        return $this->currentService;
    }

}