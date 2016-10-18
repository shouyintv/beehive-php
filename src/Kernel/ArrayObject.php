<?php
namespace Beehive\Kernel;

use ArrayAccess;
use Countable;
use Iterator;

/**
 * 数据存储
 *
 * @author Ewenlaz
 */
class ArrayObject implements ArrayAccess, Countable, Iterator
{
    protected $storage = [];

    public function __construct(array $array)
    {
        $this->storage = $array;
    }

    public function offsetSet($offset, $value)
    {
        $this->storage[$offset] = $value;
    }

    public function offsetExists ($offset)
    {
        return isset($this->storage[$offset]);
    }

    public function offsetUnset($offset)
    {
        unset($this->storage[$offset]);
    }

    public function offsetGet($offset)
    {
        return isset($this->storage[$offset]) ? $this->storage[$offset] : null;
    }

    public function count()
    {
        return count($this->storage);
    }

    function rewind()
    {
        return reset($this->storage);
    }

    function current()
    {
        return current($this->storage);
    }

    function key()
    {
        return key($this->storage);
    }

    function next()
    {
        return next($this->storage);
    }

    function valid() {
        return key($this->storage) !== null;
    }

    public function __get($name)
    {
        return isset($this->storage[$name]) ? $this->storage[$name] : null;
    }

    public function __set($name, $val)
    {
        return $this->storage[$name] = $val;
    }

    public function pack()
    {
        return beehive_packet_pack($this->storage);
    }

    public function unpack($data)
    {
        return $this->storage = beehive_packet_unpack($stream);
    }
}
