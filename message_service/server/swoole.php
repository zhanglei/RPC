<?php

/*
 * 项目名称
 */
define('PROJECT_NAME', 'MessageService');

/*
 * 目录斜杆
 */
define('DS', DIRECTORY_SEPARATOR);

/*
 * 环境
 */
define('ENVIRON', 'develop');

/*
 * 网站根目录
 */
define('PROJECT_ROOT', realpath(dirname(__DIR__)));

/*
 * YAF应用程序所在目录
 */
define('APPLICATION_PATH', PROJECT_ROOT . DS . 'application');

include PROJECT_ROOT . '/../vendor/autoload.php';

$server = \Swoole\Server::getInstance();
$server->run();
