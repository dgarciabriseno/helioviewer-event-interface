<?php declare(strict_types=1);

namespace HelioviewerEventInterface;

use \DateInterval;
use \DateTimeInterface;
use \Redis;
use Psr\Cache\CacheItemInterface;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Contracts\Cache\ItemInterface;

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

    public static function DefaultExpiry(): DateInterval {
        return new DateInterval("P2W");
    }

    /**
     * Creates a unique ID that is associated with the given identifier and provided date range.
     *
     * @param string $id Primary cache key identifier
     * @param DateTimeInterface $date Date to integrate into the resulting key
     * @param DateInterval $interval Length of time to integrate into the resulting key
     */
    public static function CreateKey(string $id, DateTimeInterface $date, DateInterval $interval): string {
        // This should be unique across all data sources
        // Stop the date at hour so that caching occurs on the hour boundary.
        // The interval uses the full interval value so that different time intervals result in different cache keys.
        $id .= $date->format('Y-m-d H') . $interval->format('%Y%M%D%H%I%S');
        return hash('sha256', $id);
    }

    /**
     * Attempts to retrieve an item from the cache.
     * On cache miss, the provided function will be executed and the value returned will be saved in the cache.
     * While the provided function is executing, other cache gets on the same key will be blocked until the function sets the cached value.
     * This prevents multiple "threads" from trying to compute the key simultaneously.
     * In the context of this project, it's for limiting external HTTP requests to reduce the load on external servers.
     * @param string $key Cache key
     * @param DateInterval $expiry Length of time for this item to live.
     * @param callable $computeValue Function which will compute the value to cache for this key.
     */
    public static function GetWithLock(string $key, DateInterval $expiry, callable $computeValue): mixed {
        return self::GetCacheInstance()->get($key, function (ItemInterface $item) use ($expiry, $computeValue) {
            $item->expiresAfter($expiry);
            return $computeValue();
        });
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

    /**
     * Clears the cache of all keys
     */
    public static function Clear(): void {
        $redis = new Redis();
        $redis->connect(HV_REDIS_HOST, HV_REDIS_PORT);
        $redis->flushAll();
    }
}
