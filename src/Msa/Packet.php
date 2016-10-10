<?php
namespace Beehive\Msa;

/**
 * 微服务协议
 *
 * @author Ewenlaz
 * new JsonPacket  
 */

class Packet
{
    const FLAG_RESPONSE = 1; //是否是返回协议
    const FLAG_EVENT_PUBLISH = 2; //事件发布协议
    const FLAG_EVENT_SUBSCRIBE = 4; //事件订阅协议
    const FLAG_REQUIRED = 8; //远程必须要返回
    const FLAG_BROADCAST = 16; //是否是广播协议
    const HEADER_LEN = 22;

    protected $packet = [];

    protected $stream = null;

    public function __construct($service = '')
    {
        if ($service) {
            $service = crc32($service);
            $this->packet['service'] = $service;
        }
    }

    public function inStream($stream)
    {
        $this->stream = $stream;
        $this->unpack();
    }

    public function unpack()
    {
        $this->packet = beehive_packet_unpack($this->stream);
    }

    public function pack()
    {
        return beehive_packet_pack($this->packet);
    }

    public function __get($name)
    {
        return isset($this->packet[$name]) ? $this->packet[$name] : null;
    }

    public function __set($name, $val)
    {
        return $this->packet[$name] = $val;
    }
}
