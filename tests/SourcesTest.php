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
        $keys = [];
        foreach (Sources::All() as $source) {
            $key = $source->GetCacheKey();
            $this->assertNotContains($key, $keys, 'Duplicate cache key found');
            array_push($keys, $key);
        }
    }
}

