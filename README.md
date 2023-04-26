# Helioviewer Event Interface
The Helioviewer Event Interface's mission is to provide a framework for easy integration of external data sources into Helioviewer.

This module provides a standard framework for integrating external data.
In general, new data sources can be added in 2 steps:

1. Add a URI to the list of known data sources. See `src/Sources.php`. This provides an endpoint to query data from.
2. Create a definition that describes how to translate data into Helioviewer's format. See `Translator/FlarePrediction.php` as an sample.

This module manages performing the external HTTP requests and passing that data over to a user-defined translator which converts the data into the Helioviewer Event Format to be processed on helioviewer.org.
It also provides a standard framework to follow for translating one object into Helioviewer.
Finally, it manages querying these external data sources which makes this the place to perform optimizations for caching, simultaneous requests, etc.

# Why?
Helioviewer was originally built to exclusively support the [Heliophysics Events Knowledgebase](https://www.lmsal.com/hek/).
For a long time, this has been the only data source supported by Helioviewer even though there are other very relevant data sources available.
NASA's Community Coordinated Modeling Center (CCMC) provides a suite of APIs for accessing scientific data such as Solar Flare Predictions, CME Analyses, Linking Flares to CMEs, Space Weather updates, etc.
All of these datasets that we want to support naturally have their own unique formats which is specific to each dataset.

In order to include **any** dataset in Helioviewer, we are working on a unified [Event Format](https://api.helioviewer.org/docs/v2/appendix/helioviewer_event_format.html) that can deliver the metadata required to display events on Helioviewer, along with the scientific data that the community cares about.

The goal for this project is to facilitate in translating these datasets into Helioviewer's event format.

# Requirements for External Data sources
1. The external data source must provide an HTTP API.
2. The API must accept a start date and end date as input parameters.
3. The API must return fields that specify a location on the sun.

Special note for #3, coordinates must be specified either in Helioprojective Coordinates or Solar Latitude and Longitude.

# Example
As an example, this is how flare predictions are integrated via this Event Interface

1. In `src/Sources.php`, we have added:
```php
new DataSource("CCMC", "Flare Scorebard", "FP", "https://iswa.gsfc.nasa.gov/IswaSystemWebApp/flarescoreboard/hapi/data", "time.min", "time.max", "Y-m-d\TH:i:s", true, "FlarePrediction", ["id" => "SIDC_Operator_REGIONS", "format" => "json", "include" => "header"], "SIDC Operator"),
```

The parameters describe the source, label for the data, pin to use on Helioviewer, and other information required to make the HTTP request.

2. We have also created `src/Translator/FlarePrediction.php`. This file defines a function named `Translate` which will accept the data from the API request, and parse it into the Helioviewer event format.
```php
function Translate(array $data, string $method, ?callable $postProcessor): array {
    $groups = [
        [
            'name' => $method,
            'contact' => "",
            'url' => "https://ccmc.gsfc.nasa.gov/scoreboards/flare/",
            'data' => []
        ]
    ];
    if (count($data['data']) == 0) {
        return $groups;
    }

    $parameters = $data['parameters'];
    $coord = new Hgs2Hpc();
    // Preprocess to only grab the latest predictions
    $result = &$groups[0]['data'];
    foreach ($records as $prediction) {
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
        $lat = GetLatitude($prediction);
        $long = GetLongitude($prediction);
        $time = GetTime($prediction);
        // If there's no positional information, then skip this entry.
        if (is_null($lat) || is_null($long) || is_null($time)) {
            continue;
        }
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
```


### Notes
Looking at `src/Sources.php` is very straightforward on adding new sources. Define the URI, query string date parameters, and the name of the translator file.

- Translators are loaded automatically and they do not follow psr-4 loading.
- Each translator must be defined in its own namespace because they all contain a function named Translate.
- The `Translate` function accepts an array (raw json returned by the API) and returns an array in the form of the Helioviewer Event Format.
- You may include other functions in the file as needed to implement your translator. They are namespaced in your translator's unique namespace so there are no conflicts.

### Configuration
Caching is implemented via Redis.
The Redis server is selected via the php constants `HV_REDIS_HOST` and `HV_REDIS_PORT`.
These should be defined by the application including this package.
