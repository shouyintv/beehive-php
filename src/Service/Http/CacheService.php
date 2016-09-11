<?php
namespace Beehive\Service\Http;

use Log;
use App;

abstract class CacheService extends Service
{
    public function invoke() {
        throw new Exception("Error Processing Request", 1);
    }
    abstract public function getCacheKey();

    public function __invoke()
    {
        $cacheKey = $this->getCacheKey();
        //判断缓存是否存在。。。。。
        Log::debug('缓存key', ['cacheKey' => $cacheKey]);
        $cache = App::make('http.cache')->get($cacheKey);
        return $cache;
    }
}