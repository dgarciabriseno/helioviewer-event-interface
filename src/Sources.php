<?php declare(strict_types=1);

namespace HelioviewerEventInterface;
use HelioviewerEventInterface\DataSource;
use HelioviewerEventInterface\Translator\DonkiCme;

const SOURCES = [
    new DataSource("Donki", "https://kauai.ccmc.gsfc.nasa.gov/DONKI/WS/get/CME", "startDate", "endDate", "Y-m-d", DonkiCme::class),
];