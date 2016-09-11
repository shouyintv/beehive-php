<?php
namespace Beehive\Facades;

/**
 * Log Facade
 *
 * @author Ewenlaz
 */
class Log extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'log';
    }
}