<?php

require __DIR__ . '/../vendor/autoload.php';

use Cpm\Salesforce\Client as SalesforceClient;


class SalesforceClientTest extends PHPUnit_Framework_TestCase {
  public function setUp() {

    $this->client = new SalesforceClient(
      'username',
      'password',
      'security_token',
      'client_id',
      'client_secret',
      'login_uri',

    );
  }

  public function testConstruct() {

  }

}
