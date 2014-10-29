<?php
namespace Salesforce\Auth;

use Salesforce\Exception\IncorrectParametersReturnedException;
use Salesforce\Exception\UnexpectedResponseFormatException;

class OAuth extends ApiAuth implements AuthInterface
{
    /**
     * @var string Consumer or client key
     */
    protected $_client_id;

    /**
     * @var string Consumer or client secret
     */
    protected $_client_secret;

    /**
     * @var string Callback or Redirect URL
     */
    protected $_callback;

    /**
     * @var string Authorize URL
     */
    protected $_authorize_url;

    /**
     * @var string Access token URL
     */
    protected $_access_token_url;

    /**
     * @var string Request token URL for OAuth1
     */
    protected $_request_token_url;

    /**
     * @var string Access token returned by OAuth server
     */
    protected $_access_token;

    /**
     * @var string Access token secret returned by OAuth server
     */
    protected $_access_token_secret;

    /**
     * @var string Instance Url return by OAuth server
     */
    protected $_instance_url;

    /**
     * @var string Unix timestamp for when token expires
     */
    protected $_expires;

    /**
     * @var string OAuth2 refresh token
     */
    protected $_refresh_token;

    /**
     * @var string OAuth2 token type
     */
    protected $_token_type;

    /**
     * @var string OAuth2 redirect type
     */
    protected $_redirect_type = 'code';

    /**
     * @var string OAuth2 response type
     */
    protected $_response_type = 'code';

    /**
     * @var string OAuth2 grant type
     */
    protected $_grant_type = 'authorization_code';

    /**
     * @var string OAuth2 response
     */
    protected $_id_token;

    /**
     * @var string OAuth2 response
     */
    protected $_signature;

    /**
     * @var string OAuth2 scope
     */
    protected $_scope = array();

    /**
     * @var bool Set to true if a refresh token was used to update an access token
     */
    protected $_access_token_updated = false;

    /**
     * @param null   $clientKey
     * @param null   $clientSecret
     * @param null   $accessToken
     * @param null   $accessTokenSecret
     * @param null   $accessTokenExpires
     * @param null   $callback
     * @param null   $accessTokenUrl
     * @param null   $authorizationUrl
     * @param null   $requestTokenUrl
     * @param null   $scope
     * @param null   $refreshToken
     * @oaran null   $instance_url
     */
    public function setup ($clientKey = null, $clientSecret = null, $accessToken = null, $accessTokenSecret = null,
                           $accessTokenExpires = null, $callback = null, $accessTokenUrl = null,
                           $authorizationUrl = null, $requestTokenUrl = null, $scope = null, $refreshToken = null, $instance_url = null, $tokenType = null)
    {
        $this->_client_id           = $clientKey;
        $this->_client_secret       = $clientSecret;
        $this->_access_token        = $accessToken;
        $this->_access_token_secret = $accessTokenSecret;
        $this->_callback            = $callback;
        $this->_access_token_url    = $accessTokenUrl;
        $this->_request_token_url   = $requestTokenUrl;
        $this->_authorize_url       = $authorizationUrl;
        $this->_instance_url        = $instance_url;
        $this->_token_type          = $tokenType;

        if (!empty($scope)) {
            $this->setScope($scope);
        }

        if (!empty($accessToken)) {
            $this->setAccessTokenDetails(array(
                'access_token'        => $accessToken,
                'access_token_secret' => $accessTokenSecret,
                'expires'             => $accessTokenExpires,
                'refresh_token'       => $refreshToken
            ));
        }
    }

    /**
     * Redirect
     *
     * @param $url
     */
    private function redirect($url)
    {
        header('Location: '.$url);
        exit();
    }

    /**
     * Validate existing access token
     *
     * @return bool
     */
    public function validateAccessToken()
    {
        if (empty($this->_access_token_url) || empty($this->_authorize_url)) {
            return false;
        }

        if (is_null($this->_access_token)) {
            if (!isset($_REQUEST['code'])) {
                $this->authorize();
                return false;
            } else {
                $this->requestAccessToken($_REQUEST['code']);
                $this->_access_token_updated = true;
            }
        }

        return true;
    }

    /**
     * Authorize App
     */
    public function authorize()
    {
        $parameters = array(
            'client_id' => $this->_client_id,
            'response_type' => $this->_response_type,
            'redirect_uri' => $this->_callback
        );
        $url = sprintf('%s?%s', $this->_authorize_url,http_build_query($parameters));
        $this->redirect($url);
    }

