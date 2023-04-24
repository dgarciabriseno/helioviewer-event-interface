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
            new DataSource("CCMC", "DONKI", "C3", "https://kauai.ccmc.gsfc.nasa.gov/DONKI/WS/get/CME", "startDate", "endDate", "Y-m-d", false, "DonkiCme"),
            new DataSource("CCMC", "Solar Flare Prediction", "FP", "https://iswa.gsfc.nasa.gov/IswaSystemWebApp/flarescoreboard/hapi/data", "time.min", "time.max", "Y-m-d\TH:i:s", true, "FlarePrediction", ["id" => "SIDC_Operator_REGIONS", "format" => "json", "include" => "header"], "SIDC Operator"),
            new DataSource("CCMC", "Solar Flare Prediction", "FP", "https://iswa.gsfc.nasa.gov/IswaSystemWebApp/flarescoreboard/hapi/data", "time.min", "time.max", "Y-m-d\TH:i:s", true, "FlarePrediction", ["id" => "BoM_flare1_REGIONS", "format" => "json", "include" => "header"], "Bureau of Meteorology"),
            new DataSource("CCMC", "Solar Flare Prediction", "FP", "https://iswa.gsfc.nasa.gov/IswaSystemWebApp/flarescoreboard/hapi/data", "time.min", "time.max", "Y-m-d\TH:i:s", true, "FlarePrediction", ["id" => "AMOS_v1_REGIONS", "format" => "json", "include" => "header"], "AMOS"),
            new DataSource("CCMC", "Solar Flare Prediction", "FP", "https://iswa.gsfc.nasa.gov/IswaSystemWebApp/flarescoreboard/hapi/data", "time.min", "time.max", "Y-m-d\TH:i:s", true, "FlarePrediction", ["id" => "ASAP_1_REGIONS", "format" => "json", "include" => "header"], "ASAP"),
            new DataSource("CCMC", "Solar Flare Prediction", "FP", "https://iswa.gsfc.nasa.gov/IswaSystemWebApp/flarescoreboard/hapi/data", "time.min", "time.max", "Y-m-d\TH:i:s", true, "FlarePrediction", ["id" => "MAG4_LOS_FEr_REGIONS", "format" => "json", "include" => "header"], "MAG4 LoS FEr"),
            new DataSource("CCMC", "Solar Flare Prediction", "FP", "https://iswa.gsfc.nasa.gov/IswaSystemWebApp/flarescoreboard/hapi/data", "time.min", "time.max", "Y-m-d\TH:i:s", true, "FlarePrediction", ["id" => "MAG4_LOS_r_REGIONS", "format" => "json", "include" => "header"], "MAG4 LoS r"),
            new DataSource("CCMC", "Solar Flare Prediction", "FP", "https://iswa.gsfc.nasa.gov/IswaSystemWebApp/flarescoreboard/hapi/data", "time.min", "time.max", "Y-m-d\TH:i:s", true, "FlarePrediction", ["id" => "MAG4_SHARP_FE_REGIONS", "format" => "json", "include" => "header"], "MAG4 Sharp FE"),
            new DataSource("CCMC", "Solar Flare Prediction", "FP", "https://iswa.gsfc.nasa.gov/IswaSystemWebApp/flarescoreboard/hapi/data", "time.min", "time.max", "Y-m-d\TH:i:s", true, "FlarePrediction", ["id" => "MAG4_SHARP_REGIONS", "format" => "json", "include" => "header"], "MAG4 Sharp"),
            new DataSource("CCMC", "Solar Flare Prediction", "FP", "https://iswa.gsfc.nasa.gov/IswaSystemWebApp/flarescoreboard/hapi/data", "time.min", "time.max", "Y-m-d\TH:i:s", true, "FlarePrediction", ["id" => "MAG4_SHARP_HMI_REGIONS", "format" => "json", "include" => "header"], "MAG4 Sharp HMI"),
            new DataSource("CCMC", "Solar Flare Prediction", "FP", "https://iswa.gsfc.nasa.gov/IswaSystemWebApp/flarescoreboard/hapi/data", "time.min", "time.max", "Y-m-d\TH:i:s", true, "FlarePrediction", ["id" => "AEffort_REGIONS", "format" => "json", "include" => "header"], "AEffort"),
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