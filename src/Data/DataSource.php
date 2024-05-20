<?php declare(strict_types=1);

namespace HelioviewerEventInterface\Data;

use \DateTimeInterface;
use \DateInterval;
use \Exception;
use GuzzleHttp\Client;

/**
 * This abstract class defines an interface for asynchronously querying
 * a data source.
 *
 * IF YOU EDIT THIS CLASS, YOU MUST UPDATE docs/datasources.md
 */
abstract class DataSource {
    /** Name of where the data is coming from */
    public string $source;
    /** Name of data product */
    public string $name;
    /**
     * Reference to an HttpClient. Subclasses should retrieve this with
     * GetClient()
     */
    private static ?Client $HttpClient = null;

    /** Translator class to use for processing data */
    private string $translator;

    public function __construct(string $source, string $name, string $translator) {
        $this->translator = $translator;
        $this->source = $source;
        $this->name = $name;
    }

    /**
     * Queries the data source asynchronously for relevant data between the start and end times.
     * Use getResult() to get the response from the last query.
     * @param DateTimeInterface $start Start of time range
     * @param DateInterval $length Length of time to query
     * @return PromiseInterface
     */
    abstract public function beginQuery(DateTimeInterface $start, DateInterval $length);

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

    protected function Translate(mixed $data, mixed $extra = null): array {
        if (is_null($this->translator)) {
            throw new Exception("Translator is null, you probably didn't call parent::__construct");
        }

        // Ah yes, indulge in string execution.
        return "HelioviewerEventInterface\\Translator\\$this->translator::Translate"($data, $extra);
    }
}
