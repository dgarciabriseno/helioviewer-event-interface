<?php declare(strict_types=1);

namespace HelioviewerEventInterface\NopTranslator;

/**
 * Test translator that does nothing but return the data as-is
 */
function Translate(array $data): array {
    return $data;
}