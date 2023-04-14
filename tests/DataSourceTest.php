<?php declare(strict_types=1);

use HelioviewerEventInterface\DataSource;
use HelioviewerEventInterface\Translator\Translator;
use PHPUnit\Framework\TestCase;

final class DataSourceTest extends TestCase
{
    private DateTime $START_DATE;
    private DateTime $END_DATE;
    public function __construct(string $name) {
        parent::__construct($name);
        $this->START_DATE = new DateTime('2023-04-01');
        $this->END_DATE = new DateTime('2023-04-02');
    }

    public function testAsyncQuery(): void
    {
        // Test via the DONKI CME data source
        $datasource = new DataSource("DONKI", "CME", "CE", "https://kauai.ccmc.gsfc.nasa.gov/DONKI/WS/get/CME", "startDate", "endDate", "Y-m-d", "NopTranslator");
        $datasource->beginQuery($this->START_DATE, $this->END_DATE);
        $data = $datasource->getResult();
        // Normally the translator returns helioviewer groups, but in this case since we're using the NopTranslator it just returns the data as-is.
        // So in this case, "groups" is not actually helioviewer groups, it's just raw event data for the purpose of testing.
        $this->assertEquals(8, count($data['groups'][0]));
    }

    public function testDonkiCme(): void
    {
        $datasource = new DataSource("Donki", "CME", "CE", "https://kauai.ccmc.gsfc.nasa.gov/DONKI/WS/get/CME", "startDate", "endDate", "Y-m-d", "DonkiCme");
        $datasource->beginQuery($this->START_DATE, $this->END_DATE);
        $group = $datasource->getResult();
        // Here it runs through the DonkiCme translator, so it should actually be in the correct event format.
        $this->assertEquals(8, count($group['groups'][0]['data']));
    }

    public function testQueryWithNoData(): void {
        $start = new DateTimeImmutable("2019-09-05T18:20:28Z");
        $end = new DateTimeImmutable("2019-09-05T18:20:28Z");

        $datasource = new DataSource("Donki", "CME", "CE", "https://kauai.ccmc.gsfc.nasa.gov/DONKI/WS/get/CME", "startDate", "endDate", "Y-m-d", "DonkiCme");
        $datasource->beginQuery($start, $end);
        $group = $datasource->getResult();
        $this->assertCount(0, $group['groups']);
    }
}

