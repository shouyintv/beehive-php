<?php
namespace Beehive\Logger\Adapter;

use Phalcon\Logger\Adapter;
use Phalcon\Logger\Formatter;
use Phalcon\Logger\Formatter\Line;
use Phalcon\Logger\AdapterInterface;
/**
 * 控制台输出
 *
 * @author ewenlaz
 */
class Console extends Adapter implements AdapterInterface
{
    public function loginternal($message, $type, $timestamp, $context = null)
    {
        $data = $this->getFormatter()->format($message, $type, $timestamp, $context);
        print $data;
    }

    public function getFormatter()
    {
        if (!$this->_formatter instanceof Formatter) {
            $this->_formatter = new Line(null, 'Y-m-d H:i:s');
        }
        return $this->_formatter;
    }

    public function close()
    {
    }
}