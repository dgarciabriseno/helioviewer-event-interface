<?php declare(strict_types=1);

namespace HelioviewerEventInterface\Data;

use DateTimeInterface;
use DateInterval;
use Exception;
use GuzzleHttp\Client;

/**
 * This abstract class defines an interface for asynchronously querying
 * a data source.
 *
 * IF YOU EDIT THIS CLASS, YOU MUST UPDATE docs/datasources.md
 */
abstract class DataSource {
    /**
     * Reference to an HttpClient. Subclasses should retrieve this with
     * GetClient()
     */
    private static ?Client $HttpClient = null;

    /** Translator class to use for processing data */
    private string $translator;

    public function __construct(string $translator) {
        $this->translator = $translator;
    }

    /**
     * Queries the data source asynchronously for relevant data between the start and end times.
     * Use getResult() to get the response from the last query.
     * @param DateTimeInterface $start Start of time range
     * @param DateInterval $length Length of time to query
     * @param callable $postprocessor Executable function to call on each Helioviewer Event processed during the query
     * @return PromiseInterface
     */
    abstract public function beginQuery(DateTimeInterface $start, DateInterval $length, ?callable $postprocessor = null);

    /**
     * Returns the result from the last query started with beginQuery
     * This will block if the request is still ongoing.
     */
    abstract public function getResult(): array;


    /**
     * Generates a key that is unique to this data source
     * @return string
     */
    abstract public function GetCacheKey(DateTimeInterface $date, DateInterval $interval): string;

    protected static function GetClient(): Client {
        if (is_null(self::$HttpClient)) {
            self::$HttpClient = new Client([]);
        }
        return self::$HttpClient;
    }

    protected function Translate(mixed $data, mixed $extra = null, ?callable $postprocessor = null): array {
        if (is_null($this->translator)) {
            throw new Exception("Translator is null, you probably didn't call parent::__construct");
        }

        // Load the requested translator and execute it
        include_once __DIR__ . "/../Translator/" . $this->translator . ".php";
        // Ah yes, indulge in string execution.
        return "HelioviewerEventInterface\\$this->translator\\Translate"($data, $extra, $postprocessor);
    }
}