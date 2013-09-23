# Salesforce PHP SDK

## Features

* Automatic authentication and authorization
* Makes calls to two Salesforce REST API endpoints:
  1. `sobjects` (`/services/data/$VERSION/sobjects/`)
  2. `query` (`/services/data/$VERSION/query/?q=$query/`)

## Example

```php
<?php

// Load things with Composer. Alternatively, these classes can be loaded manually.
require 'vendor/autoload.php';

use Cpm\Salesforce\Client as SalesforceClient
use Cpm\HttpClient 
use Cpm\Logger

// Conforms to the `IHttpClient` interface. Default implementation is with PHP's cURL library. A mock implementation can be substituted for testing.
$http_client = new HttpClient();

// Conforms to the `ILogger` interface. Default implementation logs to Drupal `watchdog()`, or elsewhere for testing outside of Drupal.
$logger = new Logger();

// Make a new client
$sf_client = new SalesforceClient(
  'username', 'password',
  'secuity_token', 'client_id',
  'client_secret', 'login_uri',
  $http_client, $logger
);

// Get data from Salesforce
// Authentication happens automatically as necessary!
$query = sprintf('SELECT id FROM Campaign WHERE rC_Giving__Source_Code__c = %s', 'abcdefghij');
$query_result = $sf_client->query($query);

// Get more data, this time from a `sobjects` resource.
// The class is still authenticated
$sobject_result = $sf_client->sobject('Product2', 12345);
```
