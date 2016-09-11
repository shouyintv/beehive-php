<?php
namespace Beehive\Storage\SortedSet;

use Log;
use Swoole\Timer;
use Exception;
use Swoole\Table;
use App;
use Redis as RedisStorage;

/**
 * App
 *
 * @author Ewenlaz
 */

class Redis implements SortedSetInterface
{
    protected $redis = null;
    public function __construct(RedisStorage $redis)
    {
        $this->redis = $redis;
    }
    public function add($key, $data = '', $score = 0)
    {
        return $this->redis->zAdd($key, $score, $data);
    }
    public function rangeByScore($key, $min = 0, $max = 0)
    {
        return $this->redis->zRangeByScore($key, $min, $max);
    }
}