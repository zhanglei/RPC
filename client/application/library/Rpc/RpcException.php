<?php

namespace Rpc;


class RpcException extends \Exception
{

    public static function connectFailed($string)
    {
        throw new self($string, 10001);
    }
    
    public static function packetInvalid()
    {
        throw new self('无效协议包', 10002);
    }

    public static function invalidServices()
    {
        throw new self('无效服务', 10003);
    }
    
    public static function reciveFailed($server)
    {
        throw new self($server . ' : recive wrong or timeout', 10004);
    }
    
    public static function serviceNotExist()
    {
        throw new self('服务不存在', 10005);
    }

    public static function MissingServiceList()
    {
        throw new self('缺少可用服务列表', 10006);
    }

}