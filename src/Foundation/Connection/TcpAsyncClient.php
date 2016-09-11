<?php
namespace Beehive\Foundation\Connection;

use Beehive\Foundation\Event\Emitter;
use Beehive\Contracts\Connection\Connection;
use Swoole\Client;
use RuntimeException;
use Log;

class TcpAsyncClient extends Emitter implements Connection 
{
    protected $socket = null;
    protected $remoteAddr = '0.0.0.0';
    protected $remotePort = 0;
    protected $option = [];

    public function __construct($host = '', $port = 0)
    {
        $this->remoteAddr = $host;
        $this->remotePort = $port;
        $isUnixDomain = strpos($host, '.sock') ? true : false;
        if ($isUnixDomain) {
            $this->remotePort = 0;
        }
        $type = $isUnixDomain ? SWOOLE_SOCK_UNIX_STREAM : SWOOLE_SOCK_TCP;
        $this->socket = new Client($type, SWOOLE_SOCK_ASYNC);
        $this->socket->on('connect', function ($cli) {
            $this->emit(static::EVENT_CONNECT, [$this]);
        });

        $this->socket->on('error', function ($cli) {
            Log::error('connection error');
            $this->emit(static::EVENT_ERROR, [$this]);
            $this->eventListeners = [];
            $this->socket = null;
        });
        $this->socket->on('close', function ($cli) {
            Log::error('connection close');
            $this->emit(static::EVENT_CLOSE, [$this]);
            $this->eventListeners = [];
            $this->socket = null;
        });
        $this->socket->on('receive', function ($cli, $data) {
            $this->emit(static::EVENT_RECEIVE, [$this, $data]);
        });
    }

    public function send($data = '')
    {
        if (!$this->socket) {
            throw new RuntimeException('socket is close');
        }
        return $this->socket->send($data);
    }

    public function close()
    {
        if (!$this->socket) {
            throw new RuntimeException('socket is close');
        }
        return $this->socket->close();
    }
    public function connect()
    {
        if (!$this->socket) {
            throw new RuntimeException('socket is close');
        }
        $this->socket->set($this->option);
        return $this->socket->connect($this->remoteAddr, $this->remotePort);
    }

    public function setOption(array $option = []) {
        $this->option = $option;
    }

    public function getAddress()
    {
        return [$this->remoteAddr, $this->remotePort];
    }
}