<?php declare(strict_types=1);

use HelioviewerEventInterface\Events;
use PHPUnit\Framework\TestCase;
use HelioviewerEventInterface\Translator\DonkiFlare;


final class DonkiFlareTest extends TestCase
{
    public function testShortLabelWithAll(): void 
    {
        $data["peakTime"] = "2024-05-13T01:33Z";
        $data["classType"] = "M1.2";
        $data["activeRegionNum"] = "13664";

        $donkiCme = new DonkiFlare($data);

        $this->assertEquals("2024-05-13 01:33:00 M1.2 AR 13664", $donkiCme->shortLabel());
    }

    public function testShortLabelWithAllMissing(): void 
    {
        $data["peakTime"] = "2024-05-13T01:33Z";

        $donkiCme = new DonkiFlare($data);

        $this->assertEquals("2024-05-13 01:33:00", $donkiCme->shortLabel());
    }

    public function testShortLabelWithRegionMissing(): void 
    {
        $data["peakTime"] = "2024-05-13T01:33Z";
        $data["classType"] = "CLASSTYPE";

        $donkiCme = new DonkiFlare($data);

        $this->assertEquals("2024-05-13 01:33:00 CLASSTYPE", $donkiCme->shortLabel());
    }

    public function testShortLabelWithClassMissing(): void 
    {
        $data["peakTime"] = "2024-05-13T01:33Z";
        $data["activeRegionNum"] = "REGION";

        $donkiCme = new DonkiFlare($data);

        $this->assertEquals("2024-05-13 01:33:00 AR REGION", $donkiCme->shortLabel());
    }

}

