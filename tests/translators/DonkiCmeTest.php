<?php declare(strict_types=1);

use HelioviewerEventInterface\Events;
use PHPUnit\Framework\TestCase;

final class DonkiCmeTest extends TestCase
{
    public function testDataWithNoLatLong(): void
    {
        $start = new DateTimeImmutable("2021-12-08T09:01:31Z");
        $end = new DateTimeImmutable("2021-12-09T09:01:31Z");

        $result = Events::GetFromSource(["DONKI"], $start, $end);
        $this->assertNotNull($result);
    }
}

