<?php declare(strict_types=1);

namespace HelioviewerEventInterface;

use DateInterval;
use DateTimeImmutable;
use \DateTimeInterface;
use \Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Request;
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
    protected bool   $reverse;
    protected string $translator;
    protected ?array $queryParameters;
    protected mixed  $extra;
    private PromiseInterface $request;
    private static ?Client $HttpClient = null;

    private static function GetClient() {
        if (is_null(self::$HttpClient)) {
            self::$HttpClient = new Client([]);
        }
        return self::$HttpClient;
    }

    /**
     * Generates a key that is unique to this data source
     * @return string
     */
    public function GetCacheKey(): string {
        $data = "$this->source $this->name " . json_encode($this->queryParameters) . json_encode($this->extra);
        return strval(crc32($data));
    }

    /**
     * Creates a new DataSource instance
     * @param string $name The name of the source data. It's okay for this to be a duplicate of other sources
     * @param string $pin The pin to use for this specific resource
     * @param string $url The url of for the source's API.
     * @param string $startName The query string parameter name for the start date.
     * @param string $endName The query string parameter name for the end date.
     * @param string $dateFormat The format to use for the dates.
     * @param bool   $reverse Whether this data source should query backwards in time instead of forwards in time.
     * @param string $translator The name of the translator class to use for this data source.
     * @param ?array $queryParameters Constant parameters that will pass through to the http request
     * @param mixed  $extra Extra data to pass through to the translator
     */
    public function __construct(string $source, string $name, string $pin, string $uri, string $startName, string $endName, string $dateFormat, bool $reverse, string $translator, ?array $queryParameters = null, mixed $extra = null) {
        $this->source = $source;
        $this->name = $name;
        $this->pin = $pin;
        $this->uri = $uri;
        $this->startName = $startName;
        $this->endName = $endName;
        $this->dateFormat = $dateFormat;
        $this->reverse = $reverse;
        $this->translator = $translator;
        $this->queryParameters = $queryParameters;
        $this->extra = $extra;
    }

    /**
     * Queries the data source asynchronously for relevant data between the start and end times.
     * Use getResult() to get the response from the last query.
     * @param DateTimeInterface $start Start of time range
     * @param DateInterval $length Length of time to query
     * @param callable $postprocessor Executable function to call on each Helioviewer Event processed during the query
     * @return PromiseInterface
     */
    public function beginQuery(DateTimeInterface $start, DateInterval $length, ?callable $postprocessor = null) {
        $this->sendAsyncQuery($start, $length, $postprocessor);
    }

    /**
     * Queries the data source asynchronously for relevant data between the start and end times.
     * Use getResult() to get the response from the last query.
     * @param DateTimeInterface $start Start of time range
     * @param DateInterval $length Length of time to query
     * @param callable $postprocessor Executable function to call on each Helioviewer Event processed during the query
     * @return PromiseInterface
     */
    private function sendAsyncQuery(DateTimeInterface $start, DateInterval $length, ?callable $postprocessor = null) {
        // Convert input dates to strings
        if ($this->reverse) {
            $endString = $start->format($this->dateFormat);
            $startDate = DateTimeImmutable::createFromInterface($start);
            $startString = $startDate->sub($length)->format($this->dateFormat);
        } else {
            $startString = $start->format($this->dateFormat);
            $startDate = DateTimeImmutable::createFromInterface($start);
            $endString = $startDate->add($length)->format($this->dateFormat);
        }
        // Perform HTTP request to the source url
        $client = self::GetClient();
        // Define the request with the date range as query parameters
        $params = array_merge([$this->startName => $startString, $this->endName => $endString], $this->queryParameters ?? []);
        $promise = $client->requestAsync('GET', $this->uri, [
            'query' => $params
        ]);
        $extra = $this->extra;
        $this->request = $promise->then(
            // Decode the json result on a successful request
            function (ResponseInterface $response) use ($postprocessor, $extra) {
                $data = json_decode($response->getBody()->getContents(), true);
                if (isset($data)) {
                    // Load the requested translator and execute it
                    include_once __DIR__ . "/Translator/" . $this->translator . ".php";
                    // Ah yes, indulge in string execution.
                    return "HelioviewerEventInterface\\$this->translator\\Translate"($data, $extra, $postprocessor);
                } else {
                    // If data is null, then there's no data for the query, return an empty list.
                    return [];
                }
            },
            // Fail gracefully on failure by logging the result and returning an empty list representing no data available from this source.
            function (Exception $e) {
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
            $groups = $this->request->wait();
            return $this->BuildEventCategory($groups);
        }
        error_log("Attempted to get the result without calling beginQuery");
        return $this->BuildEventCategory(null);
    }

    private function BuildEventCategory(?array $groups): array {
        $frame = [
            'name' => $this->name,
            'pin' => $this->pin,
            'groups' => $groups ?? []
        ];

        return $frame;
    }
}