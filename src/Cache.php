<?php declare(strict_types=1);

namespace HelioviewerEventInterface;

use \DateInterval;
use \Redis;
use Psr\Cache\CacheItemInterface;
use Symfony\Component\Cache\Adapter\RedisAdapter;

class Cache {
    private static ?RedisAdapter $CacheInstance = null;
    private static function GetCacheInstance(): RedisAdapter {
        if (is_null(self::$CacheInstance)) {
            $redis = new Redis();
            $redis->connect(HV_REDIS_HOST, HV_REDIS_PORT);
            self::$CacheInstance = new RedisAdapter($redis);
        }
        return self::$CacheInstance;
    }

    /**
     * Returns the cached item associated with the given key
     * @param string $key Cache key
     */
    public static function Get(string $key): CacheItemInterface {
        return self::GetCacheInstance()->getItem($key);
    }

    /**
     * Stores a value for the given cache key
     * @param string $key Cache key
     * @param DateInterval $expiration Length of time for this item to live
     * @param mixed $value Value to store in the cache
     */
    public static function Set(string $key, DateInterval $expiration, mixed $value): void {
        $item = self::Get($key);
        $item->expiresAfter($expiration);
        $item->set($value);
        self::GetCacheInstance()->save($item);
    }
}
