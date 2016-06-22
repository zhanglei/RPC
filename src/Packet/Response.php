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
        \Yaf_Registry::get('HTTP_RESPONSE')->setBody(serialize($data))->setBody($message, 'message')->setBody($code, 'code');
    }

}