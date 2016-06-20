<?php

class SiteController extends Yaf_Controller_Abstract
{
	
   public function indexAction()
   {
       $user_info = \Rpc\Swoole::instance()->UserService()->doRequest('/index/user', ['php' => 'hello']);
       $user_info2 = \Rpc\Swoole::instance()->UserService()->doRequest('/index/test');

       $message_info = \Rpc\Swoole::instance()->MessageService()->doRequest('/index/message/');
       $message_info2 = \Rpc\Swoole::instance()->MessageService()->doRequest('/index/test/');
       var_dump($user_info, $user_info2, $message_info, $message_info2);
   }
    
}