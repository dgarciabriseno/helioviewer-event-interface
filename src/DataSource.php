<?php declare(strict_types=1);

namespace HelioviewerEventInterface;

use \DateTime;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * A datasource represents an external data source which returns JSON data that we would like to include in Helioviewer.
 * This class defines methods to query data from an external datasource given the appropriate metadata.
 */
class DataSource {
    public string $name;
    protected string $uri;
    protected string $startName;
    protected string $endName;
    protected string $dateFormat;
    protected string $translator;

    /**
     * Creates a new DataSource instance
     * @param string $name The name of this data source.
     * @param string $url The url of for the source's API.
     * @param string $startName The query string parameter name for the start date.
     * @param string $endName The query string parameter name for the end date.
     * @param string $dateFormat The format to use for the dates.
     * @param string $translator The name of the translator class to use for this data source.
     */
    public function __construct(string $name, string $uri, string $startName, string $endName, string $dateFormat, string $translator) {
        $this->name = $name;
        $this->uri = $uri;
        $this->startName = $startName;
        $this->endName = $endName;
        $this->dateFormat = $dateFormat;
        $this->translator = $translator;
    }

    /**
     * Queries the data source asynchronously for relevant data between the start and end times.
     * @param DateTime $start Start of time range
     * @param DateTime $end End of time range
     * @return PromiseInterface
     */
    public function getAsync(DateTime $start, DateTime $end): PromiseInterface {
        // Convert input dates to strings
        $startString = $start->format($this->dateFormat);
        $endString = $end->format($this->dateFormat);
        // Perform HTTP request to the source url
        $client = new Client(["base_uri" => $this->uri]);
        // Define the request with the date range as query parameters
        $promise = $client->requestAsync('GET', '', [
            'query' => [$this->startName => $startString, $this->endName => $endString]
        ]);
        return $promise->then(
            // Decode the json result on a successful request
            function (ResponseInterface $response) {
                return json_decode($response->getBody()->getContents());
            },
            // Fail gracefully on failure by logging the result and returning an empty list representing no data available from this source.
            function (RequestException $e) {
                error_log($e->getMessage());
                return [];
            }
        );
    }
}