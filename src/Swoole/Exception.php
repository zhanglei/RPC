<?php

namespace Swoole;

class Exception extends \Exception
{

    public static function BadRequest()
    {
        throw new self('bad request', 10001);
    }
    
    public static function EmptyGuid()
    {
        throw new self('empty guid', 10002);
    }

}