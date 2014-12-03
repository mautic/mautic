<?php
namespace MauticAddon\MauticCrmBundle\Crm\Sugarcrm\Api\Auth;


use MauticAddon\MauticCrmBundle\Api\Auth\ApiAuth;
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

    /*
     * @var string Unix timestamp for when token expires
     */
    protected $_expires;

    /**
     * @var string OAuth2 refresh token
     */
    protected $_refresh_token;

    /**
     * @var string OAuth2 refresh token expiry
     */
    protected $_refresh_token_expires;

    /**
     * @var string OAuth2 Response download token
     */
    protected $_download_token;

    /**
     * @var string sugar url
     */
    protected $_sugarcrm_url;

    /**
     * @var string OAuth2 scope
     */
    protected $_scope = array();

    /**
     * @var bool Set to true if a refresh token was used to update an access token
     */
    protected $_access_token_updated = false;

    private $_username;
    private $_password;

    /**
     * @param null $client_key
     * @param null $client_secret
     * @param null $access_token
     * @param null $expires
     * @param null $callback
     * @param null $accessTokenUrl
     * @param null $authorizationUrl
     * @param null $requestTokenUrl
     * @param null $scope
     * @param null $refresh_token
     * @param null $username
     * @param null $password
     * @param null $url
     * @param null $download_key
     */
    public function setup ($client_key = null, $client_secret = null, $access_token = null,
                           $expires = null, $callback = null, $accessTokenUrl = null,
                           $authorizationUrl = null, $requestTokenUrl = null, $scope = null, $refresh_token = null, $refresh_token_expires = null, $username = null, $password = null, $url = null, $download_token = null)
    {

        $this->_client_id           = $client_key;
        $this->_client_secret       = $client_secret;
        $this->_callback            = $callback;
        $this->_access_token_url    = $accessTokenUrl;
        $this->_request_token_url   = $requestTokenUrl;
        $this->_authorize_url       = $authorizationUrl;
        $this->_username            = $username;
        $this->_password            = $password;
        $this->_sugarcrm_url        = $url;
        $this->_download_token      = $download_token;

        if (!empty($scope)) {
            $this->setScope($scope);
        }

        if (!empty($access_token)) {
            $this->setAccessTokenDetails(array(
                'access_token'        => $access_token,
                'expires'             => $expires,
                'refresh_token'       => $refresh_token,
                'refresh_token_expires' => $refresh_token_expires,
                'download_token'      => $download_token
            ));
        }
    }

    /**
     * Set an existing/already retrieved access token
     *
     * @param array $accessTokenDetails
     */
    public function setAccessTokenDetails (array $accessTokenDetails)
    {
        $this->_access_token          = isset($accessTokenDetails['access_token']) ? $accessTokenDetails['access_token'] : null;
        $this->_expires               = isset($accessTokenDetails['expires']) ? $accessTokenDetails['expires'] : null;
        $this->_refresh_token         = isset($accessTokenDetails['refresh_token']) ? $accessTokenDetails['refresh_token'] : null;
        $this->_refresh_token_expires = isset($accessTokenDetails['refresh_token_expires']) ? $accessTokenDetails['refresh_token_expires'] : null;
        $this->_download_token        = isset($accessTokenDetails['download_token']) ? $accessTokenDetails['download_token'] : null;
    }

    /**
     * Validate existing access token
     *
     * @return bool
     */
    public function validateAccessToken ($refreshToken = false)
    {
        if (empty($this->_request_token_url)) {
            return false;
        }

        $oauth2_token_arguments = array(
            "grant_type"    => ($refreshToken) ? "refresh_token" : "password",
            "client_id"     => $this->_client_id,
            "client_secret" => $this->_client_secret,
            "username"      => $this->_username,
            "password"      => $this->_password,
            "platform"      => "base"
        );

        if ($refreshToken) {
            $oauth2_token_arguments['refresh_token'] = $this->_refresh_token;
        }

        $response = $this->makeRequest($this->_request_token_url . '?method=oauth_request_token', $oauth2_token_arguments, 'POST');

        if (isset($response['error'])) {
            throw new ErrorException($response['error_message']);
        } elseif (empty($response)) {
            throw new ErrorException('mautic.integration.error.response.empty');
        }

        $this->_access_token_updated = true;
        if (isset($response['expires_in'])) {
            $response['expires'] = $response['expires_in'] + time();
        }

        if (isset($response['expires_in'])) {
            $response['expires'] = $response['expires_in'] + time();
        }
        if (isset($response['refresh_expires_in'])) {
            $response['refresh_token_expires'] = $response['refresh_expires_in'] + time();
        }

        $this->setAccessTokenDetails($response);

        return true;
    }

    /**
     * Access Token Data
     *
     * @return array
     */
    public function getAccessTokenData ()
    {
        return array(
            "access_token"          => $this->_access_token,
            "expires"               => $this->_expires,
            "refresh_token"         => $this->_refresh_token,
            "refresh_token_expires" => $this->_refresh_token_expires,
            "download_token"        => $this->_download_token,
            "sugarcrm_url"          => $this->_sugarcrm_url
        );
    }
}