<?php

class IndexController extends Yaf_Controller_Abstract
{
	
   public function userAction()
   {
       \Packet\Response::packFormat('userService : userAction');
   }
    
    public function testAction()
    {
        \Packet\Response::packFormat('userService : testAction');
    }

}