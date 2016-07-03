<?php

namespace Monitor\Container;

interface ContainerInterface
{
    
    public static function getInstance();
    
    /**
     * 创建存储容器
     * @return mixed
     */
    public function create();

    /**
     * 生成服务配置
     * @return mixed
     */
    public function serverConfig();
    
}