<?php
namespace MauticAddon\MauticCrmBundle\Crm\Vtiger\Api\Auth;

use MauticAddon\MauticCrmBundle\Api\Auth\AbstractAuth;

class Auth extends AbstractAuth
{
    /**
     * @var string Unique Identifier for the session
     */
    protected $_session_id;

    /**
     * @var string Username
     */
    protected $_username;

    /**
     * @var string URL from CRM
     */
    protected $_vtiger_url;

    /**
     * @var string AccessKey from CRM
     */
    protected $_accessKey;

    /**
     * @var int User ID in CRM
     */
    protected $_user_id;

    /**
     * @var string Webservice API version
     */
    protected $_api_version;

    /**
     * @var string Version of CRM
     */
    protected $_vtiger_version;

    /**
     * @var string Token for request session_id
     */
    protected $_token;

    /**
     * @var bool Set to true if a session_id was used to update an access token
     */
    protected $_access_token_updated = false;

    /**
     * @var string Unix timestamp for when token expires
     */
    protected $_expires;

    /**
     * @param null $url
     * @param null $username
     * @param null $accessKey
     * @param null $sessionName
     * @param null $userId
     * @param null $version
     */
    public function setup ($url = null, $username = null, $accessKey = null, $sessionName = null, $userId = null, $version = null)
    {
        $this->_vtiger_url  = $url;
        $this->_username    = $username;
        $this->_accessKey   = $accessKey;
        $this->_session_id  = $sessionName;
        $this->_api_version = $version;
        $this->_user_id     = $userId;
    }

    /**
     * Validate existing access token
     *
     * @return bool
     */
    public function validateAccessToken ()
    {
        $request_url = sprintf('%s/webservice.php', $this->_vtiger_url);
        $parameters  = array(
            'operation' => 'getchallenge',
            'username'  => $this->_username
        );
        $response    = $this->makeRequest($request_url, $parameters);

        if (!$response['success']) {
            return false;
        }

        $this->_expires = $response['result']['expireTime'];
        $this->_token   = $response['result']['token'];

        $loginParameters = array(
            'operation' => 'login',
            'username'  => $this->_username,
            'accessKey' => md5($this->_token . $this->_accessKey)
        );

        $response = $this->makeRequest($request_url, $loginParameters, 'POST');
        if (!$response['success']) {
            return false;
        }

        $this->_session_id     = $response['result']['sessionName'];
        $this->_api_version    = $response['result']['version'];
        $this->_user_id        = $response['result']['userId'];
        $this->_vtiger_version = $response['result']['vtigerVersion'];

        $this->_access_token_updated = true;

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
            'sessionName' => $this->_session_id,
            'userId'      => $this->_user_id,
            'version'     => $this->_api_version,
            'url'         => $this->_vtiger_url
        );
    }

    /**
     * @param        $url
     * @param array  $parameters
     * @param string $method
     * @param array  $settings
     *
     * @return array|mixed|string
     */
    public function makeRequest ($url, array $parameters = array(), $method = 'GET', array $settings = array())
    {
        $method = strtoupper($method);

        if ($method == 'GET') {
            $url .= "?" . http_build_query($parameters);
        }

        $curl_request = curl_init($url);

        if ($method == 'POST') {
            curl_setopt($curl_request, CURLOPT_POST, 1);
        }

        curl_setopt($curl_request, CURLOPT_RETURNTRANSFER, 1);
        if (!empty($parameters) && $method !== 'GET') {
            curl_setopt($curl_request, CURLOPT_POSTFIELDS, $parameters);
        }

        $result = curl_exec($curl_request);
        curl_close($curl_request);

        //decode the response from JSON
        $response = json_decode($result, true);

        return $response;
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function isAuthorized ()
    {
        //Check for existing access token
        if (!empty($this->_session_id)) {
            if (strlen($this->_session_id) > 0) {
                return true;
            }
        }

        return false;
    }
}