<?php declare(strict_types=1);

use HelioviewerEventInterface\Cache;
use HelioviewerEventInterface\Events;
use HelioviewerEventInterface\Sources;
use PHPUnit\Framework\TestCase;

final class SourcesTest extends TestCase
{
    public function testFromArray(): void
    {
        $sources = Sources::FromArray(["CCMC"]);
        $this->assertTrue(count($sources) > 0);

        $noSources = Sources::FromArray(["blah"]);
        $this->assertEquals(0, count($noSources));
    }

    /**
     * This test verifies that no two data defined data sources have a cache
     * collision. It also verifies that cache keys are generated on the hour
     * mark.
     */
    public function testUniqueCacheKeys(): void {
        $now = new DateTime();
        $interval = new DateInterval("P1D");
        $keys = [];
        foreach (Sources::All() as $source) {
            $key = $source->GetCacheKey($now, $interval);
            $this->assertNotContains($key, $keys, 'Duplicate cache key found');
            array_push($keys, $key);
        }

        // Verify a different interval results in different keys
        $interval = new DateInterval("P2D");
        foreach (Sources::All() as $source) {
            $key = $source->GetCacheKey($now, $interval);
            $this->assertNotContains($key, $keys, 'Duplicate cache key found when using a different interval');
            array_push($keys, $key);
        }

        // Verify same keys are computed with the same interval
        foreach (Sources::All() as $source) {
            $key = $source->GetCacheKey($now, $interval);
            $this->assertContains($key, $keys);
        }

        // Verify same keys are computed with a different date within the same hour
        $newDate = new DateTime($now->format('Y-m-d H:59:59'));
        foreach (Sources::All() as $source) {
            $key = $source->GetCacheKey($newDate, $interval);
            $this->assertContains($key, $keys);
        }
    }

    public function testInvalidLocation(): void {
        $start = new DateTime("2021-12-09T23:01:53Z");
        $interval = new DateInterval("P1D");
        Events::GetAll($start, $interval, $start);
    }
}

