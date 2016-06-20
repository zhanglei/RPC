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

}