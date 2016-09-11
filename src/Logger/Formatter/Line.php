<?php
namespace Beehive\Logger\Formatter;

use Phalcon\Logger\Formatter\Line as LineFormatter;
use Phalcon\Logger;

/**
 * 控制台输出格式化
 *
 * @author ewenlaz
 */

class Line extends LineFormatter
{

    public function __construct($format = null, $dataFormat = null)
    {
        if (!$dataFormat) {
            $dataFormat = 'Y-m-d H:i:s';
        }
        parent::__construct($format, $dataFormat);
    }

    public function format($message, $type, $timestamp, $context = null)
    {
        if (is_array($context)) {
            $data = [];
            foreach ($context as $k => &$v) {
                $v = is_string($v) ? $v : json_encode($v);
                $data[] = $k . '=' . $v;
            }
            $message .= ' > ' . implode(', ', $data);
        }
        return parent::format($message, $type, $timestamp, $context);
    }
}