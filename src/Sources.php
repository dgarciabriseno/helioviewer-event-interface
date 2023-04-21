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
            // Using "C3" instead of "CE" because this abbreviation needs to be unique across all data sources and CE is taken by HEK
            new DataSource("DONKI", "Coronal Mass Ejection", "C3", "https://kauai.ccmc.gsfc.nasa.gov/DONKI/WS/get/CME", "startDate", "endDate", "Y-m-d", "DonkiCme"),
            new DataSource("CCMC", "Solar Flare Prediction", "FP", "https://iswa.gsfc.nasa.gov/IswaSystemWebApp/flarescoreboard/hapi/data", "time.min", "time.max", "Y-m-d\TH:i:s", "FlarePrediction", ["id" => "SIDC_Operator_REGIONS", "format" => "json", "include" => "header"], "SIDC Operator"),
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