<?php

namespace Protocols;

class Exception extends \Exception 
{

    public static function LengthInvalid($project = '')
    {
        $project = $project ? $project . ' : ' : (defined('PROJECT_NAME') ? PROJECT_NAME . ' : ' : '');
        throw new self($project . 'packet length invalid', 20001);
    }
    
}