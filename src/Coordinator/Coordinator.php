<?php declare(strict_types=1);

namespace HelioviewerEventInterface\Coordinator;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;

/**
 * Interface to the coordinator API
 */
class Coordinator {
    private static ?Client $client = null;
    /**
     * Performs a get request to the given url and returns the results
     * @throws RequestException if the request fails.
     * @throws ConnectionException if the coordinator server can't be reached.
     * @param string $url
     */
    private static function Get(string $url) {
        if (is_null(Coordinator::$client)) {
            Coordinator::$client = new Client();
        }
        $response = Coordinator::$client->request('GET', $url);
        return $response->getBody()->getContents();
    }

    /**
     * Transforms an HPC coordinate as seen from earth, to an HPC coordinate
     * inside Helioviewer's frame of reference
     * @param float $x X coordinate in arcseconds
     * @param float $y Y coordinate in arcseconds
     * @param string $event_time Time that the event was measured
     * @param string $target Helioviewer observation time
     */
    public static function HPC(float $x, float $y, string $event_time, ?string $target = null) {
        $target = $target ?? $event_time;
        $result = Coordinator::Get(HV_COORDINATOR_URL . "/hpc?x=" . $x . "&y=" . $y . "&coord_time=" . urlencode($event_time) . "&target=" . urlencode($target));
        $response = json_decode($result, true);
        return array("x" => $response["x"], "y" => $response["y"]);
    }

    /**
     * Converts latitude and longitude coordinates at
     * the given time to Helioprojective coordinates
     * @param float $latitude Latitude coordinate
     * @param float $longitude Longitude coordinate
     * @param string $event_time Time that the event was measured
     * @param string $target Helioviewer observation time
     */
    public static function Hgs2Hpc(float $latitude, float $longitude, string $event_time, ?string $target = null) {
        $target = $target ?? $event_time;
        $result = Coordinator::Get(HV_COORDINATOR_URL . "/hgs2hpc?lat=" . $latitude . "&lon=" . $longitude . "&coord_time=" . urlencode($event_time) . "&target=" . urlencode($target));
        $response = json_decode($result, true);
        return array("x" => $response["x"], "y" => $response["y"]);
    }
}
