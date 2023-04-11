<?php declare(strict_types=1);

namespace HelioviewerEventInterface\Translator;

interface Translator {
    /**
     * The entry point for the translator. Converts the given $data array into Helioviewer Event Format
     */
    public static function Translate(array $data): array;
}