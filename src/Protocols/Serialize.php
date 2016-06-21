<?php

namespace Protocols;

class Serialize implements Protocol
{
    public static $instance;

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
     * @param bool $protocol
     * @return string
     */
    public static function encode($value, $protocol = false)
    {
        $value = serialize($value);
        
        if ($protocol) {
            return pack('N', strlen($value)) . pack('N', self::PROTOCOLS_MODE_SERIALIZE) . $value;
        } else {
            return pack('N', strlen($value)) . $value;
        }
        
    }

    /**
     * 解包
     * @param $str
     * @param bool $protocol
     * @return bool|array
     */
    public static function decode($str, $protocol = false)
    {
        $header = substr($str, 0, 4);
        $len = unpack('Nlen', $header)['len'];
        
        if ($protocol) {
            $result = substr($str, 8);
        } else {
            $result = substr($str, 4);
        }

        if ($len != strlen($result)) {
            Exception::LengthInvalid();
        }

        return unserialize($result);

    }

}