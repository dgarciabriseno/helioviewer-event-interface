<?php declare(strict_types=1);

namespace HelioviewerEventInterface\Util;

/**
 * The Camel2Title reader can be used to extract and parse array fields which have camelCased keys.
 * This will convert the camel keys into title keys. For example "modelCompletionTime" -> "Model Completion Time"
 * This is useful for converting raw JSON into Views.
 * Note that language is assumed to be english
 */
class Camel2Title {
    /**
     * Parses $data and returns the data with title cased keys.
     * If $fields is given, then only those fields will be processed
     */
    static public function Parse(array $data, ?array $fields = null): array {
        $processed = [];
        $fields = $fields ?? array_keys($data);
        foreach ($fields as $key) {
            $value = $data[$key];
            $titleCase = self::camel2Title($key);
            $processed[$titleCase] = $value;
        }
        return $processed;
    }

    private static function camel2Title(string $str): string {
        // Split by uppercase characters
        $split = preg_split('/(?=[A-Z])/', $str);
        // Rejoin the words with spaces in between each word
        $spaced = implode(' ', $split);
        // ucwords will capitalize the first letter of each word
        return ucwords($spaced);
    }
}