# Salesforce PHP SDK

## Features

* Automatic and lazy authentication and authorization.
* Makes calls to two Salesforce REST API endpoints:
  1. `sobjects` (`/services/data/$VERSION/sobjects`)
  2. `query` (`/services/data/$VERSION/query`)

## Example

```php
<?php

// Load things with Composer. Alternatively, can be loaded manually.
require 'vendor/autoload.php';

use Cpm\Salesforce\Client as SalesforceClient;
use Cpm\HttpClient;
use Cpm\Logger;


// Make a new client
$sf = new SalesforceClient(
  'username', 'password',
  'secuity_token', 'client_id',
  'client_secret', 'login_uri',
  HttpClient::make(), new Logger() 
);


// Get data from the `query` resource. Authentication happens automatically.
$query = sprintf('SELECT id FROM Campaign WHERE rC_Giving__Source_Code__c = %s', 'abcdefghij');
$query_result = $sf->query($query);


// Get data from the `sobjects` resource. The class is still authenticated
$sobject_result = $sf->sobject('Product2', 12345);
```
