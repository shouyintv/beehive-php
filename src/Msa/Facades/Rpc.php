<?php
namespace Beehive\Msa\Facades;

use Beehive\Facades\Facade;

/**
 * Rpc Facade
 *
 * @author Ewenlaz
 */
class Rpc extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'rpc';
    }
}