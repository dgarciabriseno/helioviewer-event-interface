<?php declare(strict_types=1);

use HelioviewerEventInterface\Events;
use HelioviewerEventInterface\Translator\FlarePrediction;
use HelioviewerEventInterface\Util\Date;
use PHPUnit\Framework\TestCase;
use function HelioviewerEventInterface\Translator\CreateShortLabel;
use HelioviewerEventInterface\Util\HapiRecord;
use PHPUnit\TextUI\XmlConfiguration\Group;

final class FlarePredictionTest extends TestCase
{
    public function testCoordinates(): void
    {
        $start = new DateTimeImmutable();
        $length = new DateInterval("P2D");
        $result = Events::GetFromSource(["CCMC"], $start, $length, $start);
        $count = 0;
        foreach ($result as $section) {
            foreach ($section['groups'] as $group) {
                foreach ($group['data'] as $record) {
                    $count += 1;
                    $this->assertArrayHasKey('hv_hpc_x', $record);
                    $this->assertArrayHasKey('hv_hpc_y', $record);
                    $this->assertArrayNotHasKey('hpc_x', $record);
                    $this->assertArrayNotHasKey('hpc_y', $record);
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

    static private function MakeEvent(float $lat, float $lon): array {
        return [
            'source' => [
                'NOAALatitude' => $lat,
                'NOAALongitude' => $lon,
                'NOAALocationTime' => Date::FormatDate(new DateTimeImmutable()),
                'issue_time' => new DateTimeImmutable(),
            ]
        ];
    }

    /**
     * We've found that some CCMC Flare Scoreboard predictions have an invalid
     * coordinate. The FlarePrediction translator filters these out.
     * The FlarePrediction module now filters any records where the latitude
     * and longitude are out of bounds.
     */
    #[Group('coordinator')]
    public function testFlarePredictionInvalidCoordinates(): void {
        $data = FlarePrediction::Transform([
            'groups' => [[
                'data' => [
                    // Invalid coordinate
                    self::MakeEvent(106, 130),
                    // Edge cases out of bounds
                    self::MakeEvent(90.01, 0),
                    self::MakeEvent(-90.01, 0),
                    self::MakeEvent(0, 180.01),
                    self::MakeEvent(0, -180.01),
                    // Edge cases in-bound
                    self::MakeEvent(90, 180),
                    self::MakeEvent(-90, -180),
                ]
            ]]
        ], new DateTimeImmutable());
        $this->assertCount(2, $data['groups'][0]['data']);
        $this->assertArrayHasKey(0, $data['groups'][0]['data']);
        $this->assertArrayHasKey(1, $data['groups'][0]['data']);
    }
}

