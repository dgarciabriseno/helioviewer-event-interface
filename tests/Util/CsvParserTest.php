<?php declare(strict_types=1);

use HelioviewerEventInterface\Util\CsvParser;
use PHPUnit\Framework\TestCase;

final class CsvParserTest extends TestCase
{

    /**
     * Test normal use case of ToArray, convert a csv into key->value pairs
     */
    public function testToArray() {
        $csv = "a,b,c,d";
        $keys = ["one", "two", "three", "four"];
        $expected = [
            "one" => "a",
            "two" => "b",
            "three" => "c",
            "four" => "d"
        ];
        $data = CsvParser::ToArray($keys, $csv);
        $this->assertEquals($expected, $data);
    }

    /**
     * Test incorrect usage of ToArray, expect an exception to the thrown
     */
    public function testToArrayException() {
        $csv = "a,b,c,d";
        $keys = ["one", "two","three"];
        $this->expectException("ValueError");
        CsvParser::ToArray($keys, $csv);
    }
}