<?php
namespace Beehive\Server;

use Beehive\Foundation\Event\Emitter;

abstract class Command Extends Emitter
{
    const EVENT_BOOT = 1;
    const EVENT_START = 2;

    protected $serverName = 'NoneSvr';
    protected $serverId = 0;

    abstract public function start();

    public function setServerName($name)
    {
        $this->serverName = $name;
    }

    public function setServerId($id)
    {
        $this->serverId = $id;
    }
}