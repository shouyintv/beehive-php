<?php
namespace Beehive\Contracts\Connection;

use Beehive\Contracts\Event\Emitter;

/**
 * 数据连接接口
 *
 * @author Ewenlaz
 */
interface Connection extends Emitter
{
    const EVENT_CONNECT = 1;
    const EVENT_RECEIVE = 2;
    const EVENT_CLOSE = 3;
    const EVENT_ERROR = 4;

    public function setOption(array $option);
    public function send($data = '');
    public function close();
    public function connect();
    public function getAddress();
}
