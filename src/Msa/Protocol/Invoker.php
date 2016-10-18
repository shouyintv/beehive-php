<?php
namespace Beehive\Msa\Protocol;

use Serializable;
use Beehive\Kernel\ArrayObject;
use Beehive\Contracts\Connection\Connection;
/**
 * 协议包封装
 *
 * @author Ewenlaz
 */
abstract class Invoker extends ArrayObject implements Serializable
{
    protected $packetObjectStorage = null;
    protected $invokerCallable = null;
    protected $onResultCallable = null;

    public function __construct(array $stoarge)
    {
        parent::__construct($stoarge);
        $this->packetObjectStorage = new Packet([]);
    }

    public function setServiceName($name = '')
    {
        $this->packetObjectStorage->name = $name;
        $this->packetObjectStorage->service = crc32($name);
        return $this;
    }

    public function setPacket(Packet $packet)
    {
        $this->packetObjectStorage = $packet;
        return $this;
    }

    public function getPacket()
    {
        return $this->packetObjectStorage;
    }

    abstract public function serialize();
    abstract public function unserialize($serialized = '');

    public function then(callable $callable)
    {
        $this->onResultCallable = $callable;
        return $this;
    }

    public function onResult(Invoker $invoker)
    {
        return call_user_func_array($this->onResultCallable, [$invoker]);
    }

    public function setInvokerCallable(callable $callable)
    {
        $this->invokerCallable = $callable;
    }

    public function invoke()
    {
        if (!$this->invokerCallable) {
            throw new \Exception('InvokerCallable is null', 1);
        }
        return call_user_func_array($this->invokerCallable, [$this]);
    }

    public function isSuccess()
    {
        return $this->packetObjectStorage->code === 0;
    }

    public function getCode()
    {
        return $this->packetObjectStorage->code;
    }
}
