<?php declare(strict_types=1);

namespace HelioviewerEventInterface;
use HelioviewerEventInterface\DataSource;
use HelioviewerEventInterface\Translator\DonkiCme;

/**
 * The list of datasources known by the event interface
 * Querying a datasource must return one Helioviewer Event Category
 */
const SOURCES = [
    new DataSource("Coronal Mass Ejection", "CE", "https://kauai.ccmc.gsfc.nasa.gov/DONKI/WS/get/CME", "startDate", "endDate", "Y-m-d", DonkiCme::class),
];