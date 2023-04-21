<?php declare(strict_types=1);

namespace HelioviewerEventInterface\FlarePrediction;

use HelioviewerEventInterface\Coordinator\Hgs2Hpc;
use HelioviewerEventInterface\Types\HelioviewerEvent;
use HelioviewerEventInterface\Util\HapiRecord;

const FLARE_CLASSES = ["C", "CPlus", "M", "MPlus", "X"];

function Translate(array $data, string $method, ?callable $postProcessor): array {
    $groups = [
        [
            'name' => $method,
            'contact' => "",
            'url' => "https://ccmc.gsfc.nasa.gov/scoreboards/flare/",
            'data' => []
        ]
    ];
    $result = &$groups[0]['data'];
    $parameters = $data['parameters'];
    $coord = new Hgs2Hpc();
    foreach ($data['data'] as $record) {
        $prediction = new HapiRecord($record, $parameters, "");
        $event = new HelioviewerEvent();
        $event->id = hash('sha256', json_encode($prediction));
        $event->label = CreateLabel($prediction, $method);
        $event->version = "";
        $event->type = "FP";
        $event->start = $prediction['start_window'];
        $event->end = $prediction['end_window'];
        $event->source = $prediction->jsonSerialize();
        $event->views = [
            ['name' => 'Flare Prediction',
            'content' => $event->source]
        ];
        $hpc = $coord->convert(GetLatitude($prediction), GetLongitude($prediction), GetTime($prediction));
        $event->hpc_x = $hpc['x'];
        $event->hpc_y = $hpc['y'];
        if ($postProcessor) {
            $event = $postProcessor($event);
        }
        array_push($result, (array) $event);
    }
    return $groups;
}

/**
 * Creates the label text that shows up on Helioviewer for the flare prediction
 */
function CreateLabel(HapiRecord $prediction, string $dataset): string {
    $label = $dataset;

    // Add the flare prediction values to the label
    foreach (FLARE_CLASSES as $class) {
        $label = AppendFlarePredictionToLabel($prediction, $class, $label);
    }
    $label = LabelNoPrediction($prediction, $label);

    return $label;
}

/**
 * Adds the flare prediction as a newline on the label.
 * @return string the new label with the flare string appended to it or the original label if the flare class isn't in the prediction.
 */
function AppendFlarePredictionToLabel(HapiRecord $prediction, string $flare_class, string $label) {
    $flare_label = FlarePredictionString($prediction, $flare_class);
    if ($flare_label != "") {
        $label .= "\n$flare_label";
    }
    return $label;
}

/**
 * Handle the case where all flare prediction values are null
 */
function LabelNoPrediction(HapiRecord $prediction, string $label): string {
    $all_null = true;
    foreach (FLARE_CLASSES as $flare_class) {
        if (hasValue($prediction, $flare_class)) {
            $all_null = false;
            break;
        }
    }

    if ($all_null) {
        return $label . "\nNo probabilities given";
    }
    return $label;
}

function hasValue(HapiRecord $prediction, string $key): bool {
    // Cannot use isset because it will return true even if the value returned by the array access is null.
    return !is_null($prediction[$key]);
}

/**
 * Returns a flare prediction value as a string representation.
 * Example: FlarePredictionString($prediction, "c") -> "c: 0.75"
 */
function FlarePredictionString(HapiRecord $prediction, string $flare_class): string {
    if (hasValue($prediction, $flare_class)) {
        // Probability
        $probability = GetProbablity($prediction, $flare_class);
        // Make sure the flare class is uppercase in the label
        $tentative_label = strtoupper($flare_class) . ": " . $probability;
        // Replace the word "Plus" in the label with the plus sign.
        $tentative_label = str_replace("PLUS", "+", $tentative_label);
        return $tentative_label;
    }
    return "";
}


/**
 * Returns a percentage based on the given prediction data and flare class
 */
function GetProbablity(HapiRecord $prediction, string $flare_class): ?string {
    if (hasValue($prediction, $flare_class)) {
        $tmp = $prediction[$flare_class] * 100;
        return round($tmp, 2) . "%";
    }
    return null;
}

function GetLatitude(HapiRecord $prediction): mixed {
    return $prediction['NOAALatitude'] ?? $prediction['CataniaLatitude'] ?? $prediction['ModelLatitude'];
}

function GetLongitude(HapiRecord $prediction): mixed {
    return $prediction['NOAALongitude'] ?? $prediction['CataniaLongitude'] ?? $prediction['ModelLongitude'];
}

function GetTime(HapiRecord $prediction): mixed {
    return $prediction['NOAALocationTime'] ?? $prediction['CataniaLocationTime'] ?? $prediction['ModelLocationTime'];
}