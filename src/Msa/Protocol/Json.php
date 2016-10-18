<?php
namespace Beehive\Msa\Protocol;

use Beehive\Kernel\ArrayObject;

/**
 * 协议包封装
 *
 * @author Ewenlaz
 */
class Json extends Invoker
{
    public function serialize()
    {
        return json_encode($this->storage);
    }

    public function unserialize($serialized = '')
    {
        return $this->storage = json_decode($serialized, true);
    }

    public function setData($data)
    {
        $this->storage = $data;
        return $this;
    }

    public function getData()
    {
        return $this->storage;
    }
}
