<?php
namespace Beehive\Facades;

/**
 * Timer Facade
 *
 * @author Ewenlaz
 */
class Timer extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'timer';
    }
}