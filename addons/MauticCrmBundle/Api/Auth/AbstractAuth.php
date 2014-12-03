<?php

namespace MauticAddon\MauticCrmBundle\Api\Auth;

use MauticAddon\MauticCrmBundle\Api\Exception\ErrorException;

abstract class AbstractAuth
{

    /**
     * @var bool Set to true if a refresh token was used to update an access token
     */
    protected $_access_token_updated = false;

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
     * Redirect
     *
     * @param $url
     */
    protected function redirect ($url)
    {
        header('Location: ' . $url);
        exit();
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function isAuthorized ()
    {
        //Check for existing access token
        if (strlen($this->_access_token) === 0) {
            return false;
        }
        //Check to see if token in session has expired
        if (!empty($this->_expires) && $this->_expires < time()) {
            if (strlen($this->_refresh_token === 0)) {
                if (!empty($this->_refresh_token_expires) && $this->_refresh_token_expires < time()) {
                    throw new ErrorException('mautic.integration.error.refreshtoken_expired');
                }
            }
            //try to refresh the token
            $this->validateAccessToken(true);
        }

        return true;
    }

    /**
     * @return bool
     */
    public function accessTokenUpdated()
    {
        return $this->_access_token_updated;
    }


    /**
     * @param        $url
     * @param array  $parameters
     * @param string $method
     * @param array  $settings
     *
     * @return array|mixed|string
     */
    public function makeRequest ($url, array $parameters = array(), $method = 'GET', array $settings = array('encoded_data' => true))
    {
        $returnHeaders = isset($settings['return_headers']) ? true : false;
        $encodeData    = isset($settings['encoded_data']) ? true : false;
        $type          = strtoupper($method);

        if ($type == 'GET') {
            $url .= "?" . http_build_query($parameters);
        }

        $curl_request = curl_init($url);

        if ($type == 'POST') {
            curl_setopt($curl_request, CURLOPT_POST, 1);
        } elseif ($type == 'PUT') {
            curl_setopt($curl_request, CURLOPT_CUSTOMREQUEST, "PUT");
        } elseif ($type == 'DELETE') {
            curl_setopt($curl_request, CURLOPT_CUSTOMREQUEST, "DELETE");
        }

        curl_setopt($curl_request, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($curl_request, CURLOPT_HEADER, $returnHeaders);
        curl_setopt($curl_request, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl_request, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl_request, CURLOPT_FOLLOWLOCATION, 0);

        if (!empty($this->_access_token)) {
            $token = array("oauth-token: {$this->_access_token}");
            curl_setopt($curl_request, CURLOPT_HTTPHEADER, $token);
        }

        if (!empty($parameters) && $type !== 'GET') {
            if ($encodeData) {
                //encode the arguments as JSON
                $parameters = json_encode($parameters);
            }
            curl_setopt($curl_request, CURLOPT_POSTFIELDS, $parameters);
        }

        $result = curl_exec($curl_request);

        if ($returnHeaders) {
            //set headers from response
            list($headers, $content) = explode("\r\n\r\n", $result, 2);
            foreach (explode("\r\n", $headers) as $header) {
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
}