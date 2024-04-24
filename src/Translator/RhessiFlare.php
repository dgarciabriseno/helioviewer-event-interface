<?php declare(strict_types=1);

namespace HelioviewerEventInterface\RhessiFlare;

use \DateInterval;
use \DateTimeImmutable;
use \DateTimeInterface;
use Exception;
use HelioviewerEventInterface\Types\EventLink;
use HelioviewerEventInterface\Types\HelioviewerEvent;
use HelioviewerEventInterface\Util\CsvParser;
use HelioviewerEventInterface\Util\Date;

/**
 * Returns a stream created from a string
 */
function string_stream(string $str) {
    $fp = fopen('php://memory', 'r+');
    fwrite($fp, $str);
    rewind($fp);
    return $fp;
}

/**
 * Parses a subset of the RHESSI Flare List csv into the Helioviewer event format
 * The subset is determined by $extra['start'] (DateTimeInterface) and $extra['length'] (DateInterval)
 *
 * Originally, this was going to parse the entire RHESSI Flare list into Helioviewer Event Format
 * and then filter results later, but the flare list is too large to store in-memory for PHP.
 * The default limit is 128MB, and I'd rather not require Helioviewer hosts to need to modify
 * their memory limits as it just adds more complexity to system management.
 * Instead, this reads the flare CSV and only processes the values which fall in
 * the query range. Data is still cached on the hour, but since this data is static,
 * the cached values do not expire.
 *
 * @param string $csv the contents of the csv flare list
 * @param array $extra Array with the following keys: offset => int, start => DateTimeInterface, length => DateInterval
 */
function Translate(string $csv, mixed $extra, ?callable $postprocessor): array {
    // $extra must have certain keys for the RhessiFlare translator
    // offset - The start of the data within the csv file
    // start - A DateTime instance representing an endpoint of the query range.
    // length - A DateInterval instance which represents the desired time range
    //          when combined with the start time.
    if (!array_key_exists("offset", $extra) ||
        !array_key_exists("start", $extra) ||
        !array_key_exists("length", $extra)) {
            throw new Exception(
                "Missing required extra parameters for RhessiFlare Translator.
                Expected 'offset', 'start', and 'length'. Got " . print_r(array_keys($extra), true)
            );
    }
    // Setup the data that's going to be returned
    $groups = [
        [
            'name' => 'Flare',
            'contact' => '',
            'url' => 'https://umbra.nascom.nasa.gov/rhessi/rhessi_extras/flare_images_v2/hsi_flare_image_archive.html',
            'data' => []
        ]
    ];
    $stream = string_stream($csv);
    // Move pointer to the first item in the csv.
    fseek($stream, $extra['offset']);
    $count = 0;
    $saved = 0;
    while ($data = fgetcsv($stream)) {
        $count += 1;
        $flare = new RhessiFlare($data);
        if ($flare->withinRange($extra['start'], $extra['length'])) {
            $event = $flare->asEvent();
            if (isset($postprocessor)) {
                $event = $postprocessor($event);
            }
            $saved += 1;
            array_push($groups[0]['data'], (array) $event);
        } else if ($flare->isAfterRange($extra['start'], $extra['length'])) {
            // The flare list is sorted by start time, so as soon as we find
            // a flare that's after the time range, we can exit since we know
            // there will be no more flares to process.
            // Flares before the time range must still be checked.
            break;
        }
    }
    return [
        "name" => "Solar Flares",
        "pin"  => "F2",
        "groups" => $groups
    ];
}

/**
 * Interface to a Rhessi flare's csv data
 */
class RhessiFlare {
    private string $url_prefix = "https://umbra.nascom.nasa.gov/rhessi/rhessi_extras/flare_images_v2/";
    /** CSV row converted into a key->value array. See the RHESSI CSV for keys */
    private array $data;

    public function __construct(array $data) {
        $keys = explode(",", "id,start,peak,end,peakrate,totalcounts,xloc,yloc,hi_band,ntime,nen,link");
        $this->data = CsvParser::Associate($keys, $data);
        // Convert date strings into dates
        $this->data["start"] = new DateTimeImmutable($this->data["start"]);
        $this->data["peak"] = new DateTimeImmutable($this->data["peak"]);
        $this->data["end"] = new DateTimeImmutable($this->data["end"]);
    }

    /**
     * Returns true of the start/end time of this flare overlap with the given
     * time range
     */
    public function withinRange(DateTimeInterface $time, DateInterval $length): bool {
        // Get an instance of time that we can work with
        $date = DateTimeImmutable::createFromInterface($time);
        $date_end = $date->add($length);
        // Get the start and end time of the given range
        $startTime = min($date, $date_end);
        $endTime = max($date, $date_end);
        // The flare is within the given range if either its start or end time is
        // inside the query time.
        return Date::RangeOverlap($startTime, $endTime, $this->data["start"], $this->data["end"]);
    }

    public function isAfterRange(DateTimeInterface $time, DateInterval $length): bool {
        // Get an instance of time that we can work with
        $date = DateTimeImmutable::createFromInterface($time);
        $date_end = $date->add($length);
        $endTime = max($date, $date_end);
        if ($endTime < $this->data["start"]) {
            return true;
        }
        return false;
    }

    public function id() { return $this->data[0]; }


    public function asMappedArray(): array {
        // clone the data
        $data = unserialize(serialize($this->data));
        // Rewrite some fields to be more appropriate for the view
        $data['start'] = Date::FormatDate($data['start']);
        $data['end'] = Date::FormatDate($data['end']);
        $data['peak'] = Date::FormatDate($data['peak']);
        $data['link'] = $this->url();
        return $data;
    }

    public function url(): string {
        return $this->url_prefix . $this->data["link"];
    }

    public function views(): array {
        return array(
            [
                "name" => "Main",
                "content" => $this->asMappedArray()
            ]
        );
    }

    public function link(): EventLink {
        return new EventLink(
            "Full analysis",
            $this->url()
        );
    }

    public function asEvent(): HelioviewerEvent {
        $event = new HelioviewerEvent();
        $event->id = $this->data["id"];
        $event->label = "RHESSI " . $event->id;
        $event->title = $event->label;
        $event->version = "";
        $event->type = "FL";
        $event->start = Date::FormatDate($this->data["start"]);
        $event->end = Date::FormatDate($this->data["end"]);
        $event->source = $this->data;
        $event->views = $this->views();
        $event->hpc_x = floatval($this->data["xloc"]);
        $event->hpc_y = floatval($this->data["yloc"]);
        $event->link = $this->link();
        return $event;
    }
}