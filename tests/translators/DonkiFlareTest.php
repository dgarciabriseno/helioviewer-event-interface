<?php declare(strict_types=1);

use HelioviewerEventInterface\Data\JsonDataSource;
use PHPUnit\Framework\TestCase;
use HelioviewerEventInterface\Translator\DonkiFlare;


final class DonkiFlareTest extends TestCase
{
    public function testShortLabelWithAll(): void
    {
        $data["peakTime"] = "2024-05-13T01:33Z";
        $data["classType"] = "M1.2";
        $data["activeRegionNum"] = "13664";

        $donkiFlare = new DonkiFlare($data);

        $this->assertEquals("2024-05-13 01:33:00 M1.2 AR 13664", $donkiFlare->shortLabel());
    }

    public function testShortLabelWithAllMissing(): void
    {
        $data["peakTime"] = "2024-05-13T01:33Z";

        $donkiFlare = new DonkiFlare($data);

        $this->assertEquals("2024-05-13 01:33:00", $donkiFlare->shortLabel());
    }

    public function testShortLabelWithRegionMissing(): void
    {
        $data["peakTime"] = "2024-05-13T01:33Z";
        $data["classType"] = "CLASSTYPE";

        $donkiFlare = new DonkiFlare($data);

        $this->assertEquals("2024-05-13 01:33:00 CLASSTYPE", $donkiFlare->shortLabel());
    }

    public function testShortLabelWithClassMissing(): void
    {
        $data["peakTime"] = "2024-05-13T01:33Z";
        $data["activeRegionNum"] = "REGION";

        $donkiFlare = new DonkiFlare($data);

        $this->assertEquals("2024-05-13 01:33:00 AR REGION", $donkiFlare->shortLabel());
    }

    public function testFlareTransform(): void {
        $ds = new JsonDataSource("CCMC", "DONKI", "F1", "https://kauai.ccmc.gsfc.nasa.gov/DONKI/WS/get/FLR", "startDate", "endDate", "Y-m-d", "DonkiFlare");
        $ds->beginQuery(new DateTime('2024-01-01 00:00:00'), new DateInterval('PT1H'), new DateTime('2024-01-01 00:00:00'));
        $result = $ds->getResult();
        // All flares in this time period are near the east limb.
        // assert that the coordinates are in that general area
        foreach ($result['groups'] as $group) {
            foreach ($group['data'] as $event) {
                $this->assertLessThan(-920, $event['hv_hpc_x']);
            }
        }
    }
}

