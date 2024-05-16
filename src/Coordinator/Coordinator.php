<?php declare(strict_types=1);

namespace HelioviewerEventInterface\Coordinator;

use \Exception;

/**
 * Interface to the coordinator API
 */
class Coordinator {
    /**
     * Transforms an HPC coordinate as seen from earth, to an HPC coordinate
     * inside Helioviewer's frame of reference
     * @param $x X coordinate in arcseconds
     * @param $y Y coordinate in arcseconds
     * @param $obstime Observation time
     */
    public static function HPC(float $x, float $y, string $obstime) {
        try {
            $result = file_get_contents(HV_COORDINATOR_URL . "/hpc?x=" . $x . "&y=" . $y . "&obstime=" . urlencode($obstime));
            $response = json_decode($result, true);
            // The response format from the API is just x, y, but better to
            // not make any assumptions. Handle the error here instead of
            // on the client.
            return array("x" => $response["x"], "y" => $response["y"]);
        } catch (Exception $e) {
            // On exception, log it and return the original coordinate.
            // We do this so that the coordinate we show is mostly correct,
            // rather than halting the processing of all events.
            error_log($e->getMessage());
            return array("x" => $x, "y" => $y);
        }
    }
}