<?php declare(strict_types=1);

namespace HelioviewerEventInterface\Coordinator;

use \Exception;

class Hgs2Hpc {
    /**
     * Converts latitude and longitude coordinates at the given time to HelioProjective coordinates
     */
    public function convert(float $latitude, float $longitude, string $date) {
        try {
            $result = file_get_contents(HV_COORDINATOR_URL . "/hgs2hpc?lat=" . $latitude . "&lon=" . $longitude . "&obstime=" . urlencode($date));
            $response = json_decode($result, true);
            // The response format from the API is just x, y, but better to
            // not make any assumptions. Handle the error here instead of
            // on the client.
            return array("x" => $response["x"], "y" => $response["y"]);
        } catch (Exception $e) {
            // On exception, log it and return something.
            error_log($e->getMessage());
            return array("x" => 0, "y" => 0);
        }
    }
}