<?php declare(strict_types=1);

namespace HelioviewerEventInterface\Types;

use HelioviewerEventInterface\Types\EventLink;

class HelioviewerEvent
{
    public string $id;         // Required
    public string $label;      // Required
    public string $version;    // Required
    public string $type;       // Required
    public string $start;      // Required
    public string $end;        // Required
    public array $source;      // Required
    public array $views;       // Required
    public float $hpc_x;       // Required
    public float $hpc_y;       // Required
    public ?EventLink $link;   // Optional

    public float $hv_hpc_x;    // Reserved
    public float $hv_hpc_y;    // Reserved
}