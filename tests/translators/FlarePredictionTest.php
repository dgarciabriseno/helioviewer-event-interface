<?php declare(strict_types=1);

use HelioviewerEventInterface\Events;
use PHPUnit\Framework\TestCase;

include_once __DIR__ . "/../../src/Translator/FlarePrediction.php";

final class FlarePredictionTest extends TestCase
{
    public function testCoordinates(): void
    {
        $start = new DateTimeImmutable();
        $length = new DateInterval("P2D");
        $result = Events::GetFromSource(["CCMC"], $start, $length);
        $count = 0;
        foreach ($result as $section) {
            foreach ($section['groups'] as $group) {
                foreach ($group['data'] as $record) {
                    $count += 1;
                    $this->assertNotEquals(0.123456789, $record['hpc_x']);
                    $this->assertNotEquals(0.987654321, $record['hpc_y']);
                }
            }
        }
        $this->assertTrue($count > 0);
    }
}

