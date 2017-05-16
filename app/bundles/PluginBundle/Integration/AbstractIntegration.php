<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PluginBundle\Integration;

use Doctrine\ORM\EntityManager;
use Joomla\Http\HttpFactory;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Helper\CacheStorageHelper;
use Mautic\CoreBundle\Helper\EncryptionHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Entity\IntegrationEntity;
use Mautic\PluginBundle\Event\PluginIntegrationAuthCallbackUrlEvent;
use Mautic\PluginBundle\Event\PluginIntegrationFormBuildEvent;
use Mautic\PluginBundle\Event\PluginIntegrationFormDisplayEvent;
use Mautic\PluginBundle\Event\PluginIntegrationKeyEvent;
use Mautic\PluginBundle\Event\PluginIntegrationRequestEvent;
use Mautic\PluginBundle\Exception\ApiErrorException;
use Mautic\PluginBundle\Helper\oAuthHelper;
use Mautic\PluginBundle\PluginEvents;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Router;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class AbstractIntegration.
 *
 * @method pushLead(Lead $lead, array $config = [])
 * @method pushLeadToCampaign(Lead $lead, mixed $integrationCampaign, mixed $integrationMemberStatus)
 */
abstract class AbstractIntegration
{
    /**
     * @var bool
     */
    protected $coreIntegration = false;

    /**
     * @var MauticFactory
     */
    protected $factory;

    /**
     * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var Integration
     */
    protected $settings;

    /**
     * @var array Decrypted keys
     */
    protected $keys = [];

    /**
     * @var CacheStorageHelper
     */
    protected $cache;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * @var null|SessionInterface
     */
    protected $session;

    /**
     * @var null|Request
     */
    protected $request;

    /**
     * @var Router
     */
    protected $router;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var EncryptionHelper
     */
    protected $encryptionHelper;

    /**
     * @var LeadModel
     */
    protected $leadModel;

    /**
     * @var CompanyModel
     */
    protected $companyModel;

    /**
     * @var PathsHelper
     */
    protected $pathsHelper;

    /**
     * @var NotificationModel
     */
    protected $notificationModel;

    /**
     * @var FieldModel
     */
    protected $fieldModel;

    /**
     * Used for notifications.
     *
     * @var array|null
     */
    protected $adminUsers;

    /**
     * @var
     */
    protected $notifications = [];

    /**
     * @var
     */
    protected $lastIntegrationError;

