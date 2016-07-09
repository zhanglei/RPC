<?php

namespace Swoole\Client;

use Swoole\Packet\Format;

class SOA
{

    protected static $requestList = [];

    /**
     * 投递任务列表
     * @var array
     */
    protected static $taskList= [];

    /**
     * @var array
     */
    protected $serviceList = [];

    /**
     * @var string
     */
    protected $currentService;

    /**
     * @var array
     */
    protected $config = [];

    /**
     * @var string
     */
    protected $guid;

    /**
     * @var int
     */
    protected $timeout = 1;

    /**
     * @var int
     */
    protected $requestIndex = 0;

    /**
     * 同步模式
     * @var int
     */
    const SYNC_MODE = 1;

    /**
     * 异步模式
     * @var int
     */
    const ASYNC_MODE = 2;

    /**
     * 发送请求
     * @param $api
     * @param array $params
     * @param int $mode
     * @return bool|Result
     */
    public function call($api, $params = [], $mode = 2)
    {
        $this->guid = $this->generateGuid();

        $send_data = Format::packEncode(
            [
                'api'       => $api,
                'params'    => $params
            ],
            $mode,
            $this->guid
        );

        $client = $this->request($send_data);

        if ($client) {
            self::$requestList[$this->guid] = $client;
        }

        return $client;
    }

    /**
     * 投递到task处理
     * @param $api
     * @param array $params
     * @param int $mode
     * @return bool|Result
     */
    public function task($api, $params = [], $mode = 2)
    {
        $this->guid = $this->generateGuid();

        $send_data = Format::packEncode(
            [
                'cmd'       => 'task',
                'api'       => $api,
                'params'    => $params
            ],
            $mode,
            $this->guid
        );

        $client = $this->request($send_data);

        if ($client) {
            self::$taskList[$this->guid] = $client;
        }

        return $client;
    }

//    public function resultData()
//    {
//        while (true)
//        {
//            if (empty(self::$requestList)) {
//                break;
//            }
//
//            foreach (self::$requestList as $result_obj) {
//                if ($result_obj->socket !== null) {
//                    $result = $result_obj->socket->recv();
//
//                    if (empty($result)) {
//                        $this->resultError($result_obj, Result::ERR_CLOSED);
//                        continue;
//                    } else {
//                        $header = Format::packDecodeHeader($result);
//
//                        //错误的包头
//                        if ($header == false) {
//                            $this->resultError($result_obj, Result::ERR_HEADER);
//                            continue;
//                        }
//
//                        if (Format::checkHeaderLength($header, $result) == false) {
//                            $this->resultError($result_obj, Result::ERR_LENGTH);
//                            continue;
//                        }
//
//                        if ($header['guid'] != $result_obj->requestId) {
//                            var_dump($header);
//                            var_dump($result);
//                            var_dump($header['guid'], $result_obj->requestId);
//                            $this->resultError($result_obj, Result::ERR_GUID);
//                            continue;
//                        }
//
//                        $data = Format::packDecode($result, $header['type']);
//
//                        //解包失败
//                        if ($data === false) {
//                            $this->resultError($result_obj, Result::ERR_UNPACK);
//                            continue;
//                        }
//
//                        if ($data['code'] != 0) {
//                            $result_obj->code = $result_obj['code'];
//                            $result_obj->data = null;
//                            continue;
//                        }
//
//                        $result_obj->code = 0;
//                        $result_obj->data = $data['data'];
//                    }
//
//                    //$read[$obj->requestId] = $obj->socket;
//                    //$result_obj->socket->close();
//
//                    unset($result_obj->socket);
//
//                    unset(self::$requestList[$result_obj->requestId]);
//                }
//
//
//            }
//
//            self::$requestList = [];
//        }
//    }

