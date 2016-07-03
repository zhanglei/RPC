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

//Monitor\Server::getInstance('swooletable')->run();
Monitor\Server::getInstance('redis')->run();
