<?php

class IndexController extends Yaf_Controller_Abstract
{
	
   public function messageAction()
   {
       \Packet\Response::packFormat('messageService : messageAction');
   }

    public function testAction()
    {
        \Packet\Response::packFormat('messageService : testAction');
    }

}