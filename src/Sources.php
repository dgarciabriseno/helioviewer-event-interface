<?php declare(strict_types=1);

namespace HelioviewerEventInterface;
use HelioviewerEventInterface\DataSource;

/**
 * The list of datasources known by the event interface
 * Querying a datasource must return one Helioviewer Event Category
 */
class Sources {
    public static function All() {
        return [
            new DataSource("DONKI", "Coronal Mass Ejection", "CE", "https://kauai.ccmc.gsfc.nasa.gov/DONKI/WS/get/CME", "startDate", "endDate", "Y-m-d", "DonkiCme"),
        ];
    }

    /**
     * Returns a list of Data Sources that match the given
     */
    public static function FromArray(array $sources): array {
        $all = Sources::All();
        // Return all datasources where the source name is in the given source array
        return array_filter($all, function ($datasource) use ($sources) {
            return in_array($datasource->source, $sources);
        });
    }
}