<?php declare(strict_types=1);

namespace HelioviewerEventInterface\Translator;

use \Exception;
use \DateTime;
use HelioviewerEventInterface\Coordinator\Hgs2Hpc;
use HelioviewerEventInterface\Types\EventLink;
use HelioviewerEventInterface\Types\HelioviewerEvent;
use HelioviewerEventInterface\Util\LocationParser;
use HelioviewerEventInterface\Util\Date;

class DonkiFlare {
    public array $flare;
    public function __construct(array $flare) {
        $this->flare = $flare;
    }

    public function id(): string {
        return $this->flare['flrID'];
    }

    public function class(): string {
        return $this->flare['classType'] ?? '';
    }

    public function region(): string {
        $region = $this->flare['activeRegionNum'] ?? null;
        if ($region) {
            return "AR $region";
        } else {
            return '';
        }
    }

    public function hpc(Hgs2Hpc $hgs2hpc): array {
        $location = LocationParser::ParseText($this->flare['sourceLocation']);
        $value = $hgs2hpc->convert($location[0], $location[1], $this->peak()->format('Y-m-d\TH:i:s\Z'));
        return [$value['x'], $value['y']];
    }

    public function instruments(): string {
        $instruments = $this->flare['instruments'];
        $obs = array();
        foreach ($instruments as $i) {
            array_push($obs, $i['displayName']);
        }
        return implode(', ', $obs);
    }

    public function link(): EventLink {
        return new EventLink("View on DONKI site", $this->flare['link']);
    }

    public function start(): string {
        return Date::FormatString($this->flare['beginTime']);
    }

    public function end(): string {
        return Date::FormatString($this->flare['endTime']);
    }

    public function peak(): DateTime {
        return new DateTime($this->flare['peakTime']);
    }

    /**
     * Creates the label used to describe this record.
     */
    public function label(): string {
        $label = Date::FormatDate($this->peak());

        if($class = $this->class()) {
            $label = $label ."\n".$class;
        }

        if($region = $this->region()) {
            $label = $label ."\n".$region;
        }

        return $label;
    }

    /**
     * Creates the short label used to describe this record.
     */
    public function shortLabel() {

        $label = Date::FormatDate($this->peak());

        if($class = $this->class()) {
            $label = $label ." ".$class;
        }

        if($region = $this->region()) {
            $label = $label ." ".$region;
        }

        return $label;

    }

    public function views(): array {
        return [
            [
                'name' => 'Flare',
                'content' => [
                    'instruments' => $this->instruments(),
                    'begin' => $this->start(),
                    'end' => $this->end(),
                    'peak' => $this->peak()->format('Y-m-d H:i:s'),
                    'class' => $this->flare['classType'],
                    'Active Region' => $this->flare['activeRegionNum'],
                    'link' => $this->flare['link']
                ]
            ]
        ];
    }


    public static function Translate(array $flares, mixed $extra, ?callable $postProcessor): array {
        $groups = [
            [
                'name' => 'Solar Flares',
                'contact' => '',
                'url' => 'https://kauai.ccmc.gsfc.nasa.gov/DONKI/',
                'data' => []
            ]
        ];
        $data = &$groups[0]['data'];
        $hgs2hpc = new Hgs2Hpc();
        foreach ($flares as $flare) {
            $flare = new self($flare);
            $event = new HelioviewerEvent();
            $event->id = $flare->id();
            $event->label = $flare->label();
            $event->short_label   = $flare->shortLabel();
            $event->version = '';
            $event->type = 'FL';
            $event->start = $flare->start();
            $event->end = $flare->end();
            $event->source = $flare->flare;
            $event->views = $flare->views();
            list($event->hpc_x, $event->hpc_y) = $flare->hpc($hgs2hpc);
            $event->link = $flare->link();
            if ($postProcessor) {
                $event = $postProcessor($event);
            }
            array_push($data, (array) $event);
        }
        return $groups;
    }

}
