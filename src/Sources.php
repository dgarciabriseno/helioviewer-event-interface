<?php declare(strict_types=1);

namespace HelioviewerEventInterface;
use HelioviewerEventInterface\Data\JsonDataSource;

/**
 * The list of datasources known by the event interface
 * Querying a datasource must return one Helioviewer Event Category
 */
class Sources {
    public static function All() {
        return [
            // Using "C3" instead of "CE" because this abbreviation needs to be unique across all data sources and CE is taken by HEK
            new JsonDataSource("CCMC", "DONKI", "C3", "https://kauai.ccmc.gsfc.nasa.gov/DONKI/WS/get/CME", "startDate", "endDate", "Y-m-d", "DonkiCme"),
            new JsonDataSource("CCMC", "DONKI", "F1", "https://kauai.ccmc.gsfc.nasa.gov/DONKI/WS/get/FLR", "startDate", "endDate", "Y-m-d", "DonkiFlare"),
            new JsonDataSource("CCMC", "Solar Flare Predictions", "FP", "https://iswa.gsfc.nasa.gov/IswaSystemWebApp/flarescoreboard/hapi/data", "time.min", "time.max", "Y-m-d\TH:i:s", "FlarePrediction", ["id" => "SIDC_Operator_REGIONS", "format" => "json", "include" => "header"], "SIDC Operator"),
            new JsonDataSource("CCMC", "Solar Flare Predictions", "FP", "https://iswa.gsfc.nasa.gov/IswaSystemWebApp/flarescoreboard/hapi/data", "time.min", "time.max", "Y-m-d\TH:i:s", "FlarePrediction", ["id" => "BoM_flare1_REGIONS", "format" => "json", "include" => "header"], "Bureau of Meteorology"),
            new JsonDataSource("CCMC", "Solar Flare Predictions", "FP", "https://iswa.gsfc.nasa.gov/IswaSystemWebApp/flarescoreboard/hapi/data", "time.min", "time.max", "Y-m-d\TH:i:s", "FlarePrediction", ["id" => "AMOS_v1_REGIONS", "format" => "json", "include" => "header"], "AMOS"),
            new JsonDataSource("CCMC", "Solar Flare Predictions", "FP", "https://iswa.gsfc.nasa.gov/IswaSystemWebApp/flarescoreboard/hapi/data", "time.min", "time.max", "Y-m-d\TH:i:s", "FlarePrediction", ["id" => "ASAP_1_REGIONS", "format" => "json", "include" => "header"], "ASAP"),
            new JsonDataSource("CCMC", "Solar Flare Predictions", "FP", "https://iswa.gsfc.nasa.gov/IswaSystemWebApp/flarescoreboard/hapi/data", "time.min", "time.max", "Y-m-d\TH:i:s", "FlarePrediction", ["id" => "MAG4_LOS_FEr_REGIONS", "format" => "json", "include" => "header"], "MAG4 LoS FEr"),
            new JsonDataSource("CCMC", "Solar Flare Predictions", "FP", "https://iswa.gsfc.nasa.gov/IswaSystemWebApp/flarescoreboard/hapi/data", "time.min", "time.max", "Y-m-d\TH:i:s", "FlarePrediction", ["id" => "MAG4_LOS_r_REGIONS", "format" => "json", "include" => "header"], "MAG4 LoS r"),
            new JsonDataSource("CCMC", "Solar Flare Predictions", "FP", "https://iswa.gsfc.nasa.gov/IswaSystemWebApp/flarescoreboard/hapi/data", "time.min", "time.max", "Y-m-d\TH:i:s", "FlarePrediction", ["id" => "MAG4_SHARP_FE_REGIONS", "format" => "json", "include" => "header"], "MAG4 Sharp FE"),
            new JsonDataSource("CCMC", "Solar Flare Predictions", "FP", "https://iswa.gsfc.nasa.gov/IswaSystemWebApp/flarescoreboard/hapi/data", "time.min", "time.max", "Y-m-d\TH:i:s", "FlarePrediction", ["id" => "MAG4_SHARP_REGIONS", "format" => "json", "include" => "header"], "MAG4 Sharp"),
            new JsonDataSource("CCMC", "Solar Flare Predictions", "FP", "https://iswa.gsfc.nasa.gov/IswaSystemWebApp/flarescoreboard/hapi/data", "time.min", "time.max", "Y-m-d\TH:i:s", "FlarePrediction", ["id" => "MAG4_SHARP_HMI_REGIONS", "format" => "json", "include" => "header"], "MAG4 Sharp HMI"),
            new JsonDataSource("CCMC", "Solar Flare Predictions", "FP", "https://iswa.gsfc.nasa.gov/IswaSystemWebApp/flarescoreboard/hapi/data", "time.min", "time.max", "Y-m-d\TH:i:s", "FlarePrediction", ["id" => "AEffort_REGIONS", "format" => "json", "include" => "header"], "AEffort"),
        ];
    }

    /**
     * Returns a list of Data Sources that match the given
     */
    public static function FromArray(array $sources): array {
        $all = Sources::All();
        // Return all datasources where the source name is in the given source array
        return array_filter($all, function ($datasource) use ($sources) {
            return in_array($datasource->source, $sources) || in_array($datasource->name, $sources);
        });
    }
}