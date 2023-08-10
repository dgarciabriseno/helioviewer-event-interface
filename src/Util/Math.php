<?php declare(strict_types=1);

namespace HelioviewerEventInterface\Util;

/**
 * Math related functions
 */
class Math {
    /**
     * Compute the magnitude of a 2D vector
     */
    public static function magnitude($x, $y) {
        return sqrt(pow($x, 2) + pow($y, 2));
    }
}
