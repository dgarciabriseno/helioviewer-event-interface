<?php declare(strict_types=1);

namespace HelioviewerEventInterface\DonkiCme;

use \DateInterval;
use \DateTimeImmutable;
use \Exception;
use \Throwable;
use HelioviewerEventInterface\Types\HelioviewerEvent;
use HelioviewerEventInterface\Coordinator\Hgs2Hpc;
use HelioviewerEventInterface\Types\EventLink;

class IgnoreCme extends Exception {}

function Translate(array $data, ?callable $postProcessor): array {
    $groups = [];
    // Breaking encapsulation a bit... but creating one overall Hgs2Hpc instance means it will reuse the socket connection for each record.
    // This should give a slight performance improvement since it doesn't need to create a new connection for each record.
    $hgs2hpc = new Hgs2Hpc();
    foreach ($data as $record) {
        try {
            $cme = TranslateCME($record, $hgs2hpc, $postProcessor);
            // If the group doesn't exist already in the group list, then create it.
            if (!array_key_exists($record['catalog'], $groups)) {
                $groups[$record['catalog']] = [
                    'name' => $record['catalog'],
                    'contact' => 'Space Weather Database of NOtifications, Knowledge, Information (DONKI)',
                    'url' => 'https://kauai.ccmc.gsfc.nasa.gov/DONKI/',
                    'data' => []
                ];
            }
            $group = &$groups[$record['catalog']];
            array_push($group['data'], $cme);
        }
        catch (IgnoreCme) {
            continue;
        }
        catch (Throwable $e) {
            error_log("Failed to parse the following CME record: " . $e->getMessage());
            error_log(json_encode($record));
            continue;
        }
    }
    return array_values($groups);
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
    $event->link    = $cme->link();
    $event->views   = [['name' => 'CME', 'content' => $cme->view()]];


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
            if (is_null($analysis['latitude']) || is_null($analysis['longitude'])) {
                throw new IgnoreCme("Unknown location, can't display on Helioviewer");
            }
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
        $defaultLabel = $this->data['startTime'];
        $modeled = $this->hasModelRun() ? "\nModeled" : "";
        // Get the CME Analyses
        $analysis = $this->mostAccurateAnalysis();
        if (isset($analysis)) {
            return "Type: " . $analysis['type'] . "\nHalf Angle: " . $analysis['halfAngle'] . "&deg;\n" . $analysis['speed'] . " km/s" . $modeled;
        }
        // If fields weren't present to create a more accurate label, then just use basic information.
        return $defaultLabel . $modeled;
    }

    /**
     * Returns the first analysis that has "isMostAccurate: true"
     * @return array|null Returns the most accurate analysis or null if none is available
     */
    public function mostAccurateAnalysis() {
        if (array_key_exists('cmeAnalyses', $this->data)) {
            $cmeAnalyses = $this->data['cmeAnalyses'];
            if (isset($cmeAnalyses)) {
                foreach ($cmeAnalyses as $analysis) {
                    if ($analysis['isMostAccurate']) {
                        return $analysis;
                    }
                }
            }
        }
        return null;
    }

    private function hasLink(): bool {
        return array_key_exists("link", $this->data) && isset($this->data['link']);
    }

    private function hasModelRun(): bool {
        $analysis = $this->mostAccurateAnalysis();
        $modelKey = 'enlilList';
        if ($analysis && array_key_exists($modelKey, $analysis) && isset($analysis[$modelKey])) {
            return count($analysis[$modelKey]) > 0;
        }
        return false;
    }

    /**
     * Returns the URL to the CME source data
     */
    public function link(): ?EventLink {
        if ($this->hasLink()) {
            return new EventLink("Go to full analysis", $this->data['link']);
        } else {
            return null;
        }
    }

    /**
     * Returns data from the data object or null if the key doesn't exist
     */
    private function get(string $key): mixed {
        if (array_key_exists($key, $this->data)) {
            return $this->data[$key];
        }
        return null;
    }

    /**
     * Appends impact information to the given array
     */
    private function appendModelDetails(array &$data): void {
        $analysis = $this->mostAccurateAnalysis();
        if ($analysis) {
            $latestModel = $this->getLatestModel($analysis);
            if ($latestModel) {
                $base['Model Run Link'] = $latestModel['link'] ?? null;
                $impactList = $latestModel['impactList'] ?? [];
                foreach ($impactList as $impact) {
                    $base[$impact['location'] . " Impact"] = ($impact['isGlancingBlow'] ?? false) ? $impact['arrivalTime'] : "No Impact";
                }
            }
        }
    }

    /**
     * Returns a stringified version of the impact list
     */
    private function getImpactList(array $impactList): string {
        $result = "";
        foreach ($impactList as $impact) {
            if ($impact['isGlancingBlow'] ?? false) {
                $result .= $impact['location'] . " at " . $impact['arrivalTime'];
            }
        }
    }

    /**
     * Returns the model run with the newest timestamp
     */
    private function getLatestModel(array $analysis): ?array {
        $models = $analysis['enlilList'] ?? [];
        if (count($models) > 0) {
            $latest = array_reduce($models, function ($a, $b) {
                $lastTime = new DateTimeImmutable($a['modelCompletionTime']);
                $currentTime = new DateTimeImmutable($b['modelCompletionTime']);
                return $currentTime > $lastTime ? $b : $a;
            }, $models[0]);
            return $latest;
        }
        return null;
    }

    /**
     * Returns the most relevant CME data in a flat array of key-value pairs
     */
    public function view(): array {
        $base = [
            "Activity ID" => $this->get('activityID'),
            "Catalog"     => $this->get('catalog'),
            "Start Time"  => $this->get('startTime'),
            "Latitude"    => $this->latitude,
            "Longitude"   => $this->longitude,
            "Active Region" => $this->get('activeRegionNum'),
            "External Link" => $this->get('link'),
            "Instruments" => $this->instruments(),
            "Related Events" => $this->linkedEvents(),
            "Note" => $this->get('note')
        ];

        $analysis = $this->mostAccurateAnalysis();
        if ($analysis) {
            $base['Half Angle'] = $analysis['halfAngle'] ?? null;
            $base['Speed'] = $analysis['speed'] ?? null;
            $base['Type'] = $analysis['type'] ?? null;
            $base['Analysis Note'] = $analysis['note'] ?? null;
            $base['Analysis Link'] = $analysis['link'] ?? null;
            $this->appendModelDetails($base);
        }

        return $base;
    }

    private function linkedEvents(): ?string {
        $events = $this->get('linkedEvents');
        if (isset($events) && count($events) > 0) {
            $string = "";
            foreach ($events as $link) {
                $string .= $link['activityID'] . ", ";
            }
            $string = substr($string, 0, strlen($string) - 2);
            return $string;
        }
        return null;
    }

    /**
     * Returns the instruments used to discover this CME
     */
    private function instruments(): string {
        $instruments = $this->get('instruments');
        $text = "";
        if ($instruments) {
            foreach ($instruments as $inst) {
                $text .= $inst['displayName'] . ", ";
            }
            if (strlen($text) > 2) {
                // Trim trailing comma
                $text = substr($text, 0, strlen($text) - 2);
            }
        }
        return $text;
    }
}