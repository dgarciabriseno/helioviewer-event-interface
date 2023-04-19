<?php

include_once __DIR__."/../vendor/autoload.php";
use HelioviewerEventInterface\Events;

$sources = $_GET['sources'] ?? null;
$start = $_GET['start'] ?? '2023-04-01';
$startDate = new DateTimeImmutable($start);
$end = $_GET['end'] ?? '2023-04-02';
$endDate = new DateTimeImmutable($end);

$data = Events::GetAll($startDate, $endDate, null, $sources);
header("Content-Type: application/json");
echo json_encode($data);