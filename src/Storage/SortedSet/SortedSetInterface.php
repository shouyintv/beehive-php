<?php
namespace Beehive\Storage\SortedSet;

use Log;
use Swoole\Timer;
use Exception;
use Swoole\Table;
use App;

/**
 * App
 *
 * @author Ewenlaz
 */

interface SortedSetInterface
{
    public function add($key, $data = '', $score = 0);
    public function rangeByScore($key, $min = 0, $max = 0);
}