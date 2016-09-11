<?php
namespace Beehive\Deployer\Adapter;

use Beehive\Deployer\Adapter;

/**
 * Deployer Adapter
 *
 * @author Ewenlaz
 */
class Pm2 extends Adapter
{
    protected $deploy = [];
    public function __construct($deploy = [])
    {
        $this->deploy = $deploy;
    }

    abstract public function deploy();
    abstract public function reload();
    abstract public function stop();
    abstract public function stat();
}