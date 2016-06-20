<?php

class Bootstrap extends Yaf_Bootstrap_Abstract
{
    public function _initRoute(Yaf_Dispatcher $dispatcher)
    {
        $dispatcher->getRouter()->addConfig(
            (new Yaf_Config_Ini(PROJECT_ROOT . '/config/routes.ini', ENVIRON))->get('routes')
        );
    }

    public function _initPlugins(Yaf_Dispatcher $dispatcher)
    {
        $dispatcher->registerPlugin(new SystemPlugin());
    }
    
}