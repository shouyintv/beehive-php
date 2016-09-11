<?php
namespace Beehive\Logger\Formatter;

use Phalcon\Logger;

/**
 * 控制台输出格式化
 *
 * @author ewenlaz
 */

//21FATAL=Bright Red, ERROR=Bright Magenta, WARN=Bright Yellow, INFO=Bright Green, DEBUG=Bright Cyan, TRACE=Bright White
class Console extends Line
{
    protected $colorMap = [
        Logger::DEBUG => '0,0',
        Logger::ERROR => '0,31',
        Logger::WARNING => '0,33',
        Logger::CRITICAL => '0,35',
        Logger::CUSTOM => '0,21',
        Logger::ALERT => '0,34',
        Logger::NOTICE => '0,21',
        Logger::INFO => '0,32',
        Logger::EMERGENCY => '0,36',
        Logger::SPECIAL => '0,0'
    ];

    public function format($message, $type, $timestamp, $context = null)
    {
        $message = parent::format($message, $type, $timestamp, $context);
        if (!isset($this->colorMap[$type])) {
            $type = Logger::CUSTOM;
        }
        return "\033[".$this->colorMap[$type]."m" . $message . "\033[0m";
    }
}