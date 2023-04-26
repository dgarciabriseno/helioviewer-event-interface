<?php declare(strict_types=1);

use HelioviewerEventInterface\Cache;
use PHPUnit\Framework\TestCase;

final class CacheTest extends TestCase
{
    public function testCacheMiss(): void
    {
        $item = Cache::Get("Nothing to see here");
        $this->assertFalse($item->isHit());
    }

    public function testCacheHit(): void {
        Cache::Set("CacheTest", new DateInterval('PT5S'), 77);
        $item = Cache::Get("CacheTest");
        $this->assertTrue($item->isHit());
        $this->assertEquals(77, $item->get());
    }

    public function testCacheExpiration(): void {
        Cache::Set("CacheTest", new DateInterval('PT1S'), 99);
        $item = Cache::Get("CacheTest");
        // After caching, the item should be found in the cache.
        $this->assertTrue($item->isHit());
        $this->assertEquals(99, $item->get());
        // Wait for cache time to expire
        sleep(2);
        // Now should be a cache miss.
        $item = Cache::Get("CacheTest");
        $this->assertFalse($item->isHit());
    }
}

