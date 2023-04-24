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
}

