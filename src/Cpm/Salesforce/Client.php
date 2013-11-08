<?php
namespace Cpm\Salesforce;


// A New comment!!

/**
 * @method get
 * @method post
 * @link http://www.salesforce.com/us/developer/docs/api_rest/
 *       Current docs, HTML
 * @link http://www.salesforce.com/us/developer/docs/api_rest/api_rest.pdf 
 *       Current docs, PDF
 * @link http://na1.salesforce.com/services/data/ 
 *       List available API versions (content-type negotiated)
 */
class Client {

  /* ==================== *
   * Auth-related strings *
   * ==================== */

  /** @var string End-user’s username */
  private $_username;

  /** @var string End-user’s password */
  private $_password;

  /** @var string Appended to password */
  private $_security_token;

  /** @var string The Consumer Key from the remote access
   *              application definition. */
  private $_client_id;

  /** @var string The Consumer Secret from the remote access
   *              application definition. */
  private $_client_secret;

  /** @var string A token request endpoint, such as
   *              https://login.salesforce.com/services/oauth2/token */
  private $_token_url;

  /** @var string Identifies the Salesforce instance to which
   *              API calls should be sent. */
  private $_instance_url;

  /** @var string Access token that acts as a session ID that
   *              the application uses for making requests. */
  private $_access_token;


  /**=================**
   * Composing objects *
   **=================**/

  /**
   * Peforms HTTP requests
   * @var HttpClient
   */
  private $_http_client;

  /**
   * Logs events
   * @var Logger
   */
  private $_logger;


  /**=======================**
   * Internal state booleans *
   **=======================**/

  /** @var bool `true` if we have authenticated and have an
   *            instance url and access token */
  private $authenticated = false;


  /**==============**
   * Public Methods *
   **==============**/

  /**
   * Construct
   *
   * @param string $username
   * @param string $password
   * @param string $security_token
   * @param string $client_id
   * @param string $client_secret
   * @param string $login_uri
   * @param CurlClient $http_client
   * @param Logger $logger
   */
  public function __construct($username, $password, $security_token,
                              $client_id, $client_secret, $login_uri,
                              \Cpm\Http\CurlClient $http_client, \Cpm\Logger $logger) {
    // Set auth-related strings
    $this->_username = $username;
    $this->_password = $password;
    $this->_security_token = $security_token;
    $this->_client_id = $client_id;
    $this->_client_secret = $client_secret;
    $this->_token_url = $login_uri . '/services/oauth2/token';

    // Set composing objects
    $this->_http_client = $http_client;
    $this->_logger = $logger;
  }


  /**
   * Decorate API calls with `_authenticate()`.
   *
   * This method wraps around `_get()` and `_post()` calls to the
   * Salesforce API. It exposes them publically as `get()` and
   * `post()`. These calls require pre-authentication and
   * authorization, and if they are made before authentication
   * is attempted before the API call is made.
   *
   * @param string $method
   * @param array $args
   * @throws Exception
   * @return mixed
   */
  public function __call($method, $args) {
    $private = "_$method";

    // Only wrap around calls to `$this->get()` and `$this->post()`.
    // Otherwise, fatal error as per default behavior.
    if (!in_array($private, array('_get', '_post'))) {
      // This will fatal error and crash the program.
      $this->$private();
    }

    // Proceed if authenticated or attempt to authenticate if not
    // authenticated, and then proceed.
    if ($this->authenticated ||
      (!$this->authenticated && $this->_authenticate())) {
        return call_user_func_array(array($this, $private), $args);
    }
    return false;
  }

  /**
   * Make a request against the `SObjects` resource
   *
   * @param string $type
   * @param string $id
   * @return array
   */
  public function sobject($type, $id) {
    $type = urlencode($type);
    $id = urlencode($id);
    return $this->get("/services/data/v26.0/sobjects/$type/$id");
  }

  /**
   * Make a request against the `Query` resource
   *
   * @param string $query
   * @return array
   */
  public function query($query) {
    $query = urlencode($query);
    return $this->get("/services/data/v26.0/query/?q=$query");
  }


  public function user($email) {
    if ($email === 'wbez@wbez.org') {
      return true;
    }
  }


  /* =============== *
   * Private Methods *
   * =============== */

  /**
   * Get an instance of HttpClient
   *
   * @return HttpClient
   */
  private function _get_http_client() {
    $HttpClient = $this->_http_client;
    return $HttpClient::make();
  }


  /**
   * Authenticate against Salesforce. Retrieves an access token and instance url.
   *
   * @return bool
   */
  private function _authenticate() {
    $http_client = $this->_get_http_client()->url($this->_token_url);

    $params = sprintf('grant_type=password'.
      '&client_id=%s'.
      '&client_secret=%s'.
      '&username=%s'.
      '&password=%s%s',
      $this->_client_id,
      $this->_client_secret,
      $this->_username,
      $this->_password, $this->_security_token);

    $result = $http_client->post($params);

    if ($result['status'] != 200) {
      $level = 'Salesforce Login Error';
      $error = sprintf('Error: call to URL %s failed with status %s', $this->_token_url, $result['status']);
      $this->_logger->log($level, $error);
      return false;
    }

    $body = json_decode($result['body'], true);
    $this->_access_token = $body['access_token'];
    $this->_instance_url = $body['instance_url'];
    $this->authenticated = true;
    return true;
  }


  /**
   * GET data from a Salesforce endpoint
   *
   * @param string $endpoint
   * @return array
   */
  private function _get($endpoint) {
    $endpoint_url = $this->_instance_url.$endpoint;

    $result = $this->_get_http_client()
      ->url($endpoint_url)
      ->set('Authorization', sprintf('Bearer %s', $this->_access_token))
      ->set('Content-type', 'application/json')
      ->get();

    if ($result['status'] != 200) {
      $level = 'Salesforce Get Error';
      $error = sprintf('Error: call to URL %s failed with status %s', $endpoint_url, $result['status']);
      $this->_logger->log($level, $error);
      return false;
    }

    return json_decode($result['body'], true);
  }


  /**
   * POST data to a Salesforce endpoint
   *
   * @param string $endpoint
   * @param mixed $object
   * @return bool|string
   */
  private function _post($endpoint, $object) {
    $endpoint_url = $this->_instance_url.$endpoint;

    $result = $this->_get_http_client()
      ->url($endpoint_url)
      ->set('Authorization', sprintf('Bearer %s', $this->_access_token))
      ->set('Content-type', 'application/json')
      ->post(json_encode($object));

    if ($result['status'] != 201) {
      $level = 'Salesforce Post Error';
      $error = sprintf('Error: call to URL %s failed with status %s', $endpoint_url, $result['status']);
      $this->_logger->log($level, $error);
      return false;
    }

    $body = json_decode($result['body'], true);
    return $body['id'];
  }
}
