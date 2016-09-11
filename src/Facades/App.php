<?php
namespace Beehive\Facades;

/**
 * App Facade
 *
 * @author Ewenlaz
 */
class App extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'app';
    }
}