<?php

class ErrorController extends Yaf_Controller_Abstract
{
    public function init()
    {
        //
    }
    
    public function errorAction($exception)
    {
        echo '<pre>' . $exception->getMessage() . '<pre>';
    }
    

}