    /**
     * Request access token
     */
    protected function requestAccessToken($code)
    {
        $parameters = array(
            'code' => $code,
            'grant_type' => $this->_grant_type,
            'client_id' => $this->_client_id,
            'client_secret' => $this->_client_secret,
            'redirect_uri' => $this->_callback,
        );

        $request = $this->makeRequest($this->_access_token_url, $parameters, 'POST');

        if ( ($request['info']['http_code']<200) || ($request['info']['http_code']>=300) || empty($request)) {
            throw new \RuntimeException("Call to get token from code failed with {$request['info']['http_code']}: '{$this->_access_token_url}' - ".print_r($parameters, true)." - '{$response}'");
        }

        $this->_access_token = $request['response']['access_token'];
        $this->_token_type = $request['response']['token_type'];
        $this->_scope = $request['response']['scope'];
        $this->_id_token = $request['response']['id_token'];
        $this->_signature = $request['response']['signature'];
        $this->_instance_url = $request['response']['instance_url'];
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function isAuthorized ()
    {
        //Check for existing access token
        if (!empty($this->_request_token_url)) {
            if (strlen($this->_access_token) > 0 && strlen($this->_access_token_secret) > 0) {
                return true;
            }
        } else {
            //Check to see if token in session has expired
            if (!empty($this->_expires) && $this->_expires < time()) {
                return false;
            } elseif (strlen($this->_access_token) > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Set OAuth2 scope
     *
     * @param array|string $scope
     */
    public function setScope ($scope)
    {
        if (!is_array($scope)) {
            $this->_scope = explode(',', $scope);
        } else {
            $this->_scope = $scope;
        }
    }

    /**
     * Set an existing/already retrieved access token
     *
     * @param array $accessTokenDetails
     */
    public function setAccessTokenDetails (array $accessTokenDetails)
    {
        $this->_access_token        = isset($accessTokenDetails['access_token']) ? $accessTokenDetails['access_token'] : null;
        $this->_access_token_secret = isset($accessTokenDetails['access_token_secret']) ? $accessTokenDetails['access_token_secret'] : null;
        $this->_expires             = isset($accessTokenDetails['expires']) ? $accessTokenDetails['expires'] : null;
        $this->_refresh_token       = isset($accessTokenDetails['refresh_token']) ? $accessTokenDetails['refresh_token'] : null;
    }

    /**
     * Check to see if the access token was updated from a refresh token
     *
     * @return bool
     */
    public function accessTokenUpdated()
    {
        return $this->_access_token_updated;
    }

    /**
     * Returns access token data
     */
    public function getAccessTokenData ()
    {
        return array(
            "access_token"        => $this->_access_token,
            "expires"             => $this->_expires,
            "token_type"          => $this->_token_type,
            "refresh_token"       => $this->_refresh_token,
            "instance_url"        => $this->_instance_url
        );
    }

    /**
     * Make a request to API/OAuth server
     *
     * @param        $url
     * @param array  $parameters
     * @param string $method
     * @param array  $settings
     *
     * @return mixed
     * @throws UnexpectedResponseFormatException
     */
    public function makeRequest ($url, array $parameters = array(), $method = 'GET', array $settings = array())
    {
        //make sure $method is capitalized for congruency
        $method = strtoupper($method);

        if (!empty($this->_access_token)) {
            $parameters['access_token'] = $this->_access_token;
        }

        $headers = array();

        //Create a querystring for GET/DELETE requests
        if (count($parameters) > 0 && in_array($method, array('GET', 'DELETE')) && strpos($url, '?') === false) {
            $url = $url . '?' . http_build_query($parameters);
        }

        //Set default CURL options
        $options = array(
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true
        );

        if ($this->_access_token) {
            $headers[] = sprintf();
        }
        $options[CURLOPT_HTTPHEADER] = $headers;

        //Set post fields for POST/PUT/PATCH requests
        if (in_array($method, array('POST', 'PUT', 'PATCH'))) {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = $parameters;
        }

        //Make CURL request
        $ch = curl_init();
        curl_setopt_array($ch, $options);
        $response      = curl_exec($ch);
        $info          = curl_getinfo($ch);
        curl_close($ch);

        $parsed = json_decode($response, true);

        return array(
            'info' => $info,
            'response' => $parsed
        );
    }
}