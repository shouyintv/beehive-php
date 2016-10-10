<?php
namespace Beehive\Pdo;

use PDO;

/**
 * App Facade
 *
 * @author Ewenlaz
 */
class DbManager
{
    protected $instances = [];
    protected $configs = [];
    public function __construct($configs = [])
    {

    }

    public function get($instance, $new = false) {
        $instance = strtolower($instance);
        if (!isset($this->instances[$instance]) || $new) {
            if (!isset($this->configs[$instance])) {
                throw new \Exception(sprintf('%s db can`t instance!', $instance), 1);
            }
        }
    }
}