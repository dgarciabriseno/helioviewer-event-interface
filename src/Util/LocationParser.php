<?php declare(strict_types=1);

namespace HelioviewerEventInterface\Util;

class LocationParser
{
    /**
     * Parses location latitude longitude text in the form 'N10E20' or 'S05W10'
     * @return array [latitude, longitude]
     */
    public static function ParseText(string $location): array {
        $north_south = $location[0];
        $north_south_value = intval(substr($location, 1, 2));
        // North Positive, South Negative
        $latitude = $north_south == "N" ? $north_south_value : -$north_south_value;

        $east_west = $location[3];
        $east_west_value = intval(substr($location, 4, 2));
        // East Negative, West Positive
        $longitude = $east_west == "E" ? -$east_west_value : $east_west_value;
        return [$latitude, $longitude];
    }
}