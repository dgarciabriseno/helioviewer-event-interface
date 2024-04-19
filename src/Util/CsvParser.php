<?php declare(strict_types=1);

namespace HelioviewerEventInterface\Util;

use ValueError;

/**
 * CsvParser provides helper functions for reading CSV data.
 */
class CsvParser {
    /**
     * Converts a line of CSV text into a key->value pair array using the given
     * keys. The keys should align to each value in the comma separated list.
     *
     * @throws ValueError when the number of keys doesn't match the number of values in the line.
     */
    public static function ToArray(array $keys, string $line, string $separator = ','): array {
        $values = explode($separator, $line);
        self::ValidateSameLength($keys, $values);
        $out = [];
        foreach ($keys as $idx => $key) {
            $out[$key] = $values[$idx];
        }
        return $out;
    }

    /** Throws a ValueError if the length of $a doesn't match the length of $b */
    private static function ValidateSameLength(array $a, array $b) {
        $a_count = count($a);
        $b_count = count($b);
        if ($a_count != $b_count) {
            throw new ValueError("Array counts don't match");
        }
    }
}