<?php

namespace Protocols;

interface Protocol
{
    const PROTOCOLS_MODE_JSON = 0;
    
    const PROTOCOLS_MODE_SERIALIZE = 1;

    static function encode($buffer);
    
    static function decode($buffer);
    
}