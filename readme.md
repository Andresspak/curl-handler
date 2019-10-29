# PHP cURL request handler

Lightweight php cURL request handler.

## Methods
### Single method requests

```php
<?php
$curlHandler = new CurlHandler('https://example.com'); 

$curlHandler->get($path, $headers, $params);
$curlHandler->post($path, $headers, $params);
$curlHandler->put($path, $headers, $params);
$curlHandler->patch($path, $headers, $params);
$curlHandler->delete($path, $headers, $params);
```

### Queued request
Queues requests and runs them asynchronously

```php
<?php
$curlHandler = new CurlHandler('https://example.com'); 

$curlHandler->queue($path, $method, $headers, $params);
$curlHandler->run();
```

## Example

### Single request handling
```php
<?php

$headers = [
    'Content-Type: application/json',
];

$params = [];

$curlHandler = new CurlHandler('https://example.com'); 

list(
    $responseHeaders,   // response headers
    $responseData,      // response content
) = $curlHandler->get('/', $headers, $params);
```

### Queued request handling
```php
<?php

$headers = [
    'Content-Type: application/json',
];

$params = [];

$curlHandler = new CurlHandler('https://example.com'); 

$curlHandler->queue('/api/products', 'GET', $headers, $params);
$curlHandler->queue('/api/elements', 'GET', $headers, $params);

$bulkResponse = $curlHandler->run();

foreach ($bulkResponse as $response){
    // TODO: Handle response data
}
```
