<?php declare(strict_types=1);

use HelioviewerEventInterface\Cache;
use HelioviewerEventInterface\DataSource;
use PHPUnit\Framework\TestCase;

final class DataSourceTest extends TestCase
{
    private DateTime $START_DATE;
    private DateInterval $LENGTH;
    public function __construct() {
        parent::__construct("DataSourceTest");
        $this->START_DATE = new DateTime('2023-04-01');
        $this->LENGTH = new DateInterval('P1D');
    }

    public function testAsyncQuery(): void
    {
        // Test via the DONKI CME data source
        $datasource = new DataSource("DONKI", "CME", "CE", "https://kauai.ccmc.gsfc.nasa.gov/DONKI/WS/get/CME", "startDate", "endDate", "Y-m-d", "NopTranslator");
        $datasource->beginQuery($this->START_DATE, $this->LENGTH);
        $data = $datasource->getResult();
        // Normally the translator returns helioviewer groups, but in this case since we're using the NopTranslator it just returns the data as-is.
        // So in this case, "groups" is not actually helioviewer groups, it's just raw event data for the purpose of testing.
        $this->assertEquals(8, count($data['groups'][0]));
    }

    public function testDonkiCme(): void
    {
        $datasource = new DataSource("Donki", "CME", "CE", "https://kauai.ccmc.gsfc.nasa.gov/DONKI/WS/get/CME", "startDate", "endDate", "Y-m-d", "DonkiCme");
        $datasource->beginQuery($this->START_DATE, $this->LENGTH);
        $data = $datasource->getResult();
        // Here it runs through the DonkiCme translator, so it should actually be in the correct event format.
        $totalItems = array_reduce($data['groups'], function ($total, $group) {$total += count($group['data']); return $total;}, 0);
        $this->assertEquals(8, $totalItems);
    }

    public function testQueryWithNoData(): void {
        $start = new DateTimeImmutable("2019-09-05T18:20:28Z");
        $end = new DateInterval("P0D");

        $datasource = new DataSource("Donki", "CME", "CE", "https://kauai.ccmc.gsfc.nasa.gov/DONKI/WS/get/CME", "startDate", "endDate", "Y-m-d", "DonkiCme");
        $datasource->beginQuery($start, $end);
        $group = $datasource->getResult();
        $this->assertCount(0, $group['groups']);
    }

    public function testCachedQuery(): void {
        $start = new DateTimeImmutable("2023-04-05T20:20:00Z");
        $length = new DateInterval("P1D");

        $datasource = new DataSource("Donki", "CME", "CE", "https://kauai.ccmc.gsfc.nasa.gov/DONKI/WS/get/CME", "startDate", "endDate", "Y-m-d", "DonkiCme");
        $datasource->beginQuery($start, $length);
        $group = $datasource->getResult();
        // Now assert this result has been cached
        $key = $datasource->GetCacheKey($start, $length);
        $item = Cache::Get($key);
        $this->assertTrue($item->isHit());
        $this->assertEquals($group, $item->get());
    }

    public function testDateRounding(): void {
        $datasource = new DataSource("Donki", "CME", "CE", "https://kauai.ccmc.gsfc.nasa.gov/DONKI/WS/get/CME", "startDate", "endDate", "Y-m-d", "DonkiCme");
        $roundDownStart = new DateTimeImmutable("2021-12-09T23:29:59Z");
        $length = new DateInterval("P1D");
        $datasource->beginQuery($roundDownStart, $length);
        $downGroup = $datasource->getResult();

        $roundUpStart = new DateTimeImmutable("2021-12-09T23:30:00Z");
        $datasource->beginQuery($roundUpStart, $length);
        $upGroup = $datasource->getResult();
        $this->assertNotEquals($downGroup, $upGroup);
    }
}

