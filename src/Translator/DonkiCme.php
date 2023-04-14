<?php declare(strict_types=1);

namespace HelioviewerEventInterface\DonkiCme;

use \DateInterval;
use \DateTimeImmutable;
use \Throwable;
use HelioviewerEventInterface\Types\HelioviewerEvent;
use HelioviewerEventInterface\Coordinator\Hgs2Hpc;

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
        try {
            $cme = TranslateCME($record, $hgs2hpc, $postProcessor);
            array_push($group['data'], $cme);
        } catch (Throwable $e) {
            error_log("Failed to parse the following CME record: " . $e->getMessage());
            error_log(json_encode($record));
            continue;
        }
    }
    return $group;
}

function TranslateCME(array $record, Hgs2Hpc $hgs2hpc, ?callable $postProcessor): HelioviewerEvent {
    $start = new DateTimeImmutable($record['startTime']);
    $end = $start->add(new DateInterval("P1D"));
    $cme = new DonkiCme($record);
    $hpc = $hgs2hpc->convert($cme->latitude, $cme->longitude, $start->format('Y-m-d\TH:i:s\Z'));

    $event = new HelioviewerEvent();
    $event->id      = $record['activityID'];
    $event->label   = $cme->label();
    $event->version = $record['catalog'];
    $event->type    = 'CE';
    $event->start   = $start->format('Y-m-d H:i:s');
    $event->end     = $end->format('Y-m-d H:i:s');
    $event->hpc_x   = $hpc['x'];
    $event->hpc_y   = $hpc['y'];

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