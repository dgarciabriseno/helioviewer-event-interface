<?php declare(strict_types=1);

namespace HelioviewerEventInterface\Translator;

use \DateInterval;
use \DateTimeImmutable;
use DateTimeInterface;
use \Exception;
use \Throwable;
use HelioviewerEventInterface\Types\HelioviewerEvent;
use HelioviewerEventInterface\Coordinator\Coordinator;
use HelioviewerEventInterface\Types\EventLink;
use HelioviewerEventInterface\Util\Camel2Title;
use HelioviewerEventInterface\Util\Subarray;
use HelioviewerEventInterface\Util\Date;

class IgnoreCme extends Exception {}


function TranslateCME(array $record): array {
    $start = new DateTimeImmutable($record['startTime']);
    $end = $start->add(new DateInterval("P1D"));
    $cme = new DonkiCme($record);

    $event = new HelioviewerEvent();
    $event->id      = $record['activityID'];
    $event->label   = $cme->label();
    $event->short_label   = $cme->shortLabel();
    $event->version = $record['catalog'];
    $event->type    = 'CE';
    $event->start   = $start->format('Y-m-d H:i:s');
    $event->end     = $end->format('Y-m-d H:i:s');
    $event->link    = $cme->link();
    $event->views   = $cme->views();
    // coordinates will be updated during the transform step
    $event->hv_hpc_x = $cme->latitude;
    $event->hv_hpc_y = $cme->longitude;

    $event->source = $record;
    return (array) $event;
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
            // East Negative, West Positive
            $this->longitude = $east_west == "E" ? -$east_west_value : $east_west_value;
        }
        if (!isset($this->latitude) || !isset($this->longitude)) {
            throw new IgnoreCme("Unknown location, can't display on Helioviewer");
        }
    }

    public function views(): array  {
        // Begin with the main view
        $base = [['name' => 'CME', 'content' => $this->cme_view()]];
        // Build a tab for each analysis
        $analyses = $this->get('cmeAnalyses') ?? [];
        $tabgroup = 0;
        foreach ($analyses as  $index => $analysis) {
            $tabgroup += 1;
            array_push($base, [
                'tabgroup' => $tabgroup,
                'name' => "Analysis " . $index + 1,
                'content' => $this->analysis_view($analysis)
            ]);
            // Build a tab for each model run in the analysis
            $models = $analysis['enlilList'] ?? [];
            foreach ($models as $index => $model) {
                array_push($base, [
                    'tabgroup' => $tabgroup,
                    'name' => "Model " . $index + 1,
                    'content' => $this->model_view($model)
                ]);
            }
        }
        return $base;
    }


    /**
     * Creates the short label used to describe this record.
     */
    public function shortLabel() {
        $defaultLabel = Date::FormatString($this->data['startTime']);
        $modeled = $this->hasModelRun() ? " Modeled" : "";
        // Get the CME Analyses
        $analysis = $this->mostAccurateAnalysis();
        if (isset($analysis)) {
            return sprintf("Type: %s %s&deg; %s km/s%s", $analysis['type'], $analysis['halfAngle'], $analysis['speed'], $modeled);
        }
        // If fields weren't present to create a more accurate label, then just use basic information.
        return $defaultLabel . $modeled;
    }

    /**
     * Creates the multiline text label used to describe this record.
     */
    public function label() {
        $defaultLabel = Date::FormatString($this->data['startTime']);
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
    public function model_view(?array $model): ?array {
        if ($model) {
            $content = Camel2Title::Parse($model, [
                "modelCompletionTime",
                "link",
                "estimatedShockArrivalTime",
                "estimatedDuration",
            ]);
            $content = Subarray::merge($content, $model, [
                "au",
                "rmin_re",
                "kp_18",
                "kp_90",
                "kp_135",
                "kp_180"
            ]);
            $content['Is Earth GB'] = $model['isEarthGB'] ?? null;
            $impactList = $model['impactList'] ?? [];
            foreach ($impactList as $impact) {
                $content[$impact['location'] . " Impact"] = $impact['arrivalTime'];
                $content[$impact['location'] . " Glancing Blow"] = $impact['isGlancingBlow'];
            }
            // Add gifs
            $gifs = GetGifsFromDonkiWebPage($content['Link']);
            foreach ($gifs as $gif) {
                $content[basename($gif)] = $gif;
            }
        }
        return $content;
    }

    /**
     * Returns the most relevant CME data in a flat array of key-value pairs
     */
    public function cme_view(): array {
        return [
            "Activity ID" => $this->get('activityID'),
            "Catalog"     => $this->get('catalog'),
            "Active Region" => $this->get('activeRegionNum'),
            "Start Time"  => $this->get('startTime'),
            "Latitude"    => $this->latitude,
            "Longitude"   => $this->longitude,
            "External Link" => $this->get('link'),
            "Instruments" => $this->instruments(),
            "Related Events" => $this->linkedEvents(),
            "Note" => $this->get('note')
        ];
    }

    public function analysis_view(?array $analysis): ?array {
        $base = null;
        if ($analysis) {
            $base = Camel2Title::Parse($analysis, [
                "isMostAccurate",
                "levelOfData",
                "type",
                "speed",
                "halfAngle",
                "note",
                "link",
            ]);
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

    public static function Translate(array $data, mixed $extra): array {
        $groups = [
            [
                'name' => "CME",
                'contact' => 'Space Weather Database of NOtifications, Knowledge, Information (DONKI)',
                'url' => 'https://kauai.ccmc.gsfc.nasa.gov/DONKI/',
                'data' => []
            ]
        ];
        foreach ($data as $record) {
            try {
                $cme = TranslateCME($record);
                array_push($groups[0]['data'], $cme);
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

    public static function Transform(array $events, DateTimeInterface $obstime): array {
        $observation_time = Date::FormatDate($obstime);
        foreach ($events['groups'] as &$group) {
            // $group:
            // array('name', 'contact', 'url', 'data')
            foreach ($group['data'] as &$event) {
                // event:
                // array of HelioviewerEvent fields
                $lat = $event['hv_hpc_x'];
                $lon = $event['hv_hpc_y'];
                $time = $event['start'];
                $coord = Coordinator::Hgs2Hpc($lat, $lon, $time, $observation_time);
                $event['hv_hpc_x'] = $coord['x'];
                $event['hv_hpc_y'] = $coord['y'];
            }
        }
        return $events;
    }
}

/**
 * Queries the given DONKI Model URL and returns all gif urls found on the page.
 */
function GetGifsFromDonkiWebPage(string $url): array {
    $client = new \GuzzleHttp\Client([]);
    $response = $client->get($url);
    $page = $response->getBody()->getContents();
    preg_match_all('/\bhttps?:\/\/\S+?\.gif\b/i', $page, $gifs);
    if (count($gifs) > 0) {
        $links = array_unique($gifs[0]);
        // Update any 'http' links to 'https'
        $result = [];
        foreach ($links as $link) {
            array_push($result, str_replace('http:', 'https:', $link));
        }
        return $result;
    } else {
        return [];
    }
}
