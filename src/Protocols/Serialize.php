<?php

namespace Protocols;

class Serialize implements Protocol
{
    public static $instance;
    
    const PROTOCOLS_MODE = 1;

    public static function getInstance()
    {
        if (!self::$instance instanceof self) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * 打包
     * @param $value
     * @return string
     */
    public static function encode($value)
    {
        $value = serialize($value);
        return pack('N', strlen($value)) . pack('N', self::PROTOCOLS_MODE_SERIALIZE) . $value;
        
    }

    /**
     * 解包
     * @param $str
     * @return bool|array
     */
    public static function decode($str)
    {
        $header = substr($str, 0, 4);
        $len = unpack('Nlen', $header)['len'];

        $result = substr($str, 8);

        if ($len != strlen($result)) {
            Exception::LengthInvalid();
        }

        return unserialize($result);

    }

}