    /**
     * AbstractIntegration constructor.
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     * @param MauticFactory $factory
     *
     * @deprecated 2.8.2 To be removed in 3.0. Use constructor arguments
     *             to set dependencies instead
     */
    public function setFactory(MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @param FieldModel $fieldModel
     */
    public function setFieldModel(FieldModel $fieldModel)
    {
        $this->fieldModel = $fieldModel;
    }

    /**
     * @param NotificationModel $notificationModel
     */
    public function setNotificationModel(NotificationModel $notificationModel)
    {
        $this->notificationModel = $notificationModel;
    }

    /**
     * @param PathsHelper $pathsHelper
     */
    public function setPathsHelper(PathsHelper $pathsHelper)
    {
        $this->pathsHelper = $pathsHelper;
    }

    /**
     * @param CompanyModel $companyModel
     */
    public function setCompanyModel(CompanyModel $companyModel)
    {
        $this->companyModel = $companyModel;
    }

    /**
     * @param LeadModel $leadModel
     */
    public function setLeadModel(LeadModel $leadModel)
    {
        $this->leadModel = $leadModel;
    }

    /**
     * @param EncryptionHelper $encryptionHelper
     */
    public function setEncryptionHelper(EncryptionHelper $encryptionHelper)
    {
        $this->encryptionHelper = $encryptionHelper;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param TranslatorInterface $translator
     */
    public function setTranslator(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param Router $router
     */
    public function setRouter(Router $router)
    {
        $this->router = $router;
    }

    /**
     * @param RequestStack $requestStack
     */
    public function setRequest(RequestStack $requestStack)
    {
        $this->request = !defined('IN_MAUTIC_CONSOLE') ? $requestStack->getCurrentRequest() : null;
    }

    /**
     * @param Session|null $session
     */
    public function setSession(Session $session = null)
    {
        $this->session = !defined('IN_MAUTIC_CONSOLE') ? $session : null;
    }

    /**
     * @param EntityManager $em
     */
    public function setEntityManager(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @param CacheStorageHelper $cacheStorageHelper
     */
    public function setCache(CacheStorageHelper $cacheStorageHelper)
    {
        $this->cache = $cacheStorageHelper->getCache($this->getName());
    }

    /**
     * @param EventDispatcherInterface $dispatcher
     */
    public function setDispatcher(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @return CacheStorageHelper
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * @return \Mautic\CoreBundle\Translation\Translator
     */
    public function getTranslator()
    {
        return $this->translator;
    }

    /**
     * Called on construct.
     *
     * @deprecated 2.8.2 To be removed in 3.0
     *             Setup your integration in the class constructor instead
     */
    public function init()
    {
    }

    /**
     * @return bool
     */
    public function isCoreIntegration()
    {
        return $this->coreIntegration;
    }

    /**
     * Determines what priority the integration should have against the other integrations.
     *
     * @return int
     */
    public function getPriority()
    {
        return 9999;
    }

    /**
     * Returns the name of the social integration that must match the name of the file
     * For example, IcontactIntegration would need Icontact here.
     *
     * @return string
     */
    abstract public function getName();

    /**
     * Name to display for the integration. e.g. iContact  Uses value of getName() by default.
     *
     * @return string
     */
    public function getDisplayName()
    {
        return $this->getName();
    }

    /**
     * Returns a description shown in the config form.
     *
     * @return string
     */
    public function getDescription()
    {
        return '';
    }

    /**
     * Get icon for Integration.
     *
     * @return string
     */
    public function getIcon()
    {
        $systemPath  = $this->pathsHelper->getSystemPath('root');
        $bundlePath  = $this->pathsHelper->getSystemPath('bundles');
        $pluginPath  = $this->pathsHelper->getSystemPath('plugins');
        $genericIcon = $bundlePath.'/PluginBundle/Assets/img/generic.png';

        $name   = $this->getName();
        $bundle = $this->settings->getPlugin()->getBundle();
        $icon   = $pluginPath.'/'.$bundle.'/Assets/img/'.strtolower($name).'.png';

        if (file_exists($systemPath.'/'.$icon)) {
            return $icon;
        }

        return $genericIcon;
    }

    /**
     * Get the type of authentication required for this API.  Values can be none, key, oauth2 or callback
     * (will call $this->authenticationTypeCallback).
     *
     * @return string
     */
    abstract public function getAuthenticationType();

    /**
     * Get if data priority is enabled in the integration or not default is false.
     *
     * @return string
     */
    public function getDataPriority()
    {
        return false;
    }

    /**
     * Get a list of supported features for this integration.
     *
     * Options are:
     *  cloud_storage - Asset remote storage
     *  public_profile - Lead social profile
     *  public_activity - Lead social activity
     *  share_button - Landing page share button
     *  sso_service - SSO using 3rd party service via sso_login and sso_login_check routes
     *  sso_form - SSO using submitted credentials through the login form
     *
     * @return array
     */
    public function getSupportedFeatures()
    {
        return [];
    }

    /**
     * Returns the field the integration needs in order to find the user.
     *
     * @return mixed
     */
    public function getIdentifierFields()
    {
        return [];
    }

    /**
     * Allows integration to set a custom form template.
     *
     * @return string
     */
    public function getFormTemplate()
    {
        return 'MauticPluginBundle:Integration:form.html.php';
    }

    /**
     * Allows integration to set a custom theme folder.
     *
     * @return string
     */
    public function getFormTheme()
    {
        return 'MauticPluginBundle:FormTheme\Integration';
    }

    /**
     * Set the social integration entity.
     *
     * @param Integration $settings
     */
    public function setIntegrationSettings(Integration $settings)
    {
        $this->settings = $settings;

        $this->keys = $this->getDecryptedApiKeys();
    }

    /**
     * Get the social integration entity.
     *
     * @return Integration
     */
    public function getIntegrationSettings()
    {
        return $this->settings;
    }

    /**
     * Persist settings to the database.
     */
    public function persistIntegrationSettings()
    {
        $this->em->persist($this->settings);
        $this->em->flush();
    }

    /**
     * Merge api keys.
     *
     * @param            $mergeKeys
     * @param            $withKeys
     * @param bool|false $return    Returns the key array rather than setting them
     *
     * @return void|array
     */
    public function mergeApiKeys($mergeKeys, $withKeys = [], $return = false)
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
     * Encrypts and saves keys to the entity.
     *
     * @param array       $keys
     * @param Integration $entity
     */
    public function encryptAndSetApiKeys(array $keys, Integration $entity)
    {
        /** @var PluginIntegrationKeyEvent $event */
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
     * Returns already decrypted keys.
     *
     * @return mixed
     */
    public function getKeys()
    {
        return $this->keys;
    }

    /**
     * Returns decrypted API keys.
     *
     * @param bool $entity
     *
     * @return array
     */
    public function getDecryptedApiKeys($entity = false)
    {
        static $decryptedKeys = [];

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
     * Encrypts API keys.
     *
     * @param array $keys
     *
     * @return array
     */
    public function encryptApiKeys(array $keys)
    {
        $encrypted = [];

        foreach ($keys as $name => $key) {
            $key              = $this->encryptionHelper->encrypt($key);
            $encrypted[$name] = $key;
        }

        return $encrypted;
    }

    /**
     * Decrypts API keys.
     *
     * @param array $keys
     *
     * @return array
     */
    public function decryptApiKeys(array $keys)
    {
        $decrypted = [];

        foreach ($keys as $name => $key) {
            $key              = $this->encryptionHelper->decrypt($key);
            $decrypted[$name] = $key;
        }

        return $decrypted;
    }

    /**
     * Get the array key for clientId.
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
     * Get the array key for client secret.
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
     * Array of keys to mask in the config form.
     *
     * @return array
     */
    public function getSecretKeys()
    {
        return [$this->getClientSecretKey()];
    }

    /**
     * Get the array key for the auth token.
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
     * Get the keys for the refresh token and expiry.
     *
     * @return array
     */
    public function getRefreshTokenKeys()
    {
        return [];
    }

    /**
     * Get a list of keys required to make an API call.  Examples are key, clientId, clientSecret.
     *
     * @return array
     */
    public function getRequiredKeyFields()
    {
        switch ($this->getAuthenticationType()) {
            case 'oauth1a':
                return [
                    'consumer_id'     => 'mautic.integration.keyfield.consumerid',
                    'consumer_secret' => 'mautic.integration.keyfield.consumersecret',
                ];
            case 'oauth2':
                return [
                    'client_id'     => 'mautic.integration.keyfield.clientid',
                    'client_secret' => 'mautic.integration.keyfield.clientsecret',
                ];
            case 'key':
                return [
                    'key' => 'mautic.integration.keyfield.api',
                ];
            case 'basic':
                return [
                    'username' => 'mautic.integration.keyfield.username',
                    'password' => 'mautic.integration.keyfield.password',
                ];
            default:
                return [];
        }
    }

    /**
     * Extract the tokens returned by the oauth callback.
     *
     * @param string $data
     * @param bool   $postAuthorization
     *
     * @return mixed
     */
    public function parseCallbackResponse($data, $postAuthorization = false)
    {
        if (!$parsed = json_decode($data, true)) {
            parse_str($data, $parsed);
        }

        return $parsed;
    }

    /**
     * Generic error parser.
     *
     * @param $response
     *
     * @return string
     */
    public function getErrorsFromResponse($response)
    {
        if (is_object($response)) {
            if (!empty($response->errors)) {
                $errors = [];
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
                $errors = [];
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
     * Make a basic call using cURL to get the data.
     *
     * @param        $url
     * @param array  $parameters
     * @param string $method
     * @param array  $settings
     *
     * @return mixed|string
     */
    public function makeRequest($url, $parameters = [], $method = 'GET', $settings = [])
    {
        $method   = strtoupper($method);
        $authType = (empty($settings['auth_type'])) ? $this->getAuthenticationType() : $settings['auth_type'];

        list($parameters, $headers) = $this->prepareRequest($url, $parameters, $method, $settings, $authType);

        if (empty($settings['ignore_event_dispatch'])) {
            $event = $this->dispatcher->dispatch(
                PluginEvents::PLUGIN_ON_INTEGRATION_REQUEST,
                new PluginIntegrationRequestEvent($this, $url, $parameters, $headers, $method, $settings, $authType)
            );

            $headers    = $event->getHeaders();
            $parameters = $event->getParameters();
        }

        if (!isset($settings['query'])) {
            $settings['query'] = [];
        }

        if (isset($parameters['append_to_query'])) {
            $settings['query'] = array_merge(
                $settings['query'],
                $parameters['append_to_query']
            );

            unset($parameters['append_to_query']);
        }

        if (isset($parameters['post_append_to_query'])) {
            $postAppend = $parameters['post_append_to_query'];
            unset($parameters['post_append_to_query']);
        }

        if (!$this->isConfigured()) {
            return [
                'error' => [
                    'message' => $this->translator->trans(
                        'mautic.integration.missingkeys'
                    ),
                ],
            ];
        }

        if ($method == 'GET' && !empty($parameters)) {
            $parameters = array_merge($settings['query'], $parameters);
            $query      = http_build_query($parameters);
            $url .= (strpos($url, '?') === false) ? '?'.$query : '&'.$query;
        } elseif (!empty($settings['query'])) {
            $query = http_build_query($settings['query']);
            $url .= (strpos($url, '?') === false) ? '?'.$query : '&'.$query;
        }

        if (isset($postAppend)) {
            $url .= $postAppend;
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

        $options = [
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_HEADER         => 1,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_FOLLOWLOCATION => 0,
            CURLOPT_REFERER        => $this->getRefererUrl(),
            CURLOPT_USERAGENT      => $this->getUserAgent(),
        ];

        if (isset($settings['curl_options'])) {
            $options = array_merge($options, $settings['curl_options']);
        }

        if (isset($settings['ssl_verifypeer'])) {
            $options[CURLOPT_SSL_VERIFYPEER] = $settings['ssl_verifypeer'];
        }

        $connector = HttpFactory::getHttp(
            [
                'transport.curl' => $options,
            ]
        );

        $parseHeaders = (isset($settings['headers'])) ? array_merge($headers, $settings['headers']) : $headers;
        // HTTP library requires that headers are in key => value pairs
        $headers = [];
        if (is_array($parseHeaders)) {
            foreach ($parseHeaders as $key => $value) {
                if (strpos($value, ':') !== false) {
                    list($key, $value) = explode(':', $value);
                    $key               = trim($key);
                    $value             = trim($value);
                }

                $headers[$key] = $value;
            }
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
            return ['error' => ['message' => $exception->getMessage(), 'code' => $exception->getCode()]];
        }
        if (empty($settings['ignore_event_dispatch'])) {
            $event->setResponse($result);
            $this->dispatcher->dispatch(
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

    /**
     * @param            $integrationEntity
     * @param            $integrationEntityId
     * @param            $internalEntity
     * @param            $internalEntityId
     * @param array|null $internal
     * @param bool       $persist
     */
    public function createIntegrationEntity($integrationEntity, $integrationEntityId, $internalEntity, $internalEntityId, array $internal = null, $persist = true)
    {
        $entity = new IntegrationEntity();
        $entity->setDateAdded(new \DateTime())
            ->setLastSyncDate(new \DateTime())
            ->setIntegration($this->getName())
            ->setIntegrationEntity($integrationEntity)
            ->setIntegrationEntityId($integrationEntityId)
            ->setInternalEntity($internalEntity)
            ->setInternal($internal)
            ->setInternalEntityId($internalEntityId);

        if ($persist) {
            $this->em->getRepository('MauticPluginBundle:IntegrationEntity')->saveEntity($entity);
        }

        return $entity;
    }

    /**
     * Method to prepare the request parameters. Builds array of headers and parameters.
     *
     * @param $url
     * @param $parameters
     * @param $method
     * @param $settings
     * @param $authType
     *
     * @return array
     */
    public function prepareRequest($url, $parameters, $method, $settings, $authType)
    {
        $clientIdKey     = $this->getClientIdKey();
        $clientSecretKey = $this->getClientSecretKey();
        $authTokenKey    = $this->getAuthTokenKey();
        $authToken       = '';
        if (isset($settings['override_auth_token'])) {
            $authToken = $settings['override_auth_token'];
        } elseif (isset($this->keys[$authTokenKey])) {
            $authToken = $this->keys[$authTokenKey];
        }

        // Override token parameter key if neede
        if (!empty($settings[$authTokenKey])) {
            $authTokenKey = $settings[$authTokenKey];
        }

        $headers = [];

        if (!empty($settings['authorize_session'])) {
            switch ($authType) {
                case 'oauth1a':
                    $requestTokenUrl = $this->getRequestTokenUrl();
                    if (!array_key_exists('append_callback', $settings) && !empty($requestTokenUrl)) {
                        $settings['append_callback'] = false;
                    }
                    $oauthHelper = new oAuthHelper($this, $this->request, $settings);
                    $headers     = $oauthHelper->getAuthorizationHeader($url, $parameters, $method);
                    break;
                case 'oauth2':
                    if ($bearerToken = $this->getBearerToken(true)) {
                        $headers = [
                            "Authorization: Basic {$bearerToken}",
                            'Content-Type: application/x-www-form-urlencoded;charset=UTF-8',
                        ];
                        $parameters['grant_type'] = 'client_credentials';
                    } else {
                        $defaultGrantType = (!empty($settings['refresh_token'])) ? 'refresh_token'
                            : 'authorization_code';
                        $grantType = (!isset($settings['grant_type'])) ? $defaultGrantType
                            : $settings['grant_type'];

                        $useClientIdKey     = (empty($settings[$clientIdKey])) ? $clientIdKey : $settings[$clientIdKey];
                        $useClientSecretKey = (empty($settings[$clientSecretKey])) ? $clientSecretKey
                            : $settings[$clientSecretKey];
                        $parameters = array_merge(
                            $parameters,
                            [
                                $useClientIdKey     => $this->keys[$clientIdKey],
                                $useClientSecretKey => isset($this->keys[$clientSecretKey]) ? $this->keys[$clientSecretKey] : '',
                                'grant_type'        => $grantType,
                            ]
                        );

                        if (!empty($settings['refresh_token']) && !empty($this->keys[$settings['refresh_token']])) {
                            $parameters[$settings['refresh_token']] = $this->keys[$settings['refresh_token']];
                        }

                        if ($grantType == 'authorization_code') {
                            $parameters['code'] = $this->request->get('code');
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
                    $headers = [
                        'Authorization' => 'Basic '.base64_encode($this->keys['username'].':'.$this->keys['password']),
                    ];
                    break;
                case 'oauth1a':
                    $oauthHelper = new oAuthHelper($this, $this->request, $settings);
                    $headers     = $oauthHelper->getAuthorizationHeader($url, $parameters, $method);
                    break;
                case 'oauth2':
                    if ($bearerToken = $this->getBearerToken()) {
                        $headers = [
                            "Authorization: Bearer {$bearerToken}",
                            //"Content-Type: application/x-www-form-urlencoded;charset=UTF-8"
                        ];
                    } else {
                        if (!empty($settings['append_auth_token'])) {
                            // Workaround because $settings cannot be manipulated here
                            $parameters['append_to_query'] = [
                                $authTokenKey => $authToken,
                            ];
                        } else {
                            $parameters[$authTokenKey] = $authToken;
                        }

                        $headers = [
                            "oauth-token: $authTokenKey",
                            "Authorization: OAuth {$authToken}",
                        ];
                    }
                    break;
                case 'key':
                    $parameters[$authTokenKey] = $authToken;
                    break;
            }
        }

        return [$parameters, $headers];
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

            if ($this->session) {
                $this->session->set($this->getName().'_csrf_token', $state);
            }

            return $url;
        } else {
            return $this->router->generate(
                'mautic_integration_auth_callback',
                ['integration' => $this->getName()]
            );
        }
    }

    /**
     * State variable to append to login url (usually used in oAuth flows).
     *
     * @return string
     */
    public function getAuthLoginState()
    {
        return hash('sha1', uniqid(mt_rand()));
    }

    /**
     * Get the scope for auth flows.
     *
     * @return string
     */
    public function getAuthScope()
    {
        return '';
    }

    /**
     * Gets the URL for the built in oauth callback.
     *
     * @return string
     */
    public function getAuthCallbackUrl()
    {
        $defaultUrl = $this->router->generate(
            'mautic_integration_auth_callback',
            ['integration' => $this->getName()],
            true //absolute
        );

        /** @var PluginIntegrationAuthCallbackUrlEvent $event */
        $event = $this->dispatcher->dispatch(
            PluginEvents::PLUGIN_ON_INTEGRATION_GET_AUTH_CALLBACK_URL,
            new PluginIntegrationAuthCallbackUrlEvent($this, $defaultUrl)
        );

        return $event->getCallbackUrl();
    }

    /**
     * Retrieves and stores tokens returned from oAuthLogin.
     *
     * @param array $settings
     * @param array $parameters
     *
     * @return bool|string false if no error; otherwise the error string
     *
     * @throws ApiErrorException if OAuth2 state does not match
     */
    public function authCallback($settings = [], $parameters = [])
    {
        $authType = $this->getAuthenticationType();

        switch ($authType) {
            case 'oauth2':
                if ($this->session) {
                    $state      = $this->session->get($this->getName().'_csrf_token', false);
                    $givenState = ($this->request->isXmlHttpRequest()) ? $this->request->request->get('state') : $this->request->get('state');

                    if ($state && $state !== $givenState) {
                        $this->session->remove($this->getName().'_csrf_token');
                        throw new ApiErrorException('mautic.integration.auth.invalid.state');
                    }
                }

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
                $settings['request_token'] = ($this->request) ? $this->request->get('oauth_token') : '';

                break;
        }

        $settings['authorize_session'] = true;

        $method = (!isset($settings['method'])) ? 'POST' : $settings['method'];
        $data   = $this->makeRequest($this->getAccessTokenUrl(), $parameters, $method, $settings);

        return $this->extractAuthKeys($data);
    }

    /**
     * Extacts the auth keys from response and saves entity.
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

            if ($this->session) {
                $this->session->set($this->getName().'_tokenResponse', $data);
            }

            $error = false;
        } elseif (is_array($data) && isset($data['access_token'])) {
            if ($this->session) {
                $this->session->set($this->getName().'_tokenResponse', $data);
            }
            $error = false;
        } else {
            $error = $this->getErrorsFromResponse($data);
            if (empty($error)) {
                $error = $this->translator->trans(
                    'mautic.integration.error.genericerror',
                    [],
                    'flashes'
                );
            }
        }

        //save the data
        $this->em->persist($entity);
        $this->em->flush();

        $this->setIntegrationSettings($entity);

        return $error;
    }

    /**
     * Called in extractAuthKeys before key comparison begins to give opportunity to set expiry, rename keys, etc.
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
     * Checks to see if the integration is configured by checking that required keys are populated.
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
     * Checks if an integration is authorized and/or authorizes the request.
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
                        $error = $this->authCallback(['refresh_token' => $refreshTokenKey]);
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
                $valid = isset($this->keys['api_key']);
                break;
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
     * Get the URL required to obtain an oauth2 access token.
     *
     * @return string
     */
    public function getAccessTokenUrl()
    {
        return '';
    }

    /**
     * Get the authentication/login URL for oauth2 access.
     *
     * @return string
     */
    public function getAuthenticationUrl()
    {
        return '';
    }

    /**
     * Get request token for oauth1a authorization request.
     *
     * @param array $settings
     *
     * @return mixed|string
     */
    public function getRequestToken($settings = [])
    {
        // Child classes can easily pass in custom settings this way
        $settings = array_merge(
            ['authorize_session' => true, 'append_callback' => false, 'ssl_verifypeer' => true],
            $settings
        );

        // init result to empty string
        $result = '';

        $url = $this->getRequestTokenUrl();
        if (!empty($url)) {
            $result = $this->makeRequest(
                $url,
                [],
                'POST',
                $settings
            );
        }

        return $result;
    }

    /**
     * Url to post in order to get the request token if required; leave empty if not required.
     *
     * @return string
     */
    public function getRequestTokenUrl()
    {
        return '';
    }

    /**
     * Generate a bearer token.
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
     * Gets the ID of the user for the integration.
     *
     * @param       $identifier
     * @param array $socialCache
     *
     * @deprecated  To be removed 2.0
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
     * Get an array of public activity.
     *
     * @param $identifier
     * @param $socialCache
     *
     * @return array
     */
    public function getPublicActivity($identifier, &$socialCache)
    {
        return [];
    }

    /**
     * Get an array of public data.
     *
     * @param $identifier
     * @param $socialCache
     *
     * @return array
     */
    public function getUserData($identifier, &$socialCache)
    {
        return [];
    }

    /**
     * Generates current URL to set as referer for curl calls.
     *
     * @return string
     */
    protected function getRefererUrl()
    {
        return ($this->request) ? $this->request->getRequestUri() : null;
    }

    /**
     * Generate a user agent string.
     *
     * @return string
     */
    protected function getUserAgent()
    {
        return ($this->request) ? $this->request->server->get('HTTP_USER_AGENT') : null;
    }

    /**
     * Get a list of available fields from the connecting API.
     *
     * @param array $settings
     *
     * @return array
     */
    public function getAvailableLeadFields($settings = [])
    {
        if (empty($settings['ignore_field_cache'])) {
            $cacheSuffix = (isset($settings['cache_suffix'])) ? $settings['cache_suffix'] : '';
            if ($fields = $this->cache->get('leadFields'.$cacheSuffix)) {
                return $fields;
            }
        }

        return [];
    }

    /**
     * @param Integration $entity
     * @param array       $mauticLeadFields
     * @param array       $mauticCompanyFields
     *
     * @return array
     */
    public function cleanUpFields(Integration $entity, array $mauticLeadFields, array $mauticCompanyFields)
    {
        $featureSettings        = $entity->getFeatureSettings();
        $submittedFields        = (isset($featureSettings['leadFields'])) ? $featureSettings['leadFields'] : [];
        $submittedCompanyFields = (isset($featureSettings['companyFields'])) ? $featureSettings['companyFields'] : [];
        $submittedObjects       = (isset($featureSettings['objects'])) ? $featureSettings['objects'] : [];
        $missingRequiredFields  = [];

        // add special case in order to prevent it from being removed
        $mauticLeadFields['mauticContactTimelineLink'] = '';

        //make sure now non-existent aren't saved
        $settings = [
            'ignore_field_cache' => false,
        ];
        $settings['feature_settings']['objects'] = $submittedObjects;
        $availableIntegrationFields              = $this->getAvailableLeadFields($settings);
        $leadFields                              = [];

        /**
         * @param $mappedFields
         * @param $integrationFields
         * @param $mauticFields
         * @param $fieldType
         */
        $cleanup = function (&$mappedFields, $integrationFields, $mauticFields, $fieldType) use (&$missingRequiredFields, &$featureSettings) {
            $updateKey    = ('companyFields' === $fieldType) ? 'update_mautic_company' : 'update_mautic';
            $removeFields = array_keys(array_diff_key($mappedFields, $integrationFields));

            // Find all the mapped fields that no longer exist in Mautic
            if ($nonExistentFields = array_diff($mappedFields, array_keys($mauticFields))) {
                // Remove those fields
                $removeFields = array_merge($removeFields, array_keys($nonExistentFields));
            }

            foreach ($removeFields as $field) {
                unset($mappedFields[$field]);

                if (isset($featureSettings[$updateKey])) {
                    unset($featureSettings[$updateKey][$field]);
                }
            }

            // Check if required fields are missing
            $required = $this->getRequiredFields($integrationFields);
            if (array_diff_key($required, $mappedFields)) {
                $missingRequiredFields[$fieldType] = true;
            }
        };

        if ($submittedObjects) {
            if (in_array('company', $submittedObjects)) {
                // special handling for company fields
                if (isset($availableIntegrationFields['company'])) {
                    $cleanup($submittedCompanyFields, $availableIntegrationFields['company'], $mauticCompanyFields, 'companyFields');
                    $featureSettings['companyFields'] = $submittedCompanyFields;
                    unset($availableIntegrationFields['company']);
                }
            }

            // Rest of the objects are merged and assumed to be leadFields
            foreach ($submittedObjects as $object) {
                if (isset($availableIntegrationFields[$object])) {
                    $leadFields = array_merge($leadFields, $availableIntegrationFields[$object]);
                }
            }
        } else {
            // Cleanup assuming there are no objects as keys
            $leadFields = $availableIntegrationFields;
        }

        if (!empty($leadFields)) {
            $cleanup($submittedFields, $leadFields, $mauticLeadFields, 'leadFields');
            $featureSettings['leadFields'] = $submittedFields;
        }

        $entity->setFeatureSettings($featureSettings);

        return $missingRequiredFields;
    }

    /**
     * @param array $fields
     *
     * @return array
     */
    public function getRequiredFields(array $fields)
    {
        $requiredFields = [];
        foreach ($fields as $field => $details) {
            if ((is_array($details) && !empty($details['required'])) || 'email' === $field
                || (isset($details['optionLabel'])
                    && strtolower(
                        $details['optionLabel']
                    ) == 'email')
            ) {
                $requiredFields[$field] = $field;
            }
        }

        return $requiredFields;
    }

    /**
     * Match lead data with integration fields.
     *
     * @param $lead
     * @param $config
     *
     * @return array
     */
    public function populateLeadData($lead, $config = [])
    {
        if (!isset($config['leadFields'])) {
            $config = $this->mergeConfigToFeatureSettings($config);

            if (empty($config['leadFields'])) {
                return [];
            }
        }

        if ($lead instanceof Lead) {
            $fields = $lead->getFields(true);
            $leadId = $lead->getId();
        } else {
            $fields = $lead;
            $leadId = $lead['id'];
        }

        $leadFields      = $config['leadFields'];
        $availableFields = $this->getAvailableLeadFields($config);
        if (isset($config['object'])) {
            $availableFields = $availableFields[$config['object']];
        }
        $unknown = $this->translator->trans('mautic.integration.form.lead.unknown');
        $matched = [];

        foreach ($availableFields as $key => $field) {
            $integrationKey = $matchIntegrationKey = $this->convertLeadFieldKey($key, $field);
            if (is_array($integrationKey)) {
                list($integrationKey, $matchIntegrationKey) = $integrationKey;
            }

            if (isset($leadFields[$integrationKey])) {
                if ($leadFields[$integrationKey] == 'mauticContactTimelineLink') {
                    $this->pushContactLink  = true;
                    $mauticContactLinkField = $integrationKey;
                    continue;
                }
                $mauticKey = $leadFields[$integrationKey];
                if (isset($fields[$mauticKey]) && !empty($fields[$mauticKey]['value'])) {
                    $matched[$matchIntegrationKey] = $this->cleanPushData($fields[$mauticKey]['value']);
                }
            }

            if (!empty($field['required']) && empty($matched[$matchIntegrationKey])) {
                $matched[$matchIntegrationKey] = $unknown;
            }
        }
        if ($this->pushContactLink) {
            $matched[$mauticContactLinkField] = $this->router->generate(
                'mautic_plugin_timeline_view',
                ['integration' => $this->getName(), 'leadId' => $leadId],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
        }

        return $matched;
    }

    /**
     * Match lead data with integration fields.
     *
     * @param $lead
     * @param $config
     *
     * @return array
     */
    public function populateCompanyData($lead, $config = [])
    {
        if (!isset($config['companyFields'])) {
            $config = $this->mergeConfigToFeatureSettings($config);

            if (empty($config['companyFields'])) {
                return [];
            }
        }

        if ($lead instanceof Lead) {
            $fields = $lead->getPrimaryCompany();
        } else {
            $fields = $lead['primaryCompany'];
        }

        $companyFields   = $config['companyFields'];
        $availableFields = $this->getAvailableLeadFields($config)['company'];
        $unknown         = $this->translator->trans('mautic.integration.form.lead.unknown');
        $matched         = [];

        foreach ($availableFields as $key => $field) {
            $integrationKey = $this->convertLeadFieldKey($key, $field);

            if (isset($companyFields[$key])) {
                $mauticKey = $companyFields[$key];
                if (isset($fields[$mauticKey]) && !empty($fields[$mauticKey])) {
                    $matched[$integrationKey] = $this->cleanPushData($fields[$mauticKey]);
                }
            }

            if (!empty($field['required']) && empty($matched[$integrationKey])) {
                $matched[$integrationKey] = $unknown;
            }
        }

        return $matched;
    }

    /**
     * Takes profile data from an integration and maps it to Mautic's lead fields.
     *
     * @param       $data
     * @param array $config
     * @param null  $object
     *
     * @return array
     */
    public function populateMauticLeadData($data, $config = [], $object = null)
    {
        // Glean supported fields from what was returned by the integration
        $gleanedData = $data;

        if ($object == null) {
            $object = 'lead';
        }
        if ($object == 'company') {
            if (!isset($config['companyFields'])) {
                $config = $this->mergeConfigToFeatureSettings($config);

                if (empty($config['companyFields'])) {
                    return [];
                }
            }

            $fields = $config['companyFields'];
        }
        if ($object == 'lead') {
            if (!isset($config['leadFields'])) {
                $config = $this->mergeConfigToFeatureSettings($config);

                if (empty($config['leadFields'])) {
                    return [];
                }
            }
            $fields = $config['leadFields'];
        }

        $matched = [];
        foreach ($gleanedData as $key => $field) {
            if (isset($fields[$key]) && isset($gleanedData[$key])) {
                $matched[$fields[$key]] = $gleanedData[$key];
            }
        }

        return $matched;
    }

    /**
     * Create or update existing Mautic lead from the integration's profile data.
     *
     * @param mixed       $data        Profile data from integration
     * @param bool|true   $persist     Set to false to not persist lead to the database in this method
     * @param array|null  $socialCache
     * @param mixed||null $identifiers
     *
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
        $leadModel           = $this->leadModel;
        $uniqueLeadFields    = $this->fieldModel->getUniqueIdentifierFields();
        $uniqueLeadFieldData = [];

        foreach ($matchedFields as $leadField => $value) {
            if (array_key_exists($leadField, $uniqueLeadFields) && !empty($value)) {
                $uniqueLeadFieldData[$leadField] = $value;
            }
        }

        // Default to new lead
        $lead = new Lead();
        $lead->setNewlyCreated(true);

        if (count($uniqueLeadFieldData)) {
            $existingLeads = $this->em->getRepository('MauticLeadBundle:Lead')
                ->getLeadsByUniqueFields($uniqueLeadFieldData);

            if (!empty($existingLeads)) {
                $lead = array_shift($existingLeads);
            }
        }

        $leadModel->setFieldValues($lead, $matchedFields, false, false);

        // Update the social cache
        $leadSocialCache = $lead->getSocialCache();
        if (!isset($leadSocialCache[$this->getName()])) {
            $leadSocialCache[$this->getName()] = [];
        }

        if (null !== $socialCache) {
            $leadSocialCache[$this->getName()] = array_merge($leadSocialCache[$this->getName()], $socialCache);
        }

        // Check for activity while here
        if (null !== $identifiers && in_array('public_activity', $this->getSupportedFeatures())) {
            $this->getPublicActivity($identifiers, $leadSocialCache[$this->getName()]);
        }

        $lead->setSocialCache($leadSocialCache);

        // Update the internal info integration object that has updated the record
        if (isset($data['internal'])) {
            $internalInfo                   = $lead->getInternal();
            $internalInfo[$this->getName()] = $data['internal'];
            $lead->setInternal($internalInfo);
        }

        if ($persist) {
            // Only persist if instructed to do so as it could be that calling code needs to manipulate the lead prior to executing event listeners
            try {
                $leadModel->saveEntity($lead, false);
            } catch (\Exception $exception) {
                $this->logger->addWarning($exception->getMessage());

                return;
            }
        }

        return $lead;
    }

    /**
     * Merges a config from integration_list with feature settings.
     *
     * @param array $config
     *
     * @return array|mixed
     */
    public function mergeConfigToFeatureSettings($config = [])
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
     * Return key recognized by integration.
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
     * Sets whether fields should be sorted alphabetically or by the order the integration feeds.
     */
    public function sortFieldsAlphabetically()
    {
        return true;
    }

    /**
     * Used to match local field name with remote field name.
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
     * Convert and assign the data to assignable fields.
     *
     * @param mixed $data
     *
     * @return array
     */
    protected function matchUpData($data)
    {
        $info      = [];
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
                    $objects = [];
                    if (!empty($values)) {
                        foreach ($values as $k => $v) {
                            $v = $v;
                            if (isset($v->value)) {
                                $objects[] = $v->value;
                            }
                        }
                    }
                    $fn = (isset($fieldDetails['fields'][0])) ? $this->matchFieldName(
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
     * Get the path to the profile templates for this integration.
     */
    public function getSocialProfileTemplate()
    {
        return null;
    }

    /**
     * Checks to ensure an image still exists before caching.
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

        return $retcode == 200;
    }

    /**
     * @return \Mautic\CoreBundle\Model\NotificationModel
     */
    public function getNotificationModel()
    {
        return $this->notificationModel;
    }

    /**
     * @param \Exception $e
     * @param Lead|null  $contact
     */
    public function logIntegrationError(\Exception $e, Lead $contact = null)
    {
        $logger = $this->logger;

        if ($e instanceof ApiErrorException) {
            if (null === $this->adminUsers) {
                $this->adminUsers = $this->em->getRepository('MauticUserBundle:User')->getEntities(
                    [
                        'filter' => [
                            'force' => [
                                [
                                    'column' => 'r.isAdmin',
                                    'expr'   => 'eq',
                                    'value'  => true,
                                ],
                            ],
                        ],
                    ]
                );
            }

            $errorMessage = $e->getMessage();
            $errorHeader  = $this->getTranslator()->trans(
                'mautic.integration.error',
                [
                    '%name%' => $this->getName(),
                ]
            );

            if ($contact || $contact = $e->getContact()) {
                // Append a link to the contact
                $contactId   = $contact->getId();
                $contactName = $contact->getPrimaryIdentifier();
            } elseif ($contactId = $e->getContactId()) {
                $contactName = $this->getTranslator()->trans('mautic.integration.error.generic_contact_name', ['%id%' => $contactId]);
            }

            $this->lastIntegrationError = $errorHeader.': '.$errorMessage;

            if ($contactId) {
                $contactLink = $this->router->generate(
                    'mautic_contact_action',
                    [
                        'objectAction' => 'view',
                        'objectId'     => $contactId,
                    ],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );
                $errorMessage .= ' <a href="'.$contactLink.'">'.$contactName.'</a>';
            }

            // Prevent a flood of the same messages
            $messageHash = md5($errorMessage);
            if (!array_key_exists($messageHash, $this->notifications)) {
                foreach ($this->adminUsers as $user) {
                    $this->getNotificationModel()->addNotification(
                        $errorMessage,
                        $this->getName(),
                        false,
                        $errorHeader,
                        'text-danger fa-exclamation-circle',
                        null,
                        $user
                    );
                }

                $this->notifications[$messageHash] = true;
            }
        }

        $logger->addError('INTEGRATION ERROR: '.$this->getName().' - '.(('dev' == MAUTIC_ENV) ? (string) $e : $e->getMessage()));
    }

    /**
     * @return mixed
     */
    public function getLastIntegrationError()
    {
        return $this->lastIntegrationError;
    }

    /**
     * @return $this
     */
    public function resetLastIntegrationError()
    {
        $this->lastIntegrationError = null;

        return $this;
    }

    /**
     * Returns notes specific to sections of the integration form (if applicable).
     *
     * @param $section
     *
     * @return string
     */
    public function getFormNotes($section)
    {
        if ($section == 'leadfield_match') {
            return ['mautic.integration.form.field_match_notes', 'info'];
        } else {
            return ['', 'info'];
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
     * @param FormBuilder $builder
     * @param array       $options
     */
    public function modifyForm($builder, $options)
    {
        $this->dispatcher->dispatch(
            PluginEvents::PLUGIN_ON_INTEGRATION_FORM_BUILD,
            new PluginIntegrationFormBuildEvent($this, $builder, $options)
        );
    }

    /**
     * Returns settings for the integration form.
     *
     * @return array
     */
    public function getFormSettings()
    {
        $type               = $this->getAuthenticationType();
        $enableDataPriority = $this->getDataPriority();
        switch ($type) {
            case 'oauth1a':
            case 'oauth2':
                $callback = $authorization = true;
                break;
            default:
                $callback = $authorization = false;
                break;
        }

        return [
            'requires_callback'      => $callback,
            'requires_authorization' => $authorization,
            'default_features'       => [],
            'enable_data_priority'   => $enableDataPriority,
        ];
    }

    /**
     * @return array
     */
    public function getFormDisplaySettings()
    {
        /** @var PluginIntegrationFormDisplayEvent $event */
        $event = $this->dispatcher->dispatch(
            PluginEvents::PLUGIN_ON_INTEGRATION_FORM_DISPLAY,
            new PluginIntegrationFormDisplayEvent($this, $this->getFormSettings())
        );

        return $event->getSettings();
    }

    /**
     * Get available fields for choices in the config UI.
     *
     * @param array $settings
     *
     * @return array
     */
    public function getFormLeadFields($settings = [])
    {
        if (isset($settings['feature_settings']['objects']['company'])) {
            unset($settings['feature_settings']['objects']['company']);
        }

        return ($this->isAuthorized()) ? $this->getAvailableLeadFields($settings) : [];
    }

    /**
     * Get available company fields for choices in the config UI.
     *
     * @param array $settings
     *
     * @return array
     */
    public function getFormCompanyFields($settings = [])
    {
        $settings['feature_settings']['objects']['company'] = 'company';

        return ($this->isAuthorized()) ? $this->getAvailableLeadFields($settings) : [];
    }

    /**
     * returns template to render on popup window after trying to run OAuth.
     *
     *
     * @return null|string
     */
    public function getPostAuthTemplate()
    {
        return null;
    }

    /**
     * @param $contactId
     *
     * @return string
     */
    public function getContactTimelineLink($contactId)
    {
        return $this->router->generate(
            'mautic_plugin_timeline_view',
            ['integration' => $this->getName(), 'leadId' => $contactId],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    /**
     * @param       $eventName
     * @param array $keys
     *
     * @return array
     */
    protected function dispatchIntegrationKeyEvent($eventName, $keys = [])
    {
        /** @var PluginIntegrationKeyEvent $event */
        $event = $this->dispatcher->dispatch(
            $eventName,
            new PluginIntegrationKeyEvent($this, $keys)
        );

        return $event->getKeys();
    }

    /**
     * Cleans the identifier for api calls.
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
     * @param $value
     */
    public function cleanPushData($value)
    {
        return strip_tags(html_entity_decode($value, ENT_QUOTES));
    }

    public function getLogger()
    {
        return $this->logger;
    }
}
