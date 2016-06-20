<?php

namespace Packet;

class Response
{

    /**
     * 拼装响应返回数据
     * @param array $data
     * @param string $message
     * @param int $code
     */
    public static function packFormat($data = [], $message = 'success', $code = 0)
    {
        \Yaf_Registry::get('HTTP_RESPONSE')->setBody(serialize($data), 'data');
        \Yaf_Registry::get('HTTP_RESPONSE')->setBody($message, 'message');
        \Yaf_Registry::get('HTTP_RESPONSE')->setBody($code, 'code');
    }

}