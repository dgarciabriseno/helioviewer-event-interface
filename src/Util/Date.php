<?php declare(strict_types=1);

namespace HelioviewerEventInterface\Util;

use \DateTime;
use \DateTimeInterface;
use \Throwable;

/**
 * Provides date formatting functions for consistent date handling throughout the API
 */
class Date {
    /**
     * Formats a date provided as a string as 'Y-m-d H:i:s'
     * @param ?string $date Date string to format.
     * @param string $default Default message to use if Date can't be parsed as a date.
     * @return string Date
     */
    public static function FormatString(?string $date, string $default = 'N/A'): string {
        $date = self::ParseDateString($date);
        return self::FormatDate($date, $default);
    }

    /**
     * Formats a date instance as 'Y-m-d H:i:s'
     * @param ?DateTimeInterface $date Date string to format.
     * @param string $default Default message to use if Date can't be parsed as a date.
     * @return string Date
     */
    public static function FormatDate(?DateTimeInterface $date, string $default = 'N/A'): string {
        if (isset($date)) {
            return $date->format('Y-m-d H:i:s');
        } else {
            return $default;
        }
    }

    private static function ParseDateString(?string $date): ?DateTime {
        try {
            return new DateTime($date);
        } catch (Throwable $e) {
            return null;
        }
    }
}