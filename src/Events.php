<?php declare(strict_types=1);

namespace HelioviewerEventInterface;

use \DateInterval;
use \DateTimeImmutable;
use \DateTimeInterface;

use HelioviewerEventInterface\Sources;

/**
 * The entry point for querying all events provided by this event interface module
 */
class Events
{
    /**
     * Returns all data provided by the event interface.
     * @param DateTimeInterface $start Reference point in time for determining the query
     * @param DateInterval $length Duration of time to query, can be forward or back
     * @param DateTimeInterface $obstime Observation time, used to transform event coordinates to the position as
     *                                   seen by Helioviewer at this time.
     */
    public static function GetAll(DateTimeInterface $start, DateInterval $length, DateTimeInterface $obstime): array {
        return Events::Get($start, $length, Sources::All(), $obstime);
    }

    /**
     * Retrieves events from the given data sources
     * @param array $sources Array of strings that name the sources to query.
     * @param DateTimeInterface $start Reference point in time for determining the query
     * @param DateInterval $length Duration of time to query, can be forward or back
     * @param DateTimeInterface $obstime Observation time, used to transform event coordinates to the position as
     *                                   seen by Helioviewer at this time.
     */
    public static function GetFromSource(array $sources, DateTimeInterface $start, DateInterval $length, DateTimeInterface $obstime): array {
        if (count($sources) == 0) {
            return [];
        }
        $sources = Sources::FromArray($sources);
        return Events::Get($start, $length, $sources, $obstime);
    }

    /**
     * Retrieves events from the given source list
     * @param DateTimeInterface $start Reference point in time for determining the query
     * @param DateInterval $length Duration of time to query, can be forward or back
     * @param array $sources Array of strings that name the sources to query.
     * @param DateTimeInterface $obstime Observation time, used to transform event coordinates to the position as
     *                                   seen by Helioviewer at this time.
     */
    private static function Get(DateTimeInterface $start, DateInterval $length, array $sources, DateTimeInterface $obstime): array {
        // Begin asynchronous requests for event data
        $requests = Events::SendAsyncQueries($start, $length, $sources, $obstime);
        // Wait for all requests to complete, and get all of their responses.
        $results = Events::WaitForCompletion($requests);
        // Merge all the event data into the combined helioviewer event format.
        $events = Events::AggregateResults($results);
        // Filter events so only events within the observation time are returned.
        return Events::FilterEvents($events, $obstime);
    }

    /**
     * Sends queries out to all defined data sources asynchrously.
     * Returns an array of promises.
     * @param DateTimeInterface $start Reference point in time for determining the query
     * @param DateInterval $length Duration of time to query, can be forward or back
     * @param array $sources Array of strings that name the sources to query.
     * @param DateTimeInterface $obstime Observation time, used to transform event coordinates to the position as
     *                                   seen by Helioviewer at this time.
     * @TODO: In the future if we have a lot of promises, we should use a request pool.
     */
    private static function SendAsyncQueries(DateTimeInterface $start, DateInterval $length, array $sources, DateTimeInterface $obstime): array {
        $promises = [];
        // Send off each request asynchronously
        foreach ($sources as $dataSource) {
            $dataSource->beginQuery($start, $length, $obstime);
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

    /**
     * Filters the given event data so only the events that contain the
     * observation time are returned
     * @param array $events Aggregated event data
     * @param DateTimeInterface $obstime Observation time.
     * @return array filtered events
     */
    private static function FilterEvents(array $events, DateTimeInterface $obstime): array {
        foreach ($events as &$source) {
            foreach ($source['groups'] as &$group) {
                $group['data'] = array_filter($group['data'], function ($event) use ($obstime) {
                    $start = new DateTimeImmutable($event['start']);
                    $end = new DateTimeImmutable($event['end']);
                    return ($start <= $obstime) && ($obstime <= $end);
                });
            }
        }
        return $events;
    }
}