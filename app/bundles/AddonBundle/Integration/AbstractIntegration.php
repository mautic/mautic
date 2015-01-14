<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AddonBundle\Integration;

use Mautic\AddonBundle\Entity\Integration;
use Mautic\CoreBundle\Factory\MauticFactory;

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
     *
     * @return string
     */
    abstract public function getName();

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
        return 'MauticAddonBundle:Integration:form.html.php';
    }

    /**
     * Allows integration to set a custom theme folder
     *
     * @return string
     */
    public function getFormTheme()
    {
        return 'MauticAddonBundle:FormTheme\Integration';
    }

    /**
     * Set the social integration entity
     *
     * @param Integration $settings
     */
    public function setIntegrationSettings(Integration $settings)
    {
        $this->settings = $settings;
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
     * Merge api keys
     *
     * @param $mergeKeys
     * @param $withKeys
     * @param $return   Returns the key array rather than setting them
     *
     */
    public function mergeApiKeys($mergeKeys, $withKeys = array(), $return = false)
    {
        $settings = $this->settings;
        if (empty($withKeys)) {
            $withKeys = $this->getDecryptedApiKeys($settings);
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
            return $withKeys;
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
        $encrypted  = $this->encryptApiKeys($keys);
        $entity->setApiKeys($encrypted);
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
        if (!$entity) {
            $entity = $this->settings;
        }
        $keys = $entity->getApiKeys();
        return $this->decryptApiKeys($keys);
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
            $key = $helper->encrypt($key);
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
     * Generate the oauth login URL
     *
     * @return string
     */
    public function getOAuthLoginUrl()
    {
        $callback = $this->getOauthCallbackUrl();

        $keys = $this->getDecryptedApiKeys();
        $clientIdKey = $this->getClientIdKey();

        $clientId = $keys[$clientIdKey];
        $state = hash('sha1', uniqid(mt_rand()));
        $url = $this->getAuthenticationUrl()
            . '?client_id='.$clientId //placeholder to be replaced by whatever is the field
            . '&response_type=code'
            . '&redirect_uri=' . urlencode($callback)
            . '&state=' . $state; //set a state to protect against CSRF attacks
        $this->factory->getSession()->set($this->getName() . '_csrf_token', $state);

        return $url;
    }

    /**
     * Get the array key for clientId
     *
     * @return string
     */
    public function getClientIdKey()
    {
        return 'clientId';
    }

    /**
     * Get the array key for client secret
     *
     * @return string
     */
    public function getClientSecretKey()
    {
        return 'clientSecret';
    }

    /**
     * Get the array key for the auth token
     *
     * @return string
     */
    public function getAuthTokenKey()
    {
        return 'access_token';
    }

    /**
     * Retrieves and stores tokens returned from oAuthLogin
     *
     * @param string $clientId
     * @param string $clientSecret
     *
     * @return array
     */
    public function oAuthCallback($clientId = '', $clientSecret = '')
    {
        $request  = $this->factory->getRequest();
        $url      = $this->getAccessTokenUrl();
        $keys     = $this->getDecryptedApiKeys();
        $callback = $this->getOauthCallbackUrl();

        if (!empty($clientId)) {
            //callback from JS
            $keys['clientId']     = $clientId;
            $keys['clientSecret'] = $clientSecret;
        }

        if (!$url || !isset($keys['clientId']) || !isset($keys['clientSecret'])) {
            return array(false, $this->factory->getTranslator()->trans('mautic.integration.missingkeys'));
        }

        $query = 'client_id='.$keys['clientId']
                . '&client_secret='.$keys['clientSecret']
                . '&grant_type=authorization_code'
                . '&redirect_uri=' . urlencode($callback)
                . '&code='.$request->get('code');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $query);

        $data = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        //check to see if an entity exists
        $entity = $this->getIntegrationSettings();
        if ($entity == null) {
            $entity = new Integration();
            $entity->setName($this->getName());
        }

        if (empty($curlError)) {
            //parse the response
            $values = $this->parseCallbackResponse($data);

            if (is_array($values) && isset($values['access_token'])) {
                $keys['access_token'] = $values['access_token'];

                if (isset($values['refresh_token'])) {
                    $keys['refresh_token'] = $values['refresh_token'];
                }
                $error = false;
            } else {
                $error = $this->getErrorsFromResponse($values);
                if (empty($error)) {
                    $error = $this->factory->getTranslator()->trans("mautic.integration.error.genericerror", array(), "flashes");
                }
            }

            $encrypted = $this->encryptApiKeys($keys);
            $entity->setApiKeys($encrypted);
        } else {
            $error = $curlError;
        }

        //save the data
        $em = $this->factory->getEntityManager();
        $em->persist($entity);
        $em->flush();

        return array($entity, $error);
    }

    /**
     * Gets the URL for the built in oauth callback
     *
     * @return string
     */
    public function getOauthCallbackUrl()
    {
        return $this->factory->getRouter()->generate('mautic_integration_oauth_callback',
            array('integration' => $this->getName()),
            true //absolute
        );
    }


    /**
     * Extract the tokens returned by the oauth2 callback
     *
     * @param string $data
     *
     * @return mixed
     */
    protected function parseCallbackResponse($data)
    {
        return json_decode($data, true);
    }

    /**
     * Make a basic call using cURL to get the data
     *
     * @param string $url
     *
     * @return mixed
     */
    public function makeCall($url)
    {
        $referer  = $this->getRefererUrl();

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_REFERER, $referer);
        $data = @curl_exec($ch);

        curl_close($ch);

        return json_decode($data);
    }

    /**
     * Get a list of available fields from the connecting API
     *
     * @return array
     */
    public function getAvailableFields($silenceExceptions = true)
    {
        return array();
    }

    /**
     * Sets whether fields should be sorted alphabetically or by the order the integration feeds
     */
    public function sortFieldsAlphabetically()
    {
        return true;
    }

    /**
     * Get a list of keys required to make an API call.  Examples are key, clientId, clientSecret
     *
     * @return array
     */
    public function getRequiredKeyFields()
    {
        return array();
    }

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
     * Get the type of authentication required for this API.  Values can be none, key, oauth2 or callback
     * (will call $this->authenticationTypeCallback)
     *
     * @return string
     */
    public function getAuthenticationType()
    {
        return 'none';
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
    protected function getAuthenticationUrl()
    {
        return '';
    }

    /**
     * Get a string formatted error from an API response
     *
     * @param mixed $response
     *
     * @return string
     */
    public function getErrorsFromResponse($response)
    {
        if (is_array($response)) {
            return implode(' ', $response);
        }

        return $response;
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

    /**
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
        return 'http' . (($_SERVER['SERVER_PORT'] == 443) ? 's://' : '://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
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
            return $subfield . ucfirst($field);
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
        $info       = array();
        $available  = $this->getAvailableFields();

        foreach ($available as $field => $fieldDetails) {
            if (is_array($data)) {
                if (!isset($data[$field])) {
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
                    foreach ($fieldDetails['fields'] as $f) {
                        if (isset($values->$f)) {
                            $fn = $this->matchFieldName($field, $f);
                            $info[$fn] = $values->$f;
                        }
                    }
                    break;
                case 'array_object':
                    $objects = array();
                    foreach ($values as $k => $v) {
                        $objects[] = $v->value;
                    }
                    $info[$field] = implode('; ', $objects);
                    break;
            }
        }

        return $info;
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
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
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
        $logger->addError('INTEGRATION ERROR: ' . $this->getName() . ' - ' . $e->getMessage());
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
        return array('', 'info');
    }
}
