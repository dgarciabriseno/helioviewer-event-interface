<?php declare(strict_types=1);

namespace HelioviewerEventInterface\Data;

use \DateTimeInterface;
use \DateInterval;
use \Exception;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Cache\CacheItemInterface;
use Psr\Http\Message\ResponseInterface;
use HelioviewerEventInterface\Cache;
use HelioviewerEventInterface\Data\DataSource;

/**
 * The Csv Data Source implements processing a data source read from a CSV file.
 */
class CsvDataSource extends DataSource {
    /**
     * Location of the csv data.
     * This can be remote via https://... or local via file://...
     */
    public string $uri;

    /** Name of class which parses csv data into the proper format */
    public string $translator;

    /** Reference to cached data */
    private ?CacheItemInterface $cache = null;

    /** HTTP Request */
    private PromiseInterface $request;

    /** Extra data to send to the translator */
    private array $extra;

    /** Desired observation time to use for coordinate transformations */
    private DateTimeInterface $obstime;

    /**
     * Construct a csv data source
     * @param string $source Unique name of the data source
     * @param string $name Subname for this data source
     * @param string $uri Location of data, must start with https:// or file://
     * @param string $translator The name of the translator class to use for this data source.
     */
    public function __construct(string $source, string $name, string $uri, string $translator, array $extra = [])
    {
        parent::__construct($source, $name, $translator);
        $this->uri = $uri;
        $this->translator = $translator;
        $this->extra = $extra;
    }

    /**
     * Loads the csv into the memory, either from the source or cache
     * @param DateTimeInterface $start Start of time range
     * @param DateInterval $length Length of time to query
     * @param DateTimeInterface $obstime Observation time, used to transform event coordinates to the position as
     *                                   seen by Helioviewer at this time.
     * @return PromiseInterface
     */
    public function beginQuery(DateTimeInterface $start, DateInterval $length, DateTimeInterface $obstime)
    {
        // Store the query results so they can be used later. The whole csv is
        // cached, so these parameters aren't used in the query, but will be
        // used to filter the data in getResult.
        $this->extra["start"] = $start;
        $this->extra["length"] = $length;
        $this->obstime = $obstime;

        $this->cache = Cache::Get($this->GetCacheKey($start, $length));
        // Do nothing on cache hit.
        if ($this->cache->isHit()) {
            return;
        }

        // PHP 8: str_starts_with
        if (str_starts_with($this->uri, "http")) {
            $this->_LoadRemoteCsv();
        } else if (str_starts_with($this->uri, "file://")) {
            // don't do anything for local files here.
        } else {
            throw new Exception("Unknown URI scheme, expected http or file URI for csv data source");
        }
    }

    private function _LoadRemoteCsv() {
        // Get a reference to the cache for this csv.
        // On cache miss, send the request
        $client = $this->GetClient();
        $promise = $client->requestAsync("GET", $this->uri);
        $this->request = $promise->then(function (ResponseInterface $response) {
                if ($response->getStatusCode() == 200) {
                    return $this->Translate($response->getBody()->getContents(), $this->obstime, $this->extra);
                } else {
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

    private function _LoadLocalCsv(): array {
        $csv = file_get_contents(str_replace("file://", "", $this->uri));
        $data = $this->Translate($csv, $this->obstime, $this->extra);
        Cache::Set($this->cache->getKey(), new DateInterval("P100Y"), $data);
        return $data;
    }

    /**
     * Returns the result from the last query started with beginQuery
     * This will block if the request is still ongoing.
     */
    public function getResult(): array
    {
        // If cache hit, then return cached data
        if (isset($this->cache) && $this->cache->isHit()) {
            return $this->cache->get();
        }

        // If performing a remote request, complete it here.
        if (isset($this->request)) {
            $data = $this->request->wait();
            Cache::Set($this->cache->getKey(), new DateInterval("P100Y"), $data);
            // Since the whole CSV is loaded, the results have to be filtered down
            // to the desired query range
            return $data;
        }

        // Lastly, it's not a remote request, so load the csv from disk here.
        return $this->_LoadLocalCsv();
    }

    /**
     * Generates a key that is unique to this data source and time range
     * @return string
     */
    public function GetCacheKey(DateTimeInterface $start, DateInterval $interval): string
    {
        return Cache::CreateKey($this->name . "_csv", $start, $interval);
    }
}