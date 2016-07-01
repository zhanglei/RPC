<?php

namespace Packet;

use Protocols\Json;
use Protocols\Protocol;
use Protocols\Serialize;

class Format
{
    
    /**
     * 拼装返回数据
     * @param string $data
     * @param string $message
     * @param int $code
     * @return array
     */
    public static function packFormat($data = '', $message = 'success', $code = 0)
    {
        $pack = [
            'code'      => $code,
            'message'   => $message,
            'data'      => $data
        ];

        return $pack;
    }
    
    /**
     * 解包
     * @param $pack
     * @param int $protocol_mode
     * @return array
     */
    public static function packDecode($pack, $protocol_mode = 0)
    {
        switch ($protocol_mode) {
            case Protocol::PROTOCOLS_MODE_JSON :
                $pack = Json::decode($pack);
                break;
            case Protocol::PROTOCOLS_MODE_SERIALIZE :
                $pack = Serialize::decode($pack);
                break;
            default:
                $pack = Json::decode($pack);
                break;
        }
        
        return $pack;
    }

    /**
     * 打包
     * @param $data
     * @param int $protocol_mode
     * @return string
     */
    public static function packEncode($data, $protocol_mode = 0)
    {
        switch ($protocol_mode) {
            case Protocol::PROTOCOLS_MODE_JSON :
                $data = Json::encode($data);
                break;
            case Protocol::PROTOCOLS_MODE_SERIALIZE :
                $data = Serialize::encode($data);
                break;
            default:
                $data = Json::encode($data);
                break;
        }

        return $data;
    }
}