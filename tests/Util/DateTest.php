<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use HelioviewerEventInterface\Util\Date;

final class DateTest extends TestCase
{
    public function testParseDateString(): void
    {
        $date = '2023-04-01';
        $this->assertEquals('2023-04-01 00:00:00', Date::FormatString($date, 'N/A'));
    }

    public function testParseDate(): void
    {
        $date = new DateTime('2023-04-01');
        $this->assertEquals('2023-04-01 00:00:00', Date::FormatDate($date, 'N/A'));
    }

    public function testUnparseableDate(): void
    {
        $date = 'Not a date';
        $this->assertEquals('N/A', Date::FormatString($date, 'N/A'));
    }
}

