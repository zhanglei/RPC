<?php

namespace Protocols;

use Packet\Format;

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
            return '10002:packet length invalid';
        }

        return unserialize($result);



//        if (DoraConst::SW_DATASIGEN_FLAG == true) {
//
//            $signedcode = substr($str, 4, 4);
//            $result = substr($str, 8);
//
//            //check signed
//            if (pack("N", crc32($result . DoraConst::SW_DATASIGEN_SALT)) != $signedcode) {
//                return self::packFormat("Signed check error!", 100010);
//            }
//
//            $len = $len - 4;
//
//        } else {
//            $result = substr($str, 4);
//        }
//        if ($len != strlen($result)) {
//            //结果长度不对
//            echo "error length...\n";
//
//            return self::packFormat("packet length invalid 包长度非法", 100007);
//        }
//        //if compress the packet
//        if (DoraConst::SW_DATACOMPRESS_FLAG == true) {
//            $result = gzdecode($result);
//        }
//        $result = unserialize($result);
//
//        return self::packFormat("OK", 0, $result);
        
        //return unserialize(trim($json));
    }

}