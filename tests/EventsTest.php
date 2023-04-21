<?php declare(strict_types=1);

use HelioviewerEventInterface\DataSource;
use HelioviewerEventInterface\Events;
use PHPUnit\Framework\TestCase;

final class EventsTest extends TestCase
{
    private DateTime $START_DATE;
    private DateInterval $LENGTH;
    public function __construct() {
        parent::__construct("EventsTest");
        $this->START_DATE = new DateTime('2023-04-01');
        $this->LENGTH = new DateInterval('P1D');
    }

    /**
     * Verifies that events can be queried and that the closure to modify individual records is working
     */
    public function testGetEvents(): void
    {
        $sources = [
            new DataSource("DONKI", "Coronal Mass Ejection", "CE", "https://kauai.ccmc.gsfc.nasa.gov/DONKI/WS/get/CME", "startDate", "endDate", "Y-m-d", false, DonkiCme::class),
        ];
        $result = Events::GetAll($this->START_DATE, $this->LENGTH, function ($record) {$record->hv_hpc_x = 999; return $record;}, $sources);
        $this->assertTrue(is_array($result));
        $this->assertEquals(1, count($result));
        $this->assertTrue(array_key_exists('groups', $result[0]));
        // Verify closure works
        $this->assertEquals(999, $result[0]['groups'][0]['data'][0]['hv_hpc_x']);
    }

    /**
     * Verifies that even if a source is given that doesn't exist, nothing will crash
     */
    public function testGetNothingFromSource(): void {
        $emptySet = Events::GetFromSource(["beep beep"], $this->START_DATE, $this->LENGTH);
        $this->assertIsArray($emptySet);
        $this->assertEmpty($emptySet);
    }

    /**
     * Verifies that even if a source is given that doesn't exist, nothing will crash
     */
    public function testGetFromSource(): void {
        $data = Events::GetFromSource(["DONKI"], $this->START_DATE, $this->LENGTH);
        $this->assertTrue(is_array($data));
        $this->assertEquals(1, count($data));
        $this->assertTrue(array_key_exists('groups', $data[0]));
        $this->assertEquals(8, count($data[0]['groups'][0]['data']));
    }

    public function testGetAll(): void {
        $length = new DateInterval("P2D");
        $start = new DateTime();
        $start->sub($length);
        Events::GetAll($start, $length);
    }
}

