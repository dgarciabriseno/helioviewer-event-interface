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
        $flarePredictions = array_filter($result, function ($section) { return $section['name'] == 'Solar Flare Prediction'; })[0];
        $sidc_group = array_filter($flarePredictions['groups'], function ($group) { return $group['name'] == 'SIDC Operator'; })[0];
        foreach ($sidc_group['data'] as $prediction) {
            $this->assertNotEquals(0.123456789, $prediction['hpc_x']);
            $this->assertNotEquals(0.987654321, $prediction['hpc_y']);
        }
    }
}

