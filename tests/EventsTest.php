<?php declare(strict_types=1);

use HelioviewerEventInterface\DataSource;
use HelioviewerEventInterface\Events;
use PHPUnit\Framework\TestCase;

final class EventsTest extends TestCase
{
    private DateTime $START_DATE;
    private DateTime $END_DATE;
    public function __construct(string $name) {
        parent::__construct($name);
        $this->START_DATE = new DateTime('2023-04-01');
        $this->END_DATE = new DateTime('2023-04-02');
    }

    public function testGetEvents(): void
    {
        $sources = [
            new DataSource("Coronal Mass Ejection", "CE", "https://kauai.ccmc.gsfc.nasa.gov/DONKI/WS/get/CME", "startDate", "endDate", "Y-m-d", DonkiCme::class),
        ];

        $result = Events::GetAll($this->START_DATE, $this->END_DATE, $sources);
        $this->assertTrue(is_array($result));
        $this->assertEquals(1, count($result));
        $this->assertTrue(array_key_exists('groups', $result[0]));
    }
}

