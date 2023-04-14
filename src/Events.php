<?php declare(strict_types=1);

namespace HelioviewerEventInterface;

use DateTimeInterface;

use HelioviewerEventInterface\Sources;

/**
 * The entry point for querying all events provided by this event interface module
 */
class Events
{
    /**
     * Returns all data provided by the event interface.
     */
    public static function GetAll(DateTimeInterface $start, DateTimeInterface $end, ?callable $postprocessor = null, array $sources = null): array {
        if (is_null($sources)) {
            $sources = Sources::All();
        }
        return Events::Get($start, $end, $sources, $postprocessor);
    }

    /**
     * Retrieves events from the given data sources
     * @param array $sources Array of strings that name the sources to query.
     */
    public static function GetFromSource(array $sources, DateTimeInterface $start, DateTimeInterface $end, ?callable $postprocessor = null): array {
        $sources = Sources::FromArray($sources);
        return Events::Get($start, $end, $sources, $postprocessor);
    }

    /**
     * Retrieves events from the given source list
     */
    private static function Get(DateTimeInterface $start, DateTimeInterface $end, array $sources, ?callable $postprocessor): array {
        $requests = Events::SendAsyncQueries($start, $end, $sources, $postprocessor);
        $results = Events::WaitForCompletion($requests);
        return Events::AggregateResults($results);
    }

    /**
     * Sends queries out to all defined data sources asynchrously.
     * Returns an array of promises.
     * @TODO: In the future if we have a lot of promises, we should use a request pool.
     */
    private static function SendAsyncQueries(DateTimeInterface $start, DateTimeInterface $end, array $sources, ?callable $postprocessor): array {
        $promises = [];
        // Send off each request asynchronously
        foreach ($sources as $dataSource) {
            $dataSource->beginQuery($start, $end, $postprocessor);
            array_push($promises, $dataSource);
        }
        return $promises;
    }

    /**
     * Waits for all the promises to complete and returns their results in an array
     * These results are Event groups associated with each data source
     */
    private static function WaitForCompletion(array $sources): array {
        $responses = [];
        foreach ($sources as $dataSource) {
            array_push($responses, $dataSource->getResult());
        }
        return $responses;
    }

    /**
     * Merge together responses that fall under the same categories
     */
    private static function AggregateResults(array $categories): array {
        $cache = [];
        foreach ($categories as $category) {
            $name = $category['name'];
            // Combine the groups that fall under the same event name.
            if (array_key_exists($name, $cache)) {
                $cache[$name]['groups'] = array_merge($cache[$name]['groups'], $category['groups']);
            } else {
                // Otherwise added it to the cache
                $cache[$name] = $category;
            }
        }

        $final_result = [];
        foreach ($cache as $event_type => $category) {
            array_push($final_result, $category);
        }
        return $final_result;
    }
}