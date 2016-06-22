<?php

class SiteController extends Yaf_Controller_Abstract
{
	
   public function indexAction()
   {
       $user_info = \Rpc\Swoole::instance()->UserService()->asyncRequest('/index/user', ['php' => 'hello']);
       var_dump($user_info);

       $message_info = \Rpc\Swoole::instance()->MessageService()->syncRequest('/index/message/');
       var_dump($message_info);


       var_dump(\Rpc\Swoole::instance()->getAsyncData());

   }
    
}