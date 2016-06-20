<?php
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
define('HTDOC_ROOT', __DIR__);

/*
 * 项目所在目录
 */
define('PROJECT_ROOT', dirname(HTDOC_ROOT));

/*
 * 应用程序所在目录
 */
define('APPLICATION_PATH', PROJECT_ROOT . DS . 'application');

/*
 * 主机名
 */
define('HOSTNAME', $_SERVER['HTTP_HOST']);

include PROJECT_ROOT . '/../vendor/autoload.php';

/*
 * 实例化应用，指定读取配置文件路径以及配置文件片段
 */
$app = new \Yaf_Application(PROJECT_ROOT . DS . 'config/application.ini', ENVIRON);

$app->
/*
 * 运行前调度
 */
bootstrap()->
/*
 * 程序入口
 */
run();