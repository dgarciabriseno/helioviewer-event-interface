<?php declare(strict_types=1);

namespace HelioviewerEventInterface\Translator;

use DateTimeInterface;

/**
 * A Translator does not translate
 */
class NopTranslator {
    /**
     * Test translator that does nothing but return the data as-is
     */
    public static function Translate(mixed $data): array {
        return [$data];
    }

    public static function Transform(array $data): array {
        return $data;
    }

}
