<?php
namespace Beehive\Kernel;

use Beehive\Facades\Facade;
use Phalcon\Di;

/**
 * App
 *
 * @author Ewenlaz
 */
class Application extends Di
{
    public function bootstrap()
    {
        Facade::setFacadeApplication($this);
        AliasLoader::getInstance($this->make('config')->get('app.aliases')->toArray())->register();
    }

    public function make($name)
    {
        return $this->get($name);
    }
}