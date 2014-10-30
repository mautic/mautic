<?php
namespace ZohoCRM\Auth;

class Rest extends ApiAuth implements AuthInterface
{
    /**
     * @var string Username or Email
     */
    protected $_email_id;

    /**
     * @var string password
     */
    protected $_password;

    /**
     * @var string return by login
     */
    protected $_authtoken;

    /**
     * @var string Url from JSON API base endpoint
     */
    protected $_endpoint = 'https://crm.zoho.com/crm/private/json/';
    /**
     * @var bool Set to true if a session_id was used to update an access token
     */
    protected $_access_token_updated = false;

    /**
     * @var string Endpoint api url
     */
    protected $_endpoint_url = 'https://crm.zoho.com/crm/private/json/';

    /**
     * @var string Unix timestamp for when token expires
     */
    protected $_expires;

    /**
     * @param null $email_id
     * @param null $password
     * @param null $authtoken
     */
    public function setup($email_id= null, $password = null, $authtoken = null)
    {
        $this->_email_id = $email_id;
        $this->_password = $password;
        $this->_authtoken = $authtoken;
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
        if (is_null($this->_authtoken)) {
            $request_url = 'https://accounts.zoho.com/apiauthtoken/nb/create';
            $parameters = array(
                'SCOPE' => 'ZohoCRM/crmapi',
                'EMAIL_ID' => $this->_email_id,
                'PASSWORD' => $this->_password
            );

            $raw = $this->makeRequest($request_url, $parameters, 'GET', array('raw' => true));

            $attributes = array();
            preg_match_all('(\w*=\w*)',$raw,$matches);
            if (empty($matches[0])) {
                return false;
            }
            foreach ($matches[0] as $string_attribute) {
                $parts = explode('=',$string_attribute);
                $attributes[$parts[0]] = $parts[1];
            }

            if ($attributes['RESULT'] == 'FALSE') {
                return false;
            }

            $this->_authtoken = $attributes['AUTHTOKEN'];
            $this->_access_token_updated = true;
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
            "authtoken"        => $this->_authtoken,
            "endpoint_url"     => $this->_endpoint_url,
        );
    }

    /**
     * @param $url
     * @param array $parameters
     * @param string $method
     * @param array $settings
     * @return array|mixed|string
     */
    public function makeRequest($url, array $parameters = array(), $method = 'GET', array $settings = array())
    {
        $method = strtoupper($method);
        $encodeData = isset($settings['encoded_data']) ? true : false ;

        if ($method == 'GET')
        {
            $url .= "?" . http_build_query($parameters);
        }

        $curl_request = curl_init($url);

        if ($method == 'POST')
        {
            curl_setopt($curl_request, CURLOPT_POST, 1);
        }
        elseif ($method == 'PUT')
        {
            curl_setopt($curl_request, CURLOPT_CUSTOMREQUEST, "PUT");
        }
        elseif ($method == 'DELETE')
        {
            curl_setopt($curl_request, CURLOPT_CUSTOMREQUEST, "DELETE");
        }

        curl_setopt($curl_request, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl_request, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl_request, CURLOPT_RETURNTRANSFER, 1);
        if (!empty($parameters) && $method !== 'GET')
        {
            if ($encodeData)
            {
                //encode the arguments as JSON
                $parameters = json_encode($parameters);
            }
            curl_setopt($curl_request, CURLOPT_POSTFIELDS, $parameters);
        }

        $result = curl_exec($curl_request);
        curl_close($curl_request);

        if (isset($settings['raw'])) {
            return $result;
        }

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
        if (!empty($this->_authtoken)) {
            if (strlen($this->_authtoken) > 0) {
                return true;
            }
        } else {
            //Check to see if token in session has expired
            if (!empty($this->_expires) && $this->_expires < time()) {
                return false;
            } elseif (strlen($this->_authtoken) > 0) {
                return true;
            }
        }

        return false;
    }


}