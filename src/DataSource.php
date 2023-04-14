<?php declare(strict_types=1);

namespace HelioviewerEventInterface;

use \DateTimeInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * A datasource represents an external data source which returns JSON data that we would like to include in Helioviewer.
 * This class defines methods to query data from an external datasource given the appropriate metadata.
 */
class DataSource {
    public string $source;
    public string $name;
    public string $pin;
    protected string $uri;
    protected string $startName;
    protected string $endName;
    protected string $dateFormat;
    protected string $translator;
    private PromiseInterface $request;

    /**
     * Creates a new DataSource instance
     * @param string $name The name of the source data. It's okay for this to be a duplicate of other sources
     * @param string $pin The pin to use for this specific resource
     * @param string $url The url of for the source's API.
     * @param string $startName The query string parameter name for the start date.
     * @param string $endName The query string parameter name for the end date.
     * @param string $dateFormat The format to use for the dates.
     * @param string $translator The name of the translator class to use for this data source.
     */
    public function __construct(string $source, string $name, string $pin, string $uri, string $startName, string $endName, string $dateFormat, string $translator) {
        $this->source = $source;
        $this->name = $name;
        $this->pin = $pin;
        $this->uri = $uri;
        $this->startName = $startName;
        $this->endName = $endName;
        $this->dateFormat = $dateFormat;
        $this->translator = $translator;
    }

    /**
     * Queries the data source asynchronously for relevant data between the start and end times.
     * Use getResult() to get the response from the last query.
     * @param DateTimeInterface $start Start of time range
     * @param DateTimeInterface $end End of time range
     * @param callable $postprocessor Executable function to call on each Helioviewer Event processed during the query
     * @return PromiseInterface
     */
    public function beginQuery(DateTimeInterface $start, DateTimeInterface $end, ?callable $postprocessor = null) {
        // Convert input dates to strings
        $startString = $start->format($this->dateFormat);
        $endString = $end->format($this->dateFormat);
        // Perform HTTP request to the source url
        $client = new Client(["base_uri" => $this->uri]);
        // Define the request with the date range as query parameters
        $promise = $client->requestAsync('GET', '', [
            'query' => [$this->startName => $startString, $this->endName => $endString]
        ]);
        $this->request = $promise->then(
            // Decode the json result on a successful request
            function (ResponseInterface $response) use ($postprocessor) {
                $data = json_decode($response->getBody()->getContents(), true);
                // Load the requested translator and execute it
                include_once __DIR__ . "/Translator/" . $this->translator . ".php";
                // Ah yes, indulge in string execution.
                return "HelioviewerEventInterface\\$this->translator\\Translate"($data, $postprocessor);
            },
            // Fail gracefully on failure by logging the result and returning an empty list representing no data available from this source.
            function (RequestException $e) {
                error_log($e->getMessage());
                return [];
            }
        );
    }

    /**
     * Returns the result from the last query started with beginQuery
     * This will block if the request is still ongoing.
     */
    public function getResult(): array {
        if (isset($this->request)) {
            $group = $this->request->wait();
            return $this->BuildEventCategory($group);
        }
        error_log("Attempted to get the result without calling beginQuery");
        return [];
    }

    private function BuildEventCategory(array $group): array {
        return [
            'name' => $this->name,
            'pin' => $this->pin,
            'groups' => [$group]
        ];
    }
}