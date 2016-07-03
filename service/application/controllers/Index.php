<?php

class IndexController extends Yaf_Controller_Abstract
{
	
   public function userAction()
   {
       $params = $this->getRequest()->getParam('php');
       echo \Packet\Response::packFormat(
           [
               $params
           ]
       );
   }
    
    public function testAction()
    {
        echo \Packet\Response::packFormat('userService : testAction');
    }

}