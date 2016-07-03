<?php

class SiteController extends Yaf_Controller_Abstract
{
	
   public function indexAction()
   {

       //同步调用
       $user_info = \Rpc\Swoole::instance()->UserService()->syncRequest('/user/index/user', ['php' => 'hello']);
       var_dump($user_info);

       //异步调用，返回guid
       $guid = \Rpc\Swoole::instance()->UserService()->asyncRequest('/user/index/test');
       var_dump($guid);

       //获取所有异步结果，数组形式key为异步调用的guid
       var_dump(\Rpc\Swoole::instance()->getAsyncData());

   }
    
}