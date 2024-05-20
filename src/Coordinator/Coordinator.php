<?php declare(strict_types=1);

namespace HelioviewerEventInterface\Coordinator;

use \Exception;
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
     * @param $x X coordinate in arcseconds
     * @param $y Y coordinate in arcseconds
     * @param $obstime Observation time
     */
    public static function HPC(float $x, float $y, string $obstime) {
        $result = Coordinator::Get(HV_COORDINATOR_URL . "/hpc?x=" . $x . "&y=" . $y . "&obstime=" . urlencode($obstime));
        $response = json_decode($result, true);
        // The response format from the API is just x, y, but better to
        // not make any assumptions. Handle the error here instead of
        // on the client.
        return array("x" => $response["x"], "y" => $response["y"]);
    }

    /**
     * Converts latitude and longitude coordinates at
     * the given time to Helioprojective coordinates
     */
    public static function Hgs2Hpc(float $latitude, float $longitude, string $date) {
        $result = Coordinator::Get(HV_COORDINATOR_URL . "/hgs2hpc?lat=" . $latitude . "&lon=" . $longitude . "&obstime=" . urlencode($date));
        $response = json_decode($result, true);
        // The response format from the API is just x, y, but better to
        // not make any assumptions. Handle the error here instead of
        // on the client.
        return array("x" => $response["x"], "y" => $response["y"]);
    }
}