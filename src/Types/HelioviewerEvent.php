<?php declare(strict_types=1);

namespace HelioviewerEventInterface\Types;

use HelioviewerEventInterface\Types\EventLink;

class HelioviewerEvent
{
    /**
     * Required Fields
     */
    /** Unique event identifier  */
    public string $id;
    /** Event label, this appears next to the pin on Helioviewer.org */
    public string $label;

    /** Event short label, this appears to be short version of the label */
    public string $short_label;

    /** Event version */
    public string $version;
    /** Type of event, this is used to select the pin to display on helioviewer */
    public string $type;
    /** Start time of the event. Use Date::Format */
    public string $start;
    /** End time of the event. Use Date::Format */
    public string $end;
    /** Original source data as a key-value array */
    public array $source;
    /** Tab views */
    public array $views;
    /** x position, use Coordinator::Hgs2Hpc if you only have a heliographic position */
    public float $hpc_x;
    /** y position, use Coordinator::Hgs2Hpc if you only have a heliographic position */
    public float $hpc_y;

    /**
     * Optional Fields
     */
    /** Dialog title */
    public string $title;      // Optional, but preferred
    /** Link to event data outside of Helioviewer, if applicable */
    public ?EventLink $link;   // Optional

    /**
     * Reserved for internal use fields
     */
    public float $hv_hpc_x;    // Reserved
    public float $hv_hpc_y;    // Reserved
}
