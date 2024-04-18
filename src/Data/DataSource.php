<?php declare(strict_types=1);

namespace HelioviewerEventInterface\Data;

use DateTimeInterface;
use DateInterval;

/**
 * This abstract class defines an interface for asynchronously querying
 * a data source.
 */
abstract class DataSource {
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
}