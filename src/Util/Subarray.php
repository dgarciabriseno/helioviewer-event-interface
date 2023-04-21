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
}