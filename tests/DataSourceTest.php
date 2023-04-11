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
        $datasource = new DataSource("Donki", "https://kauai.ccmc.gsfc.nasa.gov/DONKI/WS/get/CME", "startDate", "endDate", "Y-m-d", "NopTranslator");
        $promise = $datasource->getAsync($this->START_DATE, $this->END_DATE);
        $data = $promise->wait();
        $this->assertEquals(8, count($data));
    }
}

