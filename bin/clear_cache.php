#!/usr/bin/env php
<?php

include_once __DIR__ . "/../vendor/autoload.php";

use HelioviewerEventInterface\Cache;
use HelioviewerEventInterface\Events;
use HelioviewerEventInterface\Sources;

function print_usage($name) {
	echo "Usage:

$name redis_host redis_port DateTime

Clears the redis cache for the given DateTime

";
}

function parse_args($argv) {
    if (count($argv) < 4) {
        print_usage($argv[0]);
        exit(0);
    }

    return [
        'host' => $argv[1],
        'port' => intval($argv[2]),
        'datetime' => new DateTimeImmutable($argv[3])
    ];
}

function clear_keys(DateTimeInterface $date, DateInterval $length): void {
    // Clears the "AllSources" key
    Events::ClearCache($date, $length);
    $sources = Sources::All();
    foreach ($sources as $dataSource) {
        // Clear the cache for the event interface
        Events::ClearCache($date, $length, [$dataSource->source]);
        // Clear the key for the datasource
        $key = $dataSource->GetCacheKey($date, $length);
        Cache::ClearKey($key);
    }
}

$args = parse_args($argv);
define('HV_REDIS_HOST', $args['host']);
define('HV_REDIS_PORT', $args['port']);
clear_keys($args['datetime'], new DateInterval('P1D'));