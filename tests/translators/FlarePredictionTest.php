<?php declare(strict_types=1);

use HelioviewerEventInterface\Events;
use PHPUnit\Framework\TestCase;
use function HelioviewerEventInterface\Translator\CreateShortLabel;
use HelioviewerEventInterface\Util\HapiRecord;


final class FlarePredictionTest extends TestCase
{
    public function testCoordinates(): void
    {
        $start = new DateTimeImmutable();
        $length = new DateInterval("P2D");
        $result = Events::GetFromSource(["CCMC"], $start, $length);
        $count = 0;
        foreach ($result as $section) {
            foreach ($section['groups'] as $group) {
                foreach ($group['data'] as $record) {
                    $count += 1;
                    $this->assertNotEquals(0.123456789, $record['hpc_x']);
                    $this->assertNotEquals(0.987654321, $record['hpc_y']);
                }
            }
        }
        $this->assertTrue($count > 0);
    }

    public function testShortLabel(): void {
        $parameters = [
            [
                'fill' => '',
                'length' => 22,
                'name' => 'start_window',
                'type' => 'isotime',
                'units' => 'UTC',
            ],

            [
                'fill' => '',
                'length' => 22,
                'name' => 'end_window',
                'type' => 'isotime',
                'units' => 'UTC',
            ],

            [
                'fill' => '',
                'length' => 22,
                'name' => 'issue_time',
                'type' => 'isotime',
                'units' => 'UTC',
            ],

            [
                'fill' => -1,
                'name' => 'C',
                'type' => 'double',
                'units' => 'probability',
            ],

            [
                'fill' => -1,
                'name' => 'M',
                'type' => 'double',
                'units' => 'probability',
            ],

            [
                'fill' => -1,
                'name' => 'CPlus',
                'type' => 'double',
                'units' => 'probability',
            ],

            [
                'fill' => -1,
                'name' => 'MPlus',
                'type' => 'double',
                'units' => 'probability',
            ],

            [
                'fill' => -1,
                'name' => 'X',
                'type' => 'double',
                'units' => 'probability',
            ],
            [
                'fill' => -1,
                'name' => 'NOAARegionId',
                'type' => 'integer',
                'units' => 'regionIdentifier',
            ],

            [
                'fill' => -1,
                'length' => 22,
                'name' => 'NOAALocationTime',
                'type' => 'isotime',
                'units' => 'UTC',
            ],

            [
                'fill' => 999,
                'name' => 'NOAALatitude',
                'type' => 'integer',
                'units' => 'latitude',
            ],

            [
                'fill' => 999,
                'name' => 'NOAALongitude',
                'type' => 'integer',
                'units' => 'longitude',
            ],

            [
                'fill' => -1,
                'name' => 'CataniaRegionId',
                'type' => 'integer',
                'units' => 'regionIdentifier',
            ],

            [
                'fill' => -1,
                'length' => 22,
                'name' => 'CataniaLocationTime',
                'type' => 'isotime',
                'units' => 'UTC',
            ],

            [
                'fill' => 999,
                'name' => 'CataniaLatitude',
                'type' => 'integer',
                'units' => 'latitude',
            ],

            [
                'fill' => 999,
                'name' => 'CataniaLongitude',
                'type' => 'integer',
                'units' => 'longitude',
            ],

        ];

        $record = [
            '2024-05-14T12:30:00.0',
            '2024-05-15T12:30:00.0',
            '2024-05-14T12:30:31.0',
            -1,
            -1,
            0.05,
            0.01,
            0.01,
            3667,
            '2024-05-14T00:30:00.0',
            27,
            46,
            92,
            '2024-05-13T07:00:00.0',
            27,
            34,
        ];

        $hapi_record = new HapiRecord($record, $parameters, "");

        $this->assertEquals("\nC+: 5%\nM+: 1%\nX: 1%", CreateShortLabel($hapi_record));
    }
}

