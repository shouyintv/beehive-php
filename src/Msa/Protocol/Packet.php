<?php
namespace Beehive\Msa\Protocol;

use Beehive\Kernel\ArrayObject;

/**
 * 协议包封装
 *
 * @author Ewenlaz
 */
class Packet extends ArrayObject
{
    const FLAG_RESPONSE = 1;
    const FLAG_EVENT = 2;
    public function pack()
    {
        return beehive_packet_pack($this->storage);
    }

    public function unpack($stream)
    {
        return $this->storage = beehive_packet_unpack($stream);
    }
}
