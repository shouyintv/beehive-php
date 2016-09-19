<?php
namespace Beehive\Msa;

/**
 * 微服务JSON协议
 *
 * @author Ewenlaz
 * new JsonPacket  
 */

class JsonPacket extends Packet
{
    public function unpack()
    {
        parent::unpack();
        $this->packet['body'] = json_decode($this->packet['body'], true);
    }
    public function pack()
    {
        $this->packet['body'] = json_encode($this->packet['body']);
        return parent::pack();
    }
}