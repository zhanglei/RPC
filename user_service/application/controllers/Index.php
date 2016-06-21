<?php

class IndexController extends Yaf_Controller_Abstract
{
	
   public function userAction()
   {
       //\Packet\Response::packFormat('userService : userAction');
       throw new \Exception('aaaa');
   }
    
    public function testAction()
    {
        \Packet\Response::packFormat('userService : testAction');
    }

}