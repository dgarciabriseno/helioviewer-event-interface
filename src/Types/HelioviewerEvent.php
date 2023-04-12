<?php declare(strict_types=1);

namespace HelioviewerEventInterface\Types;

class HelioviewerEvent
{
    public string $id;         // Required
    public string $label;      // Required
    public string $version;    // Required
    public string $type;       // Required
    public string $start;      // Required
    public string $end;        // Required
    public array $source;      // Required
    public float $latitude;   // Optional
    public float $longitude;  // Optional
}