<?php

namespace Packet;

class Exception extends \Exception 
{

    public static function PackageInvalid($project = '')
    {
        $project = $project ? $project . ' : ' : (defined('PROJECT_NAME') ? PROJECT_NAME . ' : ' : '');
        throw new self($project . 'invalid package', 30001);
    }
    
}