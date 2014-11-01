<?php
namespace SugarCRM\Auth;

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
     * @var string Unix timestamp for when token expires
     */
    protected $_expires;

    /**
     * @var string OAuth2 refresh token
     */
    protected $_refresh_token;

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
     * @param null   $username
     * @param null   $password
     */
    public function setup ($clientKey = null, $clientSecret = null, $accessToken = null, $accessTokenSecret = null,
                           $accessTokenExpires = null, $callback = null, $accessTokenUrl = null,
                           $authorizationUrl = null, $requestTokenUrl = null, $scope = null, $refreshToken = null, $username = null, $password = null, $sugarcrm_url = null)
    {
        $this->_client_id           = $clientKey;
        $this->_client_secret       = $clientSecret;
        $this->_access_token        = $accessToken;
        $this->_access_token_secret = $accessTokenSecret;
        $this->_callback            = $callback;
        $this->_access_token_url    = $accessTokenUrl;
        $this->_request_token_url   = $requestTokenUrl;
        $this->_authorize_url       = $authorizationUrl;
        $this->_username            = $username;
        $this->_password            = $password;
        $this->_sugarcrm_url        = $sugarcrm_url;

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
     * Set authorization URL
     *
     * @param $url
     */
    public function setAuthorizeUrl ($url)
    {
        $this->_authorize_url = $url;
    }

    /**
     * Set request token URL
     *
     * @param $url
     */
    public function setRequestTokenUrl ($url)
    {
        $this->_request_token_url = $url;
    }

    /**
     * Set access token URL
     *
     * @param $url
     */
    public function setAccessTokenUrl ($url)
    {
        $this->_access_token_url = $url;
    }

    /**
     * Set redirect type for OAuth2
     *
     * @param $type
     */
    public function setRedirectType ($type)
    {
        $this->_redirect_type = $type;
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
     * Validate existing access token
     *
     * @return bool
     */
    public function validateAccessToken ()
    {
        if (empty($this->_request_token_url)) {
            return false;
        }

        if (is_null($this->_access_token) || $this->_access_token == false) {
            $oauth2_token_arguments = array(
                "grant_type" => "password",
                "client_id" => $this->_client_id,
                "client_secret" => $this->_client_secret,
                "username" => $this->_username,
                "password" => $this->_password,
                "platform" => "base"
            );
            $response = $this->makeRequest($this->_request_token_url.'/?method=oauth_request_token', $oauth2_token_arguments, 'POST');

            $this->_access_token_updated = true;
            $this->_access_token = $response['access_token'];
            $this->_refresh_token = $response['refresh_token'];
            $this->_expires = $response['expires_in'];
            $this->_download_token = $response['download_token'];
        }

        return true;
    }

    /**
     * Access Token Data
     *
     * @return array
     */
    public function getAccessTokenData()
    {
        return array(
            "access_token"        => $this->_access_token,
            "expires"             => $this->_expires,
            "refresh_token"       => $this->_refresh_token,
            "download_token"      => $this->_download_token,
            "sugarcrm_url"        => $this->_sugarcrm_url
        );
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
     * @param $url
     * @param array $parameters
     * @param string $method
     * @param array $settings
     * @return array|mixed|string
     */
    public function makeRequest($url, array $parameters = array(), $method = 'GET', array $settings = array('encoded_data' => true))
    {
        $returnHeaders = isset($settings['return_headers']) ? true : false;
        $encodeData = isset($settings['encoded_data']) ? true : false ;
        $type = strtoupper($method);

        if ($type == 'GET')
        {
            $url .= "?" . http_build_query($parameters);
        }

        $curl_request = curl_init($url);

        if ($type == 'POST')
        {
            curl_setopt($curl_request, CURLOPT_POST, 1);
        }
        elseif ($type == 'PUT')
        {
            curl_setopt($curl_request, CURLOPT_CUSTOMREQUEST, "PUT");
        }
        elseif ($type == 'DELETE')
        {
            curl_setopt($curl_request, CURLOPT_CUSTOMREQUEST, "DELETE");
        }

        curl_setopt($curl_request, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($curl_request, CURLOPT_HEADER, $returnHeaders);
        curl_setopt($curl_request, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl_request, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl_request, CURLOPT_FOLLOWLOCATION, 0);

        if (!empty($this->_access_token))
        {
            $token = array("oauth-token: {$this->_access_token}");
            curl_setopt($curl_request, CURLOPT_HTTPHEADER, $token);
        }

        if (!empty($parameters) && $type !== 'GET')
        {
            if ($encodeData)
            {
                //encode the arguments as JSON
                $parameters = json_encode($parameters);
            }
            curl_setopt($curl_request, CURLOPT_POSTFIELDS, $parameters);
        }

        $result = curl_exec($curl_request);

        if ($returnHeaders)
        {
            //set headers from response
            list($headers, $content) = explode("\r\n\r\n", $result ,2);
            foreach (explode("\r\n",$headers) as $header)
            {
                header($header);
            }

            //return the nonheader data
            return trim($content);
        }

        curl_close($curl_request);

        //decode the response from JSON
        $response = json_decode($result, true);

        return $response;
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
}