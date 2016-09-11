<?php
namespace Beehive\Deployer;

/**
 * Deployer Adapter
 *
 * @author Ewenlaz
 */
abstract class Adapter
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