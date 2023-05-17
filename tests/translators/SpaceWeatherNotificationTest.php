<?php declare(strict_types=1);

use HelioviewerEventInterface\Cache;
use HelioviewerEventInterface\Events;
use PHPUnit\Framework\TestCase;

include_once __DIR__ . "/../../src/Translator/SpaceWeatherNotification.php";

final class SpaceWeatherNotificationTest extends TestCase
{
    protected function setUp(): void {
        Cache::Clear();
    }

    public function testSpaceWeatherNotificationFiltering(): void
    {
        $start = new DateTimeImmutable('2023-04-01');
        $length = new DateInterval("P1D");
        $result = Events::GetFromSource(["SWN"], $start, $length);
        $this->assertCount(2, $result[0]['groups'][0]['data']);
    }
}

