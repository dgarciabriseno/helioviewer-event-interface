<?php declare(strict_types=1);

use HelioviewerEventInterface\Cache;
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

    protected function setUp(): void {
        Cache::Clear();
    }

    /**
     * Verifies that events can be queried and that the closure to modify individual records is working
     */
    public function testGetEvents(): void
    {
        $result = Events::GetFromSource(["DONKI"], $this->START_DATE, $this->LENGTH, $this->START_DATE);
        $this->assertTrue(is_array($result));
        $this->assertEquals(1, count($result));
        $this->assertTrue(array_key_exists('groups', $result[0]));
    }

    /**
     * Verifies that even if a source is given that doesn't exist, nothing will crash
     */
    public function testGetNothingFromSource(): void {
        $emptySet = Events::GetFromSource(["beep beep"], $this->START_DATE, $this->LENGTH, $this->START_DATE);
        $this->assertIsArray($emptySet);
        $this->assertEmpty($emptySet);
    }

    /**
     * Verifies that even if a source is given that doesn't exist, nothing will crash
     */
    public function testGetFromSource(): void {
        $data = Events::GetFromSource(["CCMC"], $this->START_DATE, $this->LENGTH, $this->START_DATE);
        $this->assertTrue(is_array($data));
        $this->assertEquals(2, count($data));
        $this->assertTrue(array_key_exists('groups', $data[0]));
        $this->assertEquals(3, count($data[0]['groups'][0]['data']));
    }

    public function testGetAll(): void {
        $length = new DateInterval("P2D");
        $start = new DateTime();
        $start->sub($length);
        Events::GetAll($start, $length, $start);
    }
}

