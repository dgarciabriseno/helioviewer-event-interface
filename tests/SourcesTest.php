<?php declare(strict_types=1);

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
        $newDate = new DateTime($now->format('Y-m-d H:00:00'));
        foreach (Sources::All() as $source) {
            $key = $source->GetCacheKey($newDate, $interval);
            $this->assertContains($key, $keys);
        }

        $newDate = new DateTime($now->format('Y-m-d H:59:59'));
        foreach (Sources::All() as $source) {
            $key = $source->GetCacheKey($newDate, $interval);
            $this->assertContains($key, $keys);
        }
    }
}

