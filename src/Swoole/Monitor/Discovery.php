<?php

namespace Swoole\Monitor;

use Swoole\Cache\Redis;
use Swoole\Packet\Format;
use Swoole\Server\Server;

class Discovery extends Server
{
    
    protected static $handle;
    
    protected $configPath;

    protected function initHandleInstance()
    {
        if (!self::$handle) {
            self::$handle = Redis::getInstance($this->config['redis']);
        }
    }

    protected function generalConfig($data)
    {
        $this->initHandleInstance();

        $_redis_data = [
            'service' => strtolower($data['service']),
            'host' => $data['host'],
            'port' => $data['port']
        ];
        self::$handle->getHandle()->sAdd('serverlist', json_encode($_redis_data));
        self::$handle->set($data['host'] . '_' . $data['port'] . '_time', $data['time']);

        $server_list = [];
        $content = '';
        $redis_list = self::$handle->getHandle()->smembers('serverlist');

        if ($redis_list) {
            foreach ($redis_list as $node) {
                $info = json_decode($node, true);

                $time = self::$handle->get($info['host'] . '_' . $info['port'] . '_time');
                if (time() - $time > 20) {
                    continue;
                }

                $server_list[$info['service']][] = $info;
            }

            if (count($server_list) > 0) {
                foreach ($server_list as $key => $info) {
                    if ($key) {
                        $content .= '[' . $key . ']' . PHP_EOL;
                    }

                    foreach ($info as $node) {
                        $_ip = str_replace('.', '_', $node['host']);
                        $content .= $_ip . '_' . $node['port'] . '[host] = ' . $node['host'] . PHP_EOL;
                        $content .= $_ip . '_' . $node['port'] . '[port] = ' . $node['port'] . PHP_EOL;
                    }
                }

                file_put_contents($this->configPath, $content);
            }
        }
    }

    protected function setConfigPath($path)
    {
        $this->configPath = $path;
    }
    
    /**
     * @param \swoole_server $server
     * @param int $fd
     * @param int $from_id
     * @param array $data
     * @param array $header
     * @return mixed|void
     */
    public function doWork(\swoole_server $server, $fd, $from_id, $data, $header)
    {

        if (empty($data['host']) || empty($data['port']) || empty($data['time'])) {
            return $this->sendMessage($fd, Format::packFormat('', '', self::ERR_PARAMS), $header['type']);
        }

        $this->setConfigPath(realpath('../../') . '/client/config/serverlist.ini');
        $this->generalConfig($data);

        return $this->sendMessage($fd, Format::packFormat('', 'general config success'), $header['type'], $header['guid']);
    }


}