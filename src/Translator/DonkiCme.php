<?php declare(strict_types=1);

namespace HelioviewerEventInterface\DonkiCme;

use \DateInterval;
use \DateTimeImmutable;
use HelioviewerEventInterface\Types\HelioviewerEvent;
use HelioviewerEventInterface\Coordinator\Hgs2Hpc;
use AutoMapperPlus\Configuration\AutoMapperConfig;
use AutoMapperPlus\AutoMapper;
use AutoMapperPlus\MappingOperation\Operation;

function Translate(array $data, ?callable $postProcessor): array {
    $group = [
        'name' => 'CME',
        'contact' => 'Space Weather Database of NOtifications, Knowledge, Information (DONKI)',
        'url' => 'https://kauai.ccmc.gsfc.nasa.gov/DONKI/',
        'data' => []
    ];

    // Breaking encapsulation a bit... but creating one overall Hgs2Hpc instance means it will reuse the socket connection for each record.
    // This should give a slight performance improvement since it doesn't need to create a new connection for each record.
    $hgs2hpc = new Hgs2Hpc();
    foreach ($data as $record) {
        array_push($group['data'], TranslateCME($record, $hgs2hpc, $postProcessor));
    }
    return $group;
}

function TranslateCME(array $record, Hgs2Hpc $hgs2hpc, ?callable $postProcessor): HelioviewerEvent {
    $config = new AutoMapperConfig();
    $start = new DateTimeImmutable($record['startTime']);
    $end = $start->add(new DateInterval("P1D"));
    $cme = new DonkiCme($record);
    $hpc = $hgs2hpc->convert($cme->latitude, $cme->longitude, $start->format('Y-m-d\TH:i:s\Z'));
    $config->registerMapping('array', HelioviewerEvent::class)
        ->forMember('id', Operation::fromProperty('activityID'))
        ->forMember('label', Operation::setTo($cme->label()))
        ->forMember('version', Operation::fromProperty('catalog'))
        ->forMember('type', Operation::setTo('CE'))
        ->forMember('start', Operation::setTo($start->format('Y-m-d H:i:s')))
        ->forMember('end', Operation::setTo($end->format('Y-m-d H:i:s')))
        ->forMember('hpc_x', Operation::setTo($hpc['x']))
        ->forMember('hpc_y', Operation::setTo($hpc['y']));
    $mapper = new AutoMapper($config);
    $event = $mapper->map($record, HelioviewerEvent::class);
    if (isset($postProcessor)) {
        $event = $postProcessor($event);
    }
    $event->source = $record;
    return $event;
}

/**
 * Encapsulates processing a CME record
 */
class DonkiCme {
    private array $data;
    public float $latitude;
    public float $longitude;

    public function __construct(array $data) {
        $this->data = $data;
        $this->parseLatitudeLongitude();
    }

    private function parseLatitudeLongitude() {
        // First try to get the latitude longitude from the given analysis
        $analysis = $this->mostAccurateAnalysis();
        if (isset($analysis)) {
            $this->latitude = $analysis['latitude'];
            $this->longitude = $analysis['longitude'];
            return;
        }

        // If the analysis is unavailable, then attempt to get it from the sourceLocation
        $location = $this->data['sourceLocation'];
        // If the location is set and its not an empty string, then parse it
        if (isset($location) && trim($location) != "") {
            $north_south = $location[0];
            $north_south_value = intval(substr($location, 1, 2));
            // North Positive, South Negative
            $this->latitude = $north_south == "N" ? $north_south_value : -$north_south_value;

            $east_west = $location[3];
            $east_west_value = intval(substr($location, 4, 2));
            // East Positive, West Negative
            $this->longitude = $east_west == "E" ? $east_west_value : -$east_west_value;
        }
    }

    /**
     * Creates the multiline text label used to describe this record.
     */
    public function label() {
        $defaultLabel = $this->data['catalog'] . "\n" . $this->data['startTime'] . "\n";
        // Get the CME Analyses
        $analysis = $this->mostAccurateAnalysis();
        if (isset($analysis)) {
            return $defaultLabel . "Type: " . $analysis['type'] . "\nHalf Angle: " . $analysis['halfAngle'] . "\nSpeed: " . $analysis['speed'] . "km/s";
        }
        // If fields weren't present to create a more accurate label, then just use basic information.
        return $defaultLabel;
    }

    /**
     * Returns the first analysis that has "isMostAccurate: true"
     * @return array|null Returns the most accurate analysis or null if none is available
     */
    public function mostAccurateAnalysis() {
        if (array_key_exists('cmeAnalyses', $this->data)) {
            $cmeAnalyses = $this->data['cmeAnalyses'];
            foreach ($cmeAnalyses as $analysis) {
                if ($analysis['isMostAccurate']) {
                    return $analysis;
                }
            }
        }
        return null;
    }
}