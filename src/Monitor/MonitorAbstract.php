<?php

namespace Monitor;

abstract class MonitorAbstract
{
    /**
     * 启动服务
     * @return mixed
     */
    abstract public function run();

    /**
     * 服务上报
     * @param $data
     * @return mixed
     */
    abstract public function report($data);

    /**
     * 生成服务配置
     * @return mixed
     */
    abstract protected function serverConfig();
    
}