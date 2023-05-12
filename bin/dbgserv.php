<?php

include_once __DIR__."/../vendor/autoload.php";
include_once __DIR__."/../tests/bootstrap.php";

use HelioviewerEventInterface\Events;
use HelioviewerEventInterface\Cache;
// Since this is a debug server, don't bother caching anything.
// This way caching doesn't get in the way of testing changes.
Cache::Clear();

$sources = $_GET['sources'] ?? null;
$start = $_GET['start'] ?? '2023-04-01';
$startDate = new DateTimeImmutable($start);
$length = $_GET['length'] ?? 'P1D';
$length = new DateInterval($length);

if (isset($sources)) {
    $data = Events::GetFromSource($sources, $startDate, $length, null);
} else {
    $data = Events::GetAll($startDate, $length);
}
header("Content-Type: application/json");
echo json_encode($data);