    public function resultData($timeout = 1)
    {
        $success_num = 0;
        while (true) {
            $write = $error = $read = [];

            if (empty(self::$requestList)) {
                break;
            }

            foreach (self::$requestList as $_obj) {
                if ($_obj->socket !== null) {
                    $read[$_obj->requestId] = $_obj->socket;
                }
            }

            if (empty($read)) {
                break;
            }

            $n = swoole_client_select($read, $write, $error, $timeout);

            if ($n > 0) {
                //可读
                foreach ($read as $key => $sock) {

                    /**
                     * @var $result_obj Result
                     */
                    $result_obj = self::$requestList[$key];
                    $result = $result_obj->socket->recv();

                    if (empty($result)) {
                        $this->resultError($result_obj, Result::ERR_CLOSED);
                        continue;
                    } else {
                        $header = Format::packDecodeHeader($result);

                        //错误的包头
                        if ($header == false) {
                            $this->resultError($result_obj, Result::ERR_HEADER);
                            continue;
                        }

                        if (Format::checkHeaderLength($header, $result) == false) {
                            $this->resultError($result_obj, Result::ERR_LENGTH);
                            continue;
                        }

                        if ($header['guid'] != $result_obj->requestId) {
                            $this->resultError($result_obj, Result::ERR_GUID);
                            continue;
                        }

                        $data = Format::packDecode($result, $header['type']);

                        //解包失败
                        if ($data === false) {
                            $this->resultError($result_obj, Result::ERR_UNPACK);
                            continue;
                        }

                        if ($data['code'] != 0) {
                            $result_obj->code = $data['code'];
                            $result_obj->data = null;
                            continue;
                        }

                        $result_obj->code = 0;
                        $result_obj->data = $data['data'];

                        $success_num++;
                        unset(self::$requestList[$result_obj->requestId]);
                    }
                }
            }
        }
        
        return $success_num;
    }

    /**
     * 注意使用该方法  task主要用于处理  逻辑时间长  并且不需要等待返回的处理
     * 如果逻辑时间过长  使用该方法  会造成同步等待
     * @param int $timeout
     * @return int
     */
    public function resultTaskData($timeout = 1)
    {
        $success_num = 0;
        while (true) {
            $write = $error = $read = [];

            if (empty(self::$taskList)) {
                break;
            }

            foreach (self::$taskList as $_obj) {
                if ($_obj->socket !== null) {
                    $read[$_obj->requestId] = $_obj->socket;
                }
            }

            if (empty($read)) {
                break;
            }

            $n = swoole_client_select($read, $write, $error, $timeout);

            if ($n > 0) {
                //可读
                foreach ($read as $key => $sock) {

                    /**
                     * @var $result_obj Result
                     */
                    $result_obj = self::$taskList[$key];
                    $result = $result_obj->socket->recv();

                    if (empty($result)) {
                        $this->resultError($result_obj, Result::ERR_CLOSED);
                        continue;
                    } else {
                        $header = Format::packDecodeHeader($result);

                        //错误的包头
                        if ($header == false) {
                            $this->resultError($result_obj, Result::ERR_HEADER);
                            continue;
                        }

                        if (Format::checkHeaderLength($header, $result) == false) {
                            $this->resultError($result_obj, Result::ERR_LENGTH);
                            continue;
                        }

                        if ($header['guid'] != $result_obj->requestId) {
                            $this->resultError($result_obj, Result::ERR_GUID);
                            continue;
                        }

                        $data = Format::packDecode($result, $header['type']);

                        //解包失败
                        if ($data === false) {
                            $this->resultError($result_obj, Result::ERR_UNPACK);
                            continue;
                        }

                        //投递task成功
                        if ($data['code'] == Result::SUCCESS_TASK) {
                            $result_obj->code = Result::WAIT_TASK_RES;
                            $result_obj->data = null;
                            continue;
                        }

                        if ($data['code'] != 0) {
                            $result_obj->code = $data['code'];
                            $result_obj->data = null;
                            continue;
                        }

                        $result_obj->code = 0;
                        $result_obj->data = $data['data'];

                        $success_num++;
                        unset(self::$taskList[$result_obj->requestId]);
                    }
                }
            }
        }

        return $success_num;
    }

    /**
     * 设置服务列表
     * @param $list
     * @return $this
     */
    public function setServiceList($list)
    {
        if (is_string($list)) {
            if (!is_file($list)) {
                trigger_error($list . ' configuration does not exist', E_USER_WARNING);
                return false;
            }

            $list = parse_ini_file($list, true);

        } else if (is_array($list)) {
            if (empty($list)) {
                trigger_error('configure is empty', E_USER_WARNING);
                return false;
            }
        } else {
            trigger_error('parameter array or file path', E_USER_WARNING);
            return false;
        }

        $this->serviceList = $list;

        return $this;
    }

