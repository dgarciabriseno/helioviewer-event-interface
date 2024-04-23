<?php declare(strict_types=1);

use HelioviewerEventInterface\Data\CsvDataSource;
use HelioviewerEventInterface\Cache;
use PHPUnit\Framework\TestCase;

final class CsvDataSourceTest extends TestCase
{
    protected function setUp(): void {
        Cache::Clear();
    }

    public function getRemoteTestDataSource(string $translator = "NopTranslator"): CsvDataSource {
        return new CsvDataSource(
            "RHESSI",
            "https://hesperia.gsfc.nasa.gov/~kim/rhessi_helioviewer/rhessi_flares_helioviewer.txt",
            $translator,
            ["offset" => 635]
        );
    }

    public function getLocalTestDataSource(string $translator = "NopTranslator"): CsvDataSource {
        return new CsvDataSource(
            "RHESSI",
            "file://" . __DIR__ . "/../data/rhessi_flares_helioviewer.txt",
            $translator,
            // Location of the first data row in the csv file
            ["offset" => 635]
        );
    }

    /**
     * This test is specifically for the RhessiFlare translator.
     * Since it reads a csv file, it needs to know where the csv data begins
     * within the file. This parameter is passed in the "extra" data with the
     * key 'offset'. If this is missing, an exception is thrown.
     */
    public function testMissingExtraParameter() {
        $ds = new CsvDataSource(
            "RHESSI",
            "file://" . __DIR__ . "/../data/rhessi_flares_helioviewer.txt",
            "RhessiFlare"
        );
        $this->expectException("Exception");

        $ds->beginQuery(
            new DateTime("2012-02-12 00:00:00"),
            new DateInterval("P1D"),
            null
        );
        $ds->getResult();
    }

    public function testRemoteCsv(): void {
        $source = $this->getRemoteTestDataSource("RhessiFlare");
        $source->beginQuery(
            new DateTime("2012-02-22 00:00:00"),
            new DateInterval("P1D")
        );
        $data = $source->getResult();
        // Using the nop translator,  full query response is returned
        $this->assertCount(1, $data);
        $this->assertCount(4, $data[0]["data"]);
    }

    public function testLocalCsv(): void {
        $source = $this->getLocalTestDataSource("RhessiFlare");
        $source->beginQuery(
            new DateTime("2012-02-22 00:00:00"),
            new DateInterval("P1D")
        );
        $data = $source->getResult();
        // Expect to find 4 flares for the given time range
        $this->assertCount(1, $data);
        $this->assertCount(4, $data[0]["data"]);
    }

    // TODO: Test date boundary conditions (when the requested date is exactly the flare's start/end time)
}