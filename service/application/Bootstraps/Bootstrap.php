<?php

class Bootstrap extends Yaf_Bootstrap_Abstract
{

    public function _initPlugins(Yaf_Dispatcher $dispatcher)
    {
        $dispatcher->registerPlugin(new SystemPlugin());
    }
    
}