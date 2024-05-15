<?php declare(strict_types=1);

namespace HelioviewerEventInterface\Translator;

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

}
