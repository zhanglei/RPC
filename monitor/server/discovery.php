<?php

/*
 * 目录斜杆
 */
define('DS', DIRECTORY_SEPARATOR);

/*
 * 网站根目录
 */
define('PROJECT_ROOT', realpath(dirname(__DIR__)));

include PROJECT_ROOT . '/../vendor/autoload.php';

//\Swoole\Monitor\Discovery::getInstance(PROJECT_ROOT . DS . 'config/monitor.ini')->run();
$server = new \Swoole\Monitor\Discovery(PROJECT_ROOT . DS . 'config/monitor.ini');
$server->run();
