<?php
namespace MauticAddon\MauticCrmBundle\Crm\Salesforce\Api\Auth;

use MauticAddon\MauticCrmBundle\Api\Auth\AbstractAuth;
use MauticAddon\MauticCrmBundle\Api\Exception\ErrorException;

class Auth extends AbstractAuth
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
     * @var string Instance Url return by OAuth server
     */
    protected $_instance_url;

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
    protected $_id;

    /**
     * @var string OAuth2 response
     */
    protected $_signature;

    /**
     * @var string OAuth2 scope
     */
    protected $_scope = array();


    /**
     * @param null $clientKey
     * @param null $clientSecret
     * @param null $accessToken
     * @param null $accessTokenSecret
     * @param null $accessTokenExpires
     * @param null $callback
     * @param null $accessTokenUrl
     * @param null $authorizationUrl
     * @param null $requestTokenUrl
     * @param null $scope
     * @param null $refreshToken
     *
     * @oaran null   $instance_url
     */
    public function setup ($id = null, $client_key = null, $client_secret = null, $access_token = null,
                           $callback = null, $accessTokenUrl = null,
                           $authorizationUrl = null, $requestTokenUrl = null, $scope = null, $instance_url = null, $tokenType = null)
    {
        $this->_client_id           = $client_key;
        $this->_client_secret       = $client_secret;
        $this->_access_token        = $access_token;
        $this->_callback            = $callback;
        $this->_access_token_url    = $accessTokenUrl;
        $this->_request_token_url   = $requestTokenUrl;
        $this->_authorize_url       = $authorizationUrl;
        $this->_instance_url        = $instance_url;
        $this->_token_type          = $tokenType;
        $this->_id                  = $id;

        if (!empty($scope)) {
            $this->setScope($scope);
        }
    }

    /**
     * Set an existing/already retrieved access token
     *
     * @param array $accessTokenDetails
     */
    public function setAccessTokenDetails (array $accessTokenDetails)
    {
        $this->_access_token = isset($accessTokenDetails['access_token']) ? $accessTokenDetails['access_token'] : null;
    }

    /**
     * Validate existing access token
     *
     * @return bool
     */
    public function validateAccessToken ()
    {
        if (empty($this->_access_token_url) || empty($this->_authorize_url)) {
            return false;
        }

        if (!isset($_REQUEST['code'])) {
            $this->authorize();

            return false;
        } else {
            $code = $_REQUEST['code'];

            $parameters = array(
                'code'          => $code,
                'grant_type'    => $this->_grant_type,
                'client_id'     => $this->_client_id,
                'client_secret' => $this->_client_secret,
                'redirect_uri'  => $this->_callback,
            );

            $request = $this->makeRequest($this->_access_token_url, $parameters, 'POST');

            if (!empty($request['error'])) {
                throw new ErrorException($request['error_description']);
            }

            $this->_id           = $request['id'];
            $this->_access_token = $request['access_token'];
            $this->_token_type   = $request['token_type'];
            $this->_scope        = $request['scope'];
            $this->_signature    = $request['signature'];
            $this->_instance_url = $request['instance_url'];

            $this->_access_token_updated = true;
        }

        return true;
    }

    /**
     * Authorize App
     */
    public function authorize ()
    {
        $parameters = array(
            'client_id'     => $this->_client_id,
            'response_type' => $this->_response_type,
            'redirect_uri'  => $this->_callback
        );
        $url        = sprintf('%s?%s', $this->_authorize_url, http_build_query($parameters));
        $this->redirect($url);
    }

    /**
     * Returns access token data
     */
    public function getAccessTokenData ()
    {
        return array(
            "id"            => $this->_id,
            "access_token"  => $this->_access_token,
            "token_type"    => $this->_token_type,
            "signature"     => $this->_signature,
            "instance_url"  => $this->_instance_url,
            "scope"         => $this->_scope
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
     */
    public function makeRequest ($url, array $parameters = array(), $method = 'GET', array $settings = array())
    {
        //make sure $method is capitalized for congruency
        $method = strtoupper($method);

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

        $ch = curl_init();

        if (!empty($parameters) && $method !== 'GET') {
            //encode the arguments as JSON
            $parameters = json_encode($parameters);
        }

        //Set post fields for POST/PUT/PATCH requests
        if (in_array($method, array('POST', 'PUT', 'PATCH'))) {
            $options[CURLOPT_POST]       = true;
            $options[CURLOPT_POSTFIELDS] = $parameters;
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Authorization: OAuth {$this->_access_token}",
            "Content-Type: application/json"
        ));

        //Make CURL request
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        curl_close($ch);

        $parsed = json_decode($response, true);

        if (!empty($parsed[0]['errorCode'])) {
            throw new ErrorException($parsed[0]['message']);
        }

        return $parsed;
    }
}