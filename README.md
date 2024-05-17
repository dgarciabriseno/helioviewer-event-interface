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
As an example, this is how Flares from DONKI are integrated via this Event Interface.
These flares don't have too much data to parse, so they make an ideal example.

1. In `src/Sources.php`, we have added:
```php
new DataSource("CCMC", "DONKI", "F1", "https://kauai.ccmc.gsfc.nasa.gov/DONKI/WS/get/FLR", "startDate", "endDate", "Y-m-d", "DonkiFlare")
```

The parameters describe the source, label for the data, pin to use on Helioviewer, API URL, the API's date parameters, the format to send the date to the API, and the Translator to use.

2. We define the translator `src/Translator/DonkiFlare.php`. This file defines a function named `Translate` which will accept the data from the API request, and parse each record into a `HelioviewerEvent`.
```php
function Translate(array $flares, mixed $extra, ?callable $postProcessor): array {
    $groups = [
        [
            'name' => 'Solar Flares',
            'contact' => '',
            'url' => 'https://kauai.ccmc.gsfc.nasa.gov/DONKI/',
            'data' => []
        ]
    ];
    $data = &$groups[0]['data'];
    foreach ($flares as $flare) {
        $flare = new Flare($flare);
        $event = new HelioviewerEvent();
        $event->id = $flare->id();
        $event->label = $flare->label();
        $event->version = '';
        $event->type = 'FL';
        $event->start = $flare->start();
        $event->end = $flare->end();
        $event->source = $flare->flare;
        $event->views = $flare->views();
        list($event->hv_hpc_x, $event->hv_hpc_y) = $flare->hpc();
        $event->link = $flare->link();
        if ($postProcessor) {
            $event = $postProcessor($event);
        }
        array_push($data, (array) $event);
    }
    return $groups;
}
```
In this example, we iterate over each flare returned from the API, and pass that data to a `Flare` class (left out of for brevity).

This flare class defines some functions which parses the source data to return the fields that we'd like to put in the `HelioviewerEvent`.

Once all the records are parsed, we return them wrapped with some Helioviewer Event Format metadata.

### Notes
Looking at `src/Sources.php` is very straightforward on adding new sources. Define the URI, query string date parameters, and the name of the translator file.

- Translators are loaded automatically and they do not follow psr-4 loading.
- Each translator must be defined in its own namespace because they all contain a function named Translate.
- The `Translate` function accepts an array (raw json returned by the API) and returns an array in the form of the Helioviewer Event Format.
- You may include other functions in the file as needed to implement your translator. They are namespaced in your translator's unique namespace so there are no conflicts.

### Utils
There are several classes for helping with parsing data. For example, some data sources return data that already has nice key-value pairs, but they use camel case. For viewing on Helioviewer we want human readable title case with spaces. `Camel2Title` can take care of that.

Data from DONKI often uses the format "N10E33" for locations on the sun. That's handled by `LocationParser`.

If the data is from a HAPI server, `HapiRecord` will greatly help with processing the data, it lets you access the data using the parameter names instead of numeric indices.

When writing your own translator, review the features available in Util to see if any of them can help you parse your data source.
Feel free to contribute your own helpers as well.

### Configuration & Dependencies
- Redis
Caching is implemented via Redis.
The Redis server is selected via the php constants `HV_REDIS_HOST` and `HV_REDIS_PORT` and `HV_REDIS_DB`.
If `HV_REDIS_DB` is not defined, this will default to using index 10 in Redis.
These must be defined by the application including this package.

- Minimum PHP version 8.0
