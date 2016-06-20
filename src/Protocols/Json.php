<?php

namespace Protocols;

class Json implements Protocol
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
        $value = json_encode($value);

        if ($protocol) {
            return pack('N', strlen($value)) . pack('N', self::PROTOCOLS_MODE_JSON) . $value;
        } else {
            return pack('N', strlen($value)) . $value;
        }
    }

    /**
     * 解包
     * @param $json
     * @param bool $protocol
     * @return array
     */
    public static function decode($json, $protocol = false)
    {
        $header = substr($json, 0, 4);
        $len = unpack('Nlen', $header)['len'];

        if ($protocol) {
            $result = substr($json, 8);
        } else {
            $result = substr($json, 4);
        }

        if ($len != strlen($result)) {
            return '10002:packet length invalid';
        }
        
        return json_decode($json, true);
    }

}