<?php declare(strict_types=1);

use HelioviewerEventInterface\Events;
use PHPUnit\Framework\TestCase;

use function HelioviewerEventInterface\Translator\GetGifsFromDonkiWebPage;
use HelioviewerEventInterface\Translator\DonkiCme;

final class DonkiCmeTest extends TestCase
{
    public function testDataWithNoLatLong(): void
    {
        $start = new DateTimeImmutable("2021-12-08T09:01:31Z");
        $end = new DateInterval("P1D");

        $result = Events::GetFromSource(["DONKI"], $start, $end);
        $this->assertNotNull($result);
    }

    public function testGetGifs(): void {
        $gifs = GetGifsFromDonkiWebPage("https://kauai.ccmc.gsfc.nasa.gov/DONKI/view/WSA-ENLIL/24907/1");
        $this->assertCount(16, $gifs);
        foreach ($gifs as $gif) {
            $this->assertStringEndsWith(".gif", $gif);
            $this->assertStringStartsWith('https:', $gif);
        }
    }


    public function testShortLabelDefaultLabel(): void 
    {
        $data["startTime"] = "2024-05-14T10:09Z";
        $data["sourceLocation"] = "2024-05-14T10:09Z";

        $donkiCme = new DonkiCme($data);

        $this->assertEquals("2024-05-14 10:09:00", $donkiCme->shortLabel());
    }

    public function testShortLabelWithModeled(): void 
    {
        $data["startTime"] = "2024-05-14T10:09Z";
        $data["sourceLocation"] = "2024-05-14T10:09Z";
        $data["cmeAnalyses"] = [[
            'isMostAccurate' => true,
            'latitude' => -36,
            'longitude' => 72,
            'type' => 'foo_type',
            'halfAngle' => 'foo_angle',
            'speed' => 'foo_speed',
            'enlilList' => [[
            ]],
        ]]; 

        $donkiCme = new DonkiCme($data);
        $this->assertEquals("Type: foo_type foo_angle&deg; foo_speed km/s Modeled", $donkiCme->shortLabel());
    }
        
    public function testShortLabelWithoutModeled(): void 
    {
        $data["startTime"] = "2024-05-14T10:09Z";
        $data["sourceLocation"] = "2024-05-14T10:09Z";
        $data["cmeAnalyses"] = [[
            'isMostAccurate' => true,
            'latitude' => -36,
            'longitude' => 72,
            'type' => 'foo_type',
            'halfAngle' => 'foo_angle',
            'speed' => 'foo_speed',
        ]]; 

        $donkiCme = new DonkiCme($data);
        $this->assertEquals("Type: foo_type foo_angle&deg; foo_speed km/s", $donkiCme->shortLabel());
    }
}