    /**
     * 指定服务
     * @param $name
     * @return $this
     */
    public function setService($name)
    {
        if (!isset($this->serviceList[$name])) {
            trigger_error('service does not exist', E_USER_WARNING);
            return false;
        }

        $this->currentService = $name;

        return $this;
    }

    public function setConfig(array $config)
    {
        $this->config = $config;
    }

    public function setTimeOut($time)
    {
        $this->timeout = $time;
    }

    protected function request($send_data)
    {
        $result_obj = new Result($this);
        $result_obj->index = $this->requestIndex ++;
        
        if ($this->connectToServer($result_obj) === false) {
            $result_obj->code = Result::ERR_CONNECT;
            return false;
        }
        
        $result_obj->requestId = $this->guid;

        if ($result_obj->socket->send($send_data) === false) {
            $result_obj->code = Result::ERR_SEND;
            unset($result_obj->socket);
            return false;
        }

        //self::$requestList[$this->guid] = $result_obj;
        
        return $result_obj;
    }

    protected function connectToServer(Result $result_obj)
    {

        if (empty($this->currentService)) {
            $key = array_rand($this->serviceList);
            $connect_info = $this->serviceList[$key];
        } else {
            $key = array_rand($this->serviceList[$this->currentService]);
            $connect_info = $this->serviceList[$this->currentService][$key];
        }

        $key = $connect_info['host'] . ':' . $connect_info['port'] . '-' . $result_obj->index;
        $client = new \swoole_client(SWOOLE_SOCK_TCP | SWOOLE_KEEP, SWOOLE_SOCK_SYNC, $key);
        if ($this->config) {
            $client->set($this->config);
        }

        $ret = $client->connect($connect_info['host'], $connect_info['port'], $this->timeout);

        if ($ret === false) {
            return false;
        }

        $result_obj->socket = $client;

        return true;
    }

    protected function generateGuid()
    {
        $us = strstr(microtime(), ' ', true);
        return intval(strval($us * 1000 * 1000) . rand(100, 999));
    }

    protected function resultError(Result $result_obj, $erron)
    {
        $result_obj->code = $erron;
        unset(self::$requestList[$result_obj->requestId], $result_obj->socket);
    }

    protected function clean()
    {
        $this->currentService = null;
    }
}

/**
 * SOA服务请求结果对象
 * Class SOA_Result
 * @package Swoole\Client
 */
class Result
{
    public $id;
    public $code = self::ERR_NO_READY;
    public $msg;
    public $data = null;
    public $index;

    /**
     * 请求串号
     */
    public $requestId;

    /**
     * @var \Swoole\Client
     */
    public $socket = null;

    /**
     * @var SOA
     */
    protected $soa_client;

    const WAIT_TASK_RES  = 7001; //等待投递结果

    const ERR_NO_READY   = 8001; //未就绪
    const ERR_CONNECT    = 8002; //连接服务器失败
    const ERR_TIMEOUT    = 8003; //服务器端超时
    const ERR_SEND       = 8004; //发送失败
    const ERR_SERVER     = 8005; //server返回了错误码
    const ERR_UNPACK     = 8006; //解包失败了

    const ERR_HEADER     = 8007; //错误的协议头
    const ERR_LENGTH     = 8008; //错误的长度
    const ERR_CLOSED     = 8009; //连接被关闭
    const ERR_GUID       = 8010; //错误的GUID

    const SUCCESS_TASK   = 9000; //投递task成功
    
    public function __construct($soa_client)
    {
        $this->soa_client = $soa_client;
    }

    public function getResult($timeout = 1)
    {
        if ($this->code == self::ERR_NO_READY)
        {
            $this->soa_client->resultData($timeout);
        }
        return $this->data;
    }

    public function getTaskResult($timeout = 1)
    {
        if ($this->code == self::ERR_NO_READY)
        {
            $this->soa_client->resultTaskData($timeout);
        }
        return $this->data;
    }

}