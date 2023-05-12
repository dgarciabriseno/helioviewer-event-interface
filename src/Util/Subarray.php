<?php declare(strict_types=1);

namespace HelioviewerEventInterface\Util;

/**
 * Functions for handling subsections of arrays
 */
class Subarray {
    /**
     * Performs the same action as array_merge, but you can specify the fields to include
     * Copies $fields from $src into $destination
     */
    public static function merge(array $destination, array $src, array $fields): array {
        $subdata = self::extract($src, $fields);
        return array_merge($destination, $subdata);
    }

    /**
     * Extracts the keys in $fields from $data
     */
    public static function extract(array $data, array $fields): array {
        $sub = [];
        foreach ($fields as $key) {
            $sub[$key] = $data[$key];
        }
        return $sub;
    }

    /**
     * Returns a new consisting of the given key list, with their names mapped to new new key names.
     * Ex: Map(['a' => 1, 'b' => 2, 'c' => 3], ['a' => 'd', 'b' => e])
     *     Result: ['d' => 1, 'e' => 2]
     * This is useful if some data has all the data you want, but you want to select a subset of keys and assign your own names to them.
     */
    public static function Map(array $data, array $mapping) {
        $result = [];
        foreach ($mapping as $key => $newKey) {
            $result[$newKey] = $data[$key];
        }
        return $result;
    }
}