<?php

class IndexController extends Yaf_Controller_Abstract
{

    public function init()
    {
    }

    public function indexAction()
    {
        //throw new \Exception('adsad');
        //echo 'aaaa';
        $this->getResponse()->setBody("Hello");
        //$response->setHeader($this->getRequest()->getServer('SERVER_PROTOCOL'), '404 Not Found');
        //$response->setBody("Hello")->setBody(" World", "footer");
        $this->getResponse()->response();
    }

}