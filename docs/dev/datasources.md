# Data Source Writing Guide

This document aims to provide guidance on implementing new data source
interfaces. The purpose of these interfaces is to define how to query
different types of data i.e. (CSV, TAP, JSON, SOAP, etc). If you are integrating
a new data source, you should first try to use one of the existing DataSource
interfaces. If the protocol you use to get retrieve data isn't supported, then
you may need to create / request a new interface.

## Supported Interfaces

The Helioviewer Event Interface currently supports:
- JSON APIs via http requests
- CSV files, either local or remote via http.

## DataSource Interface

The **DataSource** interface is an abstraction over both the JSON, CSV, and
any future implementations for different types of data. The main event interface
API will use this interface to request data from a specific data source, and the
implementation via subclasses (`JsonDataSource`, `CsvDataSource`, etc) will take
care of processing the data.

Any Data Source implementation must implement 3 functions:
| Function    | Description |
|-------------|-------------|
| beginQuery  | Starts an asynchronous request to load the desired data |
| getResult   | Returns the final dataset which conforms to the event interface format |
| GetCacheKey | Returns a unique key for the returned data |

This interface is designed to accomodate caching requests, and allowing the
main driver to query all requested datasets simultaneously.

### Helper Functions

The datasource interface provides the following helper functions for implementing
data sources:

| Function  | Description |
|-----------|-------------|
| GetClient | Returns a guzzle http client which can be used to send async requests |
| Translate | Runs the designated translator on the given data |
| Tranform  | Runs the designated coordinate transformations on the given data |


## Guidelines

Expect the driver to make 3 calls to each data source instance:

1. constructor: XDataSource(datasource details)
2. beginQuery(desired time range)
3. getResult()

### Constructor
The constructor must always call the super constructor:
```
public function __construct($custom_param1, $custom_param2, string $translator) {
    parent::__construct($translator);
}
```

The base `DataSource` class handles tasks which are expected to be used across
multiple implementations, and it needs some information (i.e. the translator
to run) in order to do that.

### beginQuery

When `beginQuery` is called, you should check if the data already exists in the
cache, and if not, then start an asynchronous request to query
and process the data. If your data source runs synchronously, for example to
read a local file, then leave this function blank. The driver will run `beginQuery`
on all data sources to schedule multiple HTTP requests. It's best if you don't
block this process.

1. Check if requested range is already cached
2. If cache hit, then do nothing!
3. If cache miss, schedule asynchronous request which returns translated data.

<details>
<summary>Reference Code</summary>

For scheduling asynchronous requests, follow this (pseudo?)code:
```php
// Get the cache key for this request (Data should be cached on the hour)
$key = $this->GetCacheKey(params);
// Place cache in an instance variable so it can be used later without calling Cache::Get again.
$this->$cache = Cache::Get($key);
// If data is already in the cache, then do nothing
if ($this->cache->isHit()) {
    return;
}
// On cache miss, kick off the http request
// Get a reference to the Guzzle\HttpClient
$client = $this->GetClient();
// Make an asynchronous request. Store it on instance variable so it can be
// accessed in getResult
$promise = $client->requestAsync(query parameters);
// Define the work to be done when the request is complete
$this->request = $promise->then(function (ResponseInterface $response) {
    $data = custom_response_parser($response);
    return $this->Translate($data);
});
```

</details>

### getResult

`getResult` should return the processed data. If the data was already in the cache,
then simply return the cached data. If you are performing a synchronous request,
then that should be done here. Otherwise, complete the asynchronous request,
store the processed data in the cache, and return the results.

1. If cache hit, get data from cache
2. If cache miss, wait for http request to complete
3. Perform any final processing
4. Cache data
5. Transform data coordinates to observation time
6. return data

<details>
<summary>Code Reference</summary>

```php
// Assume you stored the results of Cache::Get into $this->cache
if ($this->cache && $this->cache->isHit()) {
    $data = $this->cache->get();
    return $this->Transform($data, $this->observation_time);
}

// Cache miss.
// If you followed the previous code reference, this will be the processed data
$data = $this->request->wait();
// Save data to the cache for future requests
Cache::Set($this->cache->getKey(), Cache::DefaultExpiry(params), $data);
return $this->Transform($data, $this->observation_time);
```

</details>