<?php
namespace Beehive\Server;

use Swoole\Http\Server;

/**
 * App
 *
 * @author Ewenlaz
 */
class Http extends Server// implements ServerInterface
{
    public function __construct($ip, $port, $mode = SWOOLE_BASE, $flag = SWOOLE_SOCK_TCP)
    {
        parent::__construct($ip, $port, $mode, $flag);
        $this->on('request', [$this, 'onRequest']);
    }

    public function onRequest($request, $response)
    {
        //构造输入输出。。。。
        $service = new \Example\HttpProvider\Service\Test;
        $service();
        $response->end('xxxxx');
    }
}