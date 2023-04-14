<?php declare(strict_types=1);

use HelioviewerEventInterface\Sources;
use PHPUnit\Framework\TestCase;

final class SourcesTest extends TestCase
{
    public function testFromArray(): void
    {
        $donkiSources = Sources::FromArray(["DONKI"]);
        $this->assertEquals(1, count($donkiSources));

        $noSources = Sources::FromArray(["blah"]);
        $this->assertEquals(0, count($noSources));
    }
}

