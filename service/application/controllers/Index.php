<?php

class IndexController extends Yaf_Controller_Abstract
{
	
   public function userAction()
   {
       echo \Packet\Response::packFormat('userService : userAction');
   }
    
    public function testAction()
    {
        echo \Packet\Response::packFormat('userService : testAction');
    }

}