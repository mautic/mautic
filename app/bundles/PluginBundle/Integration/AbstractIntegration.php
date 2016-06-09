<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PluginBundle\Integration;

use Joomla\Http\HttpFactory;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Event\PluginIntegrationKeyEvent;
use Mautic\PluginBundle\Event\PluginIntegrationRequestEvent;
use Mautic\PluginBundle\Helper\oAuthHelper;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PluginBundle\PluginEvents;
use Symfony\Component\Form\FormBuilder;

/**
 * Class AbstractIntegration
 */
abstract class AbstractIntegration
{

    /**
     * @var MauticFactory
     */
    protected $factory;

    /**
     * @var Integration
     */
    protected $settings;

    /**
     * @var array Decrypted keys
     */
    protected $keys;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->factory = $factory;

        $this->init();
    }

    /**
     * Called on construct
     */
    public function init()
    {

    }

    /**
     * Determines what priority the integration should have against the other integrations
     *
     * @return int
     */
    public function getPriority()
    {
        return 9999;
    }

    /**
     * Returns the name of the social integration that must match the name of the file
     * For example, IcontactIntegration would need Icontact here
     *
     * @return string
     */
    abstract public function getName();

    /**
     * Name to display for the integration. e.g. iContact  Uses value of getName() by default
     *
     * @return string
     */
    public function getDisplayName()
    {
        return $this->getName();
    }

    /**
     * Returns a description shown in the config form
     *
     * @return string
     */
    public function getDescription()
    {
        return '';
    }

    /**
     * Get the type of authentication required for this API.  Values can be none, key, oauth2 or callback
     * (will call $this->authenticationTypeCallback)
     *
     * @return string
     */
    abstract public function getAuthenticationType();

    /**
     * Get a list of supported features for this integration
     *
     * @return array
     */
    public function getSupportedFeatures()
    {
        return array();
    }

    /**
     * Returns the field the integration needs in order to find the user
     *
     * @return mixed
     */
    public function getIdentifierFields()
    {
        return array();
    }

    /**
     * Allows integration to set a custom form template
     *
     * @return string
     */
    public function getFormTemplate()
    {
        return 'MauticPluginBundle:Integration:form.html.php';
    }

    /**
     * Allows integration to set a custom theme folder
     *
     * @return string
     */
    public function getFormTheme()
    {
        return 'MauticPluginBundle:FormTheme\Integration';
    }

    /**
     * Set the social integration entity
     *
     * @param Integration $settings
     */
    public function setIntegrationSettings(Integration $settings)
    {
        $this->settings = $settings;

        $this->keys = $this->getDecryptedApiKeys();
    }

    /**
     * Get the social integration entity
     *
     * @return Integration
     */
    public function getIntegrationSettings()
    {
        return $this->settings;
    }

    /**
     * Persist settings to the database
     */
    public function persistIntegrationSettings()
    {
        $em = $this->factory->getEntityManager();
        $em->persist($this->settings);
        $em->flush();
    }

    /**
     * Merge api keys
     *
     * @param             $mergeKeys
     * @param             $withKeys
     * @param bool|false  $return Returns the key array rather than setting them
     *
     * @return void|array
     *
     */
    public function mergeApiKeys($mergeKeys, $withKeys = array(), $return = false)
    {
        $settings = $this->settings;
        if (empty($withKeys)) {
            $withKeys = $this->keys;
        }

        foreach ($withKeys as $k => $v) {
            if (!empty($mergeKeys[$k])) {
                $withKeys[$k] = $mergeKeys[$k];
            }
            unset($mergeKeys[$k]);
        }

        //merge remaining new keys
        $withKeys = array_merge($withKeys, $mergeKeys);

        if ($return) {
            $this->keys = $this->dispatchIntegrationKeyEvent(
                PluginEvents::PLUGIN_ON_INTEGRATION_KEYS_MERGE,
                $withKeys
            );

            return $this->keys;
        } else {
            $this->encryptAndSetApiKeys($withKeys, $settings);

            //reset for events that depend on rebuilding auth objects
            $this->setIntegrationSettings($settings);
        }
    }

    /**
     * Encrypts and saves keys to the entity
     *
     * @param array       $keys
     * @param Integration $entity
     */
    public function encryptAndSetApiKeys(array $keys, Integration $entity)
    {
        $this->keys = $keys;

        $keys = $this->dispatchIntegrationKeyEvent(
            PluginEvents::PLUGIN_ON_INTEGRATION_KEYS_ENCRYPT,
            $keys
        );

        // Update keys
        $this->keys = array_merge($this->keys, $keys);

        $encrypted = $this->encryptApiKeys($keys);
        $entity->setApiKeys($encrypted);
    }

    /**
     * Returns already decrypted keys
     *
     * @return mixed
     */
    public function getKeys()
    {
        return $this->keys;
    }

    /**
     * Returns decrypted API keys
     *
     * @param bool $entity
     *
     * @return array
     */
    public function getDecryptedApiKeys($entity = false)
    {
        static $decryptedKeys = array();

        if (!$entity) {
            $entity = $this->settings;
        }

        $keys = $entity->getApiKeys();

        $serialized = serialize($keys);
        if (empty($decryptedKeys[$serialized])) {
            $decryptedKeys[$serialized] = $this->dispatchIntegrationKeyEvent(
                PluginEvents::PLUGIN_ON_INTEGRATION_KEYS_DECRYPT,
                $this->decryptApiKeys($keys)
            );
        }

        return $decryptedKeys[$serialized];
    }

    /**
     * Encrypts API keys
     *
     * @param array $keys
     *
     * @return array
     */
    public function encryptApiKeys(array $keys)
    {
        /** @var \Mautic\CoreBundle\Helper\EncryptionHelper $helper */
        $helper    = $this->factory->getHelper('encryption');
        $encrypted = array();

        foreach ($keys as $name => $key) {
            $key              = $helper->encrypt($key);
            $encrypted[$name] = $key;
        }

        return $encrypted;
    }

    /**
     * Decrypts API keys
     *
     * @param array $keys
     *
     * @return array
     */
    public function decryptApiKeys(array $keys)
    {
        /** @var \Mautic\CoreBundle\Helper\EncryptionHelper $helper */
        $helper    = $this->factory->getHelper('encryption');
        $decrypted = array();

        foreach ($keys as $name => $key) {
            $key              = $helper->decrypt($key);
            $decrypted[$name] = $key;
        }

        return $decrypted;
    }

    /**
     * Get the array key for clientId
     *
     * @return string
     */
    public function getClientIdKey()
    {
        switch ($this->getAuthenticationType()) {
            case 'oauth1a':
                return 'consumer_id';
            case 'oauth2':
                return 'client_id';
            case 'key':
                return 'key';
            default:
                return '';
        }
    }

    /**
     * Get the array key for client secret
     *
     * @return string
     */
    public function getClientSecretKey()
    {
        switch ($this->getAuthenticationType()) {
            case 'oauth1a':
                return 'consumer_secret';
            case 'oauth2':
                return 'client_secret';
            case 'basic':
                return 'password';
            default:
                return '';
        }
    }

    /**
     * Array of keys to mask in the config form
     *
     * @return array
     */
    public function getSecretKeys()
    {
        return array($this->getClientSecretKey());
    }

    /**
     * Get the array key for the auth token
     *
     * @return string
     */
    public function getAuthTokenKey()
    {
        switch ($this->getAuthenticationType()) {
            case 'oauth2':
                return 'access_token';
            case 'oauth1a':
                return 'oauth_token';
            default:
                return '';
        }
    }

    /**
     * Get the keys for the refresh token and expiry
     *
     * @return array
     */
    public function getRefreshTokenKeys()
    {
        return array();
    }

    /**
     * Get a list of keys required to make an API call.  Examples are key, clientId, clientSecret
     *
     * @return array
     */
    public function getRequiredKeyFields()
    {
        switch ($this->getAuthenticationType()) {
            case 'oauth1a':
                return array(
                    'consumer_id'     => 'mautic.integration.keyfield.consumerid',
                    'consumer_secret' => 'mautic.integration.keyfield.consumersecret'
                );
            case 'oauth2':
                return array(
                    'client_id'     => 'mautic.integration.keyfield.clientid',
                    'client_secret' => 'mautic.integration.keyfield.clientsecret'
                );
            case 'key':
                return array(
                    'key' => 'mautic.integration.keyfield.api'
                );
            case 'basic':
                return array(
                    'username' => 'mautic.integration.keyfield.username',
                    'password' => 'mautic.integration.keyfield.password'
                );
            default:
                return array();
        }
    }

    /**
     * Extract the tokens returned by the oauth callback
     *
     * @param string $data
     * @param bool   $postAuthorization
     *
     * @return mixed
     */
    public function parseCallbackResponse($data, $postAuthorization = false)
    {
        return json_decode($data, true);
    }

    /**
     * Generic error parser
     *
     * @param $response
     *
     * @return string
     */
    public function getErrorsFromResponse($response)
    {
        if (is_object($response)) {
            if (!empty($response->errors)) {
                $errors = array();
                foreach ($response->errors as $e) {
                    $errors[] = $e->message;
                }

                return implode('; ', $errors);
            } elseif (!empty($response->error->message)) {
                return $response->error->message;
            } else {
                return (string) $response;
            }
        } elseif (is_array($response)) {
            if (isset($response['error_description'])) {
                return $response['error_description'];
            } elseif (isset($response['error'])) {
                if (is_array($response['error'])) {
                    if (isset($response['error']['message'])) {
                        return $response['error']['message'];
                    } else {
                        return implode(', ', $response['error']);
                    }
                } else {
                    return $response['error'];
                }
            } elseif (isset($response['errors'])) {
                $errors = array();
                foreach ($response['errors'] as $err) {
                    if (is_array($err)) {
                        if (isset($err['message'])) {
                            $errors[] = $err['message'];
                        } else {
                            $errors[] = implode(', ', $err);
                        }
                    } else {
                        $errors[] = $err;
                    }
                }

                return implode('; ', $errors);
            }

            return $response;
        } else {
            return $response;
        }
    }

    /**
     * Make a basic call using cURL to get the data
     *
     * @param        $url
     * @param array  $parameters
     * @param string $method
     * @param array  $settings
     *
     * @return mixed|string
     */
    public function makeRequest($url, $parameters = array(), $method = 'GET', $settings = array())
    {
        $method   = strtoupper($method);
        $authType = (empty($settings['auth_type'])) ? $this->getAuthenticationType() : $settings['auth_type'];

        list($parameters, $headers) = $this->prepareRequest($url, $parameters, $method, $settings, $authType);

        if (empty($settings['ignore_event_dispatch'])) {
            $event = $this->factory->getDispatcher()->dispatch(
                PluginEvents::PLUGIN_ON_INTEGRATION_REQUEST,
                new PluginIntegrationRequestEvent($this, $url, $parameters, $headers, $method, $settings, $authType)
            );

            $headers    = $event->getHeaders();
            $parameters = $event->getParameters();
        }

        if (!isset($settings['query'])) {
            $settings['query'] = array();
        }

        if (!$this->isConfigured()) {
            return array(
                'error' => array(
                    'message' => $this->factory->getTranslator()->trans(
                        'mautic.integration.missingkeys'
                    )
                )
            );
        }

        if ($method == 'GET' && !empty($parameters)) {
            $parameters = array_merge($settings['query'], $parameters);
            $query      = http_build_query($parameters);
            $url .= (strpos($url, '?') === false) ? '?'.$query : '&'.$query;
        } elseif (!empty($settings['query'])) {
            $query = http_build_query($settings['query']);
            $url .= (strpos($url, '?') === false) ? '?'.$query : '&'.$query;
        }

        // Check for custom content-type header
        if (!empty($settings['content_type'])) {
            $settings['encoding_headers_set'] = true;
            $headers[]                        = "Content-type: {$settings['content_type']}";
        }

        if ($method !== 'GET') {
            if (!empty($parameters)) {
                if ($authType == 'oauth1a') {
                    $parameters = http_build_query($parameters);
                }
                if (!empty($settings['encode_parameters'])) {
                    if ($settings['encode_parameters'] == 'json') {
                        //encode the arguments as JSON
                        $parameters = json_encode($parameters);
                        if (empty($settings['encoding_headers_set'])) {
                            $headers[] = 'Content-Type: application/json';
                        }
                    }
                }
            }
        }

        $referer = $this->getRefererUrl();
        $options = array(
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_HEADER         => 1,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_FOLLOWLOCATION => 0,
            CURLOPT_REFERER        => $referer
        );

        if (isset($settings['curl_options'])) {
            $options = array_merge($options, $settings['curl_options']);
        }

        if (isset($settings['ssl_verifypeer'])) {
            $options[CURLOPT_SSL_VERIFYPEER] = $settings['ssl_verifypeer'];
        }

        $connector = HttpFactory::getHttp(
            array(
                'transport.curl' => $options
            )
        );

        $parseHeaders = (isset($settings['headers'])) ? array_merge($headers, $settings['headers']) : $headers;

        // HTTP library requires that headers are in key => value pairs
        $headers = array();
        foreach ($parseHeaders as $key => $value) {
            if (strpos($value, ':') !== false) {
                list($key, $value) = explode(':', $value);
                $key   = trim($key);
                $value = trim($value);
            }

            $headers[$key] = $value;
        }

        try {
            switch ($method) {
                case 'GET':
                    $result = $connector->get($url, $headers, 10);
                    break;
                case 'POST':
                case 'PUT':
                case 'PATCH':
                    $connectorMethod = strtolower($method);
                    $result          = $connector->$connectorMethod($url, $parameters, $headers, 10);
                    break;
                case 'DELETE':
                    $result = $connector->delete($url, $headers, 10);
                    break;
            }
        } catch (\Exception $exception) {

            return array('error' => array('message' => $exception->getMessage(), 'code' => $exception->getCode()));
        }

        if (empty($settings['ignore_event_dispatch'])) {
            $event->setResponse($result);
            $this->factory->getDispatcher()->dispatch(
                PluginEvents::PLUGIN_ON_INTEGRATION_RESPONSE,
                $event
            );
        }

        if (!empty($settings['return_raw'])) {

            return $result;
        } else {

            $response = $this->parseCallbackResponse($result->body, !empty($settings['authorize_session']));

            return $response;
        }
    }

    /*
     * Method to prepare the request parameters. Builds array of headers and parameters
     *
     * @return array
     */
    public function prepareRequest($url, $parameters, $method, $settings, $authType)
    {
        $clientIdKey     = $this->getClientIdKey();
        $clientSecretKey = $this->getClientSecretKey();
        $authTokenKey    = $this->getAuthTokenKey();
        $authToken       = (isset($this->keys[$authTokenKey])) ? $this->keys[$authTokenKey] : '';
        $authTokenKey    = (empty($settings[$authTokenKey])) ? $authTokenKey : $settings[$authTokenKey];

        $headers = array();

        if (!empty($settings['authorize_session'])) {
            switch ($authType) {
                case 'oauth1a':
                    $requestTokenUrl = $this->getRequestTokenUrl();
                    if (!array_key_exists('append_callback', $settings) && !empty($requestTokenUrl)) {
                        $settings['append_callback'] = false;
                    }
                    $oauthHelper = new oAuthHelper($this, $this->factory->getRequest(), $settings);
                    $headers     = $oauthHelper->getAuthorizationHeader($url, $parameters, $method);
                    break;
                case 'oauth2':
                    if ($bearerToken = $this->getBearerToken(true)) {
                        $headers                  = array(
                            "Authorization: Basic {$bearerToken}",
                            "Content-Type: application/x-www-form-urlencoded;charset=UTF-8"
                        );
                        $parameters['grant_type'] = 'client_credentials';
                    } else {
                        $defaultGrantType = (!empty($settings['refresh_token'])) ? 'refresh_token'
                            : 'authorization_code';
                        $grantType        = (!isset($settings['grant_type'])) ? $defaultGrantType
                            : $settings['grant_type'];

                        $useClientIdKey     = (empty($settings[$clientIdKey])) ? $clientIdKey : $settings[$clientIdKey];
                        $useClientSecretKey = (empty($settings[$clientSecretKey])) ? $clientSecretKey
                            : $settings[$clientSecretKey];
                        $parameters         = array_merge(
                            $parameters,
                            array(
                                $useClientIdKey     => $this->keys[$clientIdKey],
                                $useClientSecretKey => $this->keys[$clientSecretKey],
                                'grant_type'        => $grantType
                            )
                        );

                        if (!empty($settings['refresh_token']) && !empty($this->keys[$settings['refresh_token']])) {
                            $parameters[$settings['refresh_token']] = $this->keys[$settings['refresh_token']];
                        }

                        if ($grantType == 'authorization_code') {
                            $parameters['code'] = $this->factory->getRequest()->get('code');
                        }
                        if (empty($settings['ignore_redirecturi'])) {
                            $callback                   = $this->getAuthCallbackUrl();
                            $parameters['redirect_uri'] = $callback;
                        }
                    }
                    break;
            }
        } else {
            switch ($authType) {
                case 'basic':
                    $headers = array(
                        'Authorization' => 'Basic '.base64_encode($this->keys['username'].':'.$this->keys['password']),
                    );
                    break;
                case 'oauth1a':
                    $oauthHelper = new oAuthHelper($this, $this->factory->getRequest(), $settings);
                    $headers     = $oauthHelper->getAuthorizationHeader($url, $parameters, $method);
                    break;
                case 'oauth2':
                    if ($bearerToken = $this->getBearerToken()) {
                        $headers = array(
                            "Authorization: Bearer {$bearerToken}",
                            //"Content-Type: application/x-www-form-urlencoded;charset=UTF-8"
                        );
                    } else {
                        if (!empty($settings['append_auth_token'])) {
                            $settings['query'][$authTokenKey] = $authToken;
                        } else {
                            $parameters[$authTokenKey] = $authToken;
                        }

                        $headers = array(
                            "oauth-token: $authTokenKey",
                            "Authorization: OAuth {$authToken}",
                        );
                    }
                    break;
                case 'key':
                    $parameters[$authTokenKey] = $authToken;
                    break;
            }
        }

        return array($parameters, $headers);
    }

    /**
     * Generate the auth login URL.  Note that if oauth2, response_type=code is assumed.  If this is not the case,
     * override this function.
     *
     * @return string
     */
    public function getAuthLoginUrl()
    {
        $authType = $this->getAuthenticationType();

        if ($authType == 'oauth2') {
            $callback    = $this->getAuthCallbackUrl();
            $clientIdKey = $this->getClientIdKey();
            $state       = $this->getAuthLoginState();
            $url         = $this->getAuthenticationUrl()
                .'?client_id='.$this->keys[$clientIdKey]
                .'&response_type=code'
                .'&redirect_uri='.urlencode($callback)
                .'&state='.$state;

            if ($scope = $this->getAuthScope()) {
                $url .= '&scope='.urlencode($scope);
            }

            $this->factory->getSession()->set($this->getName().'_csrf_token', $state);

            return $url;
        } else {
            return $this->factory->getRouter()->generate(
                'mautic_integration_auth_callback',
                array('integration' => $this->getName())
            );
        }
    }

    /**
     * State variable to append to login url (usually used in oAuth flows)
     *
     * @return string
     */
    public function getAuthLoginState()
    {
        return hash('sha1', uniqid(mt_rand()));
    }

    /**
     * Get the scope for auth flows
     *
     * @return string
     */
    public function getAuthScope()
    {
        return '';
    }

    /**
     * Gets the URL for the built in oauth callback
     *
     * @return string
     */
    public function getAuthCallbackUrl()
    {
        return $this->factory->getRouter()->generate(
            'mautic_integration_auth_callback',
            array('integration' => $this->getName()),
            true //absolute
        );
    }

    /**
     * Retrieves and stores tokens returned from oAuthLogin
     *
     * @param array $settings
     * @param array $parameters
     *
     * @return bool|string false if no error; otherwise the error string
     */
    public function authCallback($settings = array(), $parameters = array())
    {
        $authType = $this->getAuthenticationType();

        switch ($authType) {
            case 'oauth2':
                if (!empty($settings['use_refresh_token'])) {
                    // Try refresh token
                    $refreshTokenKeys = $this->getRefreshTokenKeys();
                    if (!empty($refreshTokenKeys)) {
                        list($refreshTokenKey, $expiryKey) = $refreshTokenKeys;

                        $settings['refresh_token'] = $refreshTokenKey;
                    }
                }
                break;

            case 'oauth1a':
                // After getting request_token and authorizing, post back to access_token
                $settings['append_callback']  = true;
                $settings['include_verifier'] = true;

                // Get request token returned from Twitter and submit it to get access_token
                $settings['request_token'] = $this->factory->getRequest()->get('oauth_token');
                break;
        }

        $settings['authorize_session'] = true;

        $method = (!isset($settings['method'])) ? 'POST' : $settings['method'];
        $data   = $this->makeRequest($this->getAccessTokenUrl(), $parameters, $method, $settings);

        return $this->extractAuthKeys($data);

    }

    /**
     * Extacts the auth keys from response and saves entity
     *
     * @param $data
     * @param $tokenOverride
     *
     * @return bool|string false if no error; otherwise the error string
     */
    public function extractAuthKeys($data, $tokenOverride = null)
    {
        //check to see if an entity exists
        $entity = $this->getIntegrationSettings();
        if ($entity == null) {
            $entity = new Integration();
            $entity->setName($this->getName());
        }

        // Prepare the keys for extraction such as renaming, setting expiry, etc
        $data = $this->prepareResponseForExtraction($data);

        //parse the response
        $authTokenKey = ($tokenOverride) ? $tokenOverride : $this->getAuthTokenKey();
        if (is_array($data) && isset($data[$authTokenKey])) {
            $keys      = $this->mergeApiKeys($data, null, true);
            $encrypted = $this->encryptApiKeys($keys);
            $entity->setApiKeys($encrypted);

            $this->factory->getSession()->set($this->getName().'_tokenResponse', $data);

            $error = false;
        } elseif (is_array($data) && isset($data['access_token'])) {
            $this->factory->getSession()->set($this->getName().'_tokenResponse', $data);
            $error = false;
        } else {
            $error = $this->getErrorsFromResponse($data);
            if (empty($error)) {
                $error = $this->factory->getTranslator()->trans(
                    "mautic.integration.error.genericerror",
                    array(),
                    "flashes"
                );
            }
        }

        //save the data
        $em = $this->factory->getEntityManager();
        $em->persist($entity);
        $em->flush();

        $this->setIntegrationSettings($entity);

        return $error;
    }

    /**
     * Called in extractAuthKeys before key comparison begins to give opportunity to set expiry, rename keys, etc
     *
     * @param $data
     *
     * @return mixed
     */
    public function prepareResponseForExtraction($data)
    {
        return $data;
    }

    /**
     * Checks to see if the integration is configured by checking that required keys are populated
     *
     * @return bool
     */
    public function isConfigured()
    {
        $requiredTokens = $this->getRequiredKeyFields();
        foreach ($requiredTokens as $token => $label) {
            if (empty($this->keys[$token])) {

                return false;
            }
        }

        return true;
    }

    /**
     * Checks if an integration is authorized and/or authorizes the request
     *
     * @return bool
     */
    public function isAuthorized()
    {
        if (!$this->isConfigured()) {

            return false;
        }

        $type         = $this->getAuthenticationType();
        $authTokenKey = $this->getAuthTokenKey();

        switch ($type) {
            case 'oauth1a':
            case 'oauth2':
                $refreshTokenKeys = $this->getRefreshTokenKeys();
                if (!isset($this->keys[$authTokenKey])) {
                    $valid = false;
                } elseif (!empty($refreshTokenKeys)) {
                    list($refreshTokenKey, $expiryKey) = $refreshTokenKeys;
                    if (!empty($this->keys[$refreshTokenKey]) && !empty($expiryKey) && isset($this->keys[$expiryKey])
                        && time() > $this->keys[$expiryKey]
                    ) {
                        //token has expired so try to refresh it
                        $error = $this->authCallback(array('refresh_token' => $refreshTokenKey));
                        $valid = (empty($error));
                    } else {
                        // The refresh token doesn't have an expiry so the integration will have to check for expired sessions and request new token
                        $valid = true;
                    }
                } else {
                    $valid = true;
                }
                break;
            case 'key':
            case 'rest':
                $valid = isset($this->keys[$authTokenKey]);
                break;
            case 'basic':
                $valid = (!empty($this->keys['username']) && !empty($this->keys['password']));
                break;
            default:
                $valid = true;
                break;
        }

        return $valid;
    }

    /**
     * Get the URL required to obtain an oauth2 access token
     *
     * @return string
     */
    public function getAccessTokenUrl()
    {
        return '';
    }

    /**
     * Get the authentication/login URL for oauth2 access
     *
     * @return string
     */
    public function getAuthenticationUrl()
    {
        return '';
    }

    /**
     * Get request token for oauth1a authorization request
     *
     * @param array $settings
     *
     * @return mixed|string
     */
    public function getRequestToken($settings = array())
    {
        // Child classes can easily pass in custom settings this way
        $settings = array_merge(
            array('authorize_session' => true, 'append_callback' => false, 'ssl_verifypeer' => false),
            $settings
        );

        // init result to empty string
        $result = '';

        $url = $this->getRequestTokenUrl();
        if (!empty($url)) {
            $result = $this->makeRequest(
                $url,
                array(),
                'POST',
                $settings
            );
        }

        return $result;
    }

    /**
     * Url to post in order to get the request token if required; leave empty if not required
     *
     * @return string
     */
    public function getRequestTokenUrl()
    {
        return '';
    }

    /**
     * Generate a bearer token
     *
     * @param $inAuthorization
     *
     * @return string
     */
    public function getBearerToken($inAuthorization = false)
    {
        return '';
    }

    /**
     * Cleans the identifier for api calls
     *
     * @param mixed $identifier
     *
     * @return string
     */
    protected function cleanIdentifier($identifier)
    {
        if (is_array($identifier)) {
            foreach ($identifier as &$i) {
                $i = urlencode($i);
            }
        } else {
            $identifier = urlencode($identifier);
        }

        return $identifier;
    }

    /**ids=me
     * Gets the ID of the user for the integration
     *
     * @param       $identifier
     * @param array $socialCache
     *
     * @return mixed
     */
    public function getUserId($identifier, &$socialCache)
    {
        if (!empty($socialCache['id'])) {
            return $socialCache['id'];
        }

        return false;
    }

    /**
     * Get an array of public activity
     *
     * @param $identifier
     * @param $socialCache
     *
     * @return array
     */
    public function getPublicActivity($identifier, &$socialCache)
    {
        return array();
    }

    /**
     * Get an array of public data
     *
     * @param $identifier
     * @param $socialCache
     *
     * @return array
     */
    public function getUserData($identifier, &$socialCache)
    {
        return array();
    }

    /**
     * Generates current URL to set as referer for curl calls
     *
     * @return string
     */
    protected function getRefererUrl()
    {
        $request = $this->factory->getRequest();

        return $request->getRequestUri();
    }

    /**
     * Get a list of available fields from the connecting API
     *
     * @return array
     */
    public function getAvailableLeadFields($settings = array())
    {
        return array();
    }

    /**
     * Match lead data with integration fields
     *
     * @param $lead
     * @param $config
     *
     * @return array
     */
    public function populateLeadData($lead, $config = array())
    {
        if (!isset($config['leadFields'])) {
            $config = $this->mergeConfigToFeatureSettings($config);

            if (empty($config['leadFields'])) {

                return array();
            }
        }

        if ($lead instanceof Lead) {
            $fields = $lead->getFields(true);
        } else {
            $fields = $lead;
        }

        $leadFields      = $config['leadFields'];
        $availableFields = $this->getAvailableLeadFields($config);
        $unknown         = $this->factory->getTranslator()->trans('mautic.integration.form.lead.unknown');
        $matched         = array();

        foreach ($availableFields as $key => $field) {
            $integrationKey = $this->convertLeadFieldKey($key, $field);

            if (isset($leadFields[$key])) {
                $mauticKey = $leadFields[$key];
                if (isset($fields[$mauticKey]) && !empty($fields[$mauticKey]['value'])) {
                    $matched[$integrationKey] = $fields[$mauticKey]['value'];
                }
            }

            if (!empty($field['required']) && empty($matched[$integrationKey])) {
                $matched[$integrationKey] = $unknown;
            }
        }

        return $matched;
    }

    /**
     * Takes profile data from an integration and maps it to Mautic's lead fields
     *
     * @param       $data
     * @param array $config
     *
     * @return array
     */
    public function populateMauticLeadData($data, $config = array())
    {
        // Glean supported fields from what was returned by the integration
        $gleanedData = $data;

        if (!isset($config['leadFields'])) {
            $config = $this->mergeConfigToFeatureSettings($config);

            if (empty($config['leadFields'])) {

                return array();
            }
        }

        $leadFields      = $config['leadFields'];
        $availableFields = $this->getAvailableLeadFields($config);
        $matched         = array();

        foreach ($gleanedData as $key => $field) {
            if (isset($leadFields[$key]) && isset($gleanedData[$key])) {
                $matched[$leadFields[$key]] = $gleanedData[$key];
            }
        }


        return $matched;
    }

    /**
     * Create or update existing Mautic lead from the integration's profile data
     *
     * @param mixed       $data    Profile data from integration
     * @param bool|true   $persist Set to false to not persist lead to the database in this method
     * @param array|null  $socialCache
     * @param mixed||null $identifiers
     * @return Lead
     */
    public function getMauticLead($data, $persist = true, $socialCache = null, $identifiers = null)
    {
        if (is_object($data)) {
            // Convert to array in all levels
            $data = json_encode(json_decode($data), true);
        } elseif (is_string($data)) {
            // Assume JSON
            $data = json_decode($data, true);
        }

        // Match that data with mapped lead fields
        $matchedFields = $this->populateMauticLeadData($data);

        if (empty($matchedFields)) {

            return;
        }

        // Find unique identifier fields used by the integration
        /** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
        $leadModel           = $this->factory->getModel('lead');
        $uniqueLeadFields    = $this->factory->getModel('lead.field')->getUniqueIdentiferFields();
        $uniqueLeadFieldData = array();

        foreach ($matchedFields as $leadField => $value) {
            if (array_key_exists($leadField, $uniqueLeadFields) && !empty($value)) {
                $uniqueLeadFieldData[$leadField] = $value;
            }
        }

        // Default to new lead
        $lead            = new Lead();
        $lead->setNewlyCreated(true);

        if (count($uniqueLeadFieldData)) {

            $existingLeads = $this->factory->getEntityManager()->getRepository('MauticLeadBundle:Lead')
                ->getLeadsByUniqueFields($uniqueLeadFieldData);

            if (!empty($existingLeads)) {
                $lead = array_shift($existingLeads);
                // Update remaining leads
                if (count($existingLeads)) {
                    foreach ($existingLeads as $existingLead) {
                        $existingLead->setLastActive(new \DateTime());
                    }
                }
            }
        }

        $leadModel->setFieldValues($lead, $matchedFields, false, false);

        // Update the social cache
        $leadSocialCache = $lead->getSocialCache();
        if (!isset($leadSocialCache[$this->getName()])) {
            $leadSocialCache[$this->getName()] = array();
        }
        $leadSocialCache[$this->getName()] = array_merge($leadSocialCache[$this->getName()], $socialCache);

        // Check for activity while here
        if (null !== $identifiers && in_array('public_activity', $this->getSupportedFeatures())) {
            $this->getPublicActivity($identifiers, $leadSocialCache[$this->getName()]);
        }

        $lead->setSocialCache($leadSocialCache);

        $lead->setLastActive(new \DateTime());

        if ($persist) {
            // Only persist if instructed to do so as it could be that calling code needs to manipulate the lead prior to executing event listeners
            $leadModel->saveEntity($lead, false);
        }

        return $lead;
    }

    /**
     * Merges a config from integration_list with feature settings
     *
     * @param array $config
     *
     * @return array|mixed
     */
    public function mergeConfigToFeatureSettings($config = array())
    {
        $featureSettings = $this->settings->getFeatureSettings();

        if (isset($config['config'])
            && (empty($config['integration'])
                || (!empty($config['integration'])
                    && $config['integration'] == $this->getName()))
        ) {
            $featureSettings = array_merge($featureSettings, $config['config']);
        }

        return $featureSettings;
    }

    /**
     * Return key recognized by integration
     *
     * @param $key
     * @param $field
     *
     * @return mixed
     */
    public function convertLeadFieldKey($key, $field)
    {
        return $key;
    }

    /**
     * Sets whether fields should be sorted alphabetically or by the order the integration feeds
     */
    public function sortFieldsAlphabetically()
    {
        return true;
    }

    /**
     * Used to match local field name with remote field name
     *
     * @param string $field
     * @param string $subfield
     *
     * @return mixed
     */
    public function matchFieldName($field, $subfield = '')
    {
        if (!empty($field) && !empty($subfield)) {
            return $subfield.ucfirst($field);
        }

        return $field;
    }

    /**
     * Convert and assign the data to assignable fields
     *
     * @param mixed $data
     *
     * @return array
     */
    protected function matchUpData($data)
    {
        $info      = array();
        $available = $this->getAvailableLeadFields();

        foreach ($available as $field => $fieldDetails) {
            if (is_array($data)) {
                if (!isset($data[$field]) and !is_object($data)) {
                    $info[$field] = '';
                    continue;
                } else {
                    $values = $data[$field];
                }
            } else {
                if (!isset($data->$field)) {
                    $info[$field] = '';
                    continue;
                } else {
                    $values = $data->$field;
                }
            }

            switch ($fieldDetails['type']) {
                case 'string':
                case 'boolean':
                    $info[$field] = $values;
                    break;
                case 'object':
                    $values = $values;
                    foreach ($fieldDetails['fields'] as $f) {
                        if (isset($values->$f)) {
                            $fn = $this->matchFieldName($field, $f);

                            $info[$fn] = $values->$f;
                        }
                    }
                    break;
                case 'array_object':
                    $objects = array();
                    if (!empty($values)) {
                        foreach ($values as $k => $v) {
                            $v = $v;
                            if (isset($v->value)) {
                                $objects[] = $v->value;
                            }
                        }
                    }
                    $fn        = (isset($fieldDetails['fields'][0])) ? $this->matchFieldName(
                        $field,
                        $fieldDetails['fields'][0]
                    ) : $field;
                    $info[$fn] = implode('; ', $objects);

                    break;
            }
        }

        return $info;
    }

    /**
     * Get the path to the profile templates for this integration
     *
     * @return null
     */
    public function getSocialProfileTemplate()
    {
        return null;
    }

    /**
     * Checks to ensure an image still exists before caching
     *
     * @param string $url
     *
     * @return bool
     */
    public function checkImageExists($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt(
            $ch,
            CURLOPT_USERAGENT,
            'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13'
        );
        curl_exec($ch);
        $retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($retcode == 200);
    }

    /**
     * @param \Exception $e
     */
    public function logIntegrationError(\Exception $e)
    {
        $logger = $this->factory->getLogger();
        $logger->addError('INTEGRATION ERROR: '.$this->getName().' - '.$e->getMessage());
    }

    /**
     * Returns notes specific to sections of the integration form (if applicable)
     *
     * @param $section
     *
     * @return string
     */
    public function getFormNotes($section)
    {
        if ($section == 'leadfield_match') {
            return array('mautic.integration.form.field_match_notes', 'info');
        } else {
            return array('', 'info');
        }
    }

    /**
     * Allows appending extra data to the config.
     *
     * @param FormBuilder|Form $builder
     * @param array            $data
     * @param string           $formArea Section of form being built keys|features|integration
     *                                   keys can be used to store login/request related settings; keys are encrypted
     *                                   features can be used for configuring share buttons, etc
     *                                   integration is called when adding an integration to events like point triggers,
     *                                   campaigns actions, forms actions, etc
     */
    public function appendToForm(&$builder, $data, $formArea)
    {

    }

    /**
     * Returns settings for the integration form
     *
     * @return array
     */
    public function getFormSettings()
    {
        $type = $this->getAuthenticationType();
        switch ($type) {
            case 'oauth1a':
            case 'oauth2':
                $callback = $authorization = true;
                break;
            default:
                $callback = $authorization = false;
                break;
        }

        return array(
            'requires_callback'      => $callback,
            'requires_authorization' => $authorization
        );
    }

    /**
     * Get available fields for choices in the config UI
     *
     * @param array $settings
     *
     * @return array
     */
    public function getFormLeadFields($settings = array())
    {
        return ($this->isAuthorized()) ? $this->getAvailableLeadFields($settings) : array();
    }

    /**
     * returns template to render on popup window after trying to run OAuth
     *
     *
     * @return null|string
     */
    public function getPostAuthTemplate()
    {
        return null;
    }

    /**
     * @param       $eventName
     * @param array $keys
     *
     * @return array
     */
    protected function dispatchIntegrationKeyEvent($eventName, $keys = array())
    {
        /** @var PluginIntegrationKeyEvent $event */
        $event = $this->factory->getDispatcher()->dispatch(
            $eventName,
            new PluginIntegrationKeyEvent($this, $keys)
        );

        return $event->getKeys();
    }
}
