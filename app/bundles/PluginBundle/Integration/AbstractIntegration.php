<?php

namespace Mautic\PluginBundle\Integration;

use Doctrine\ORM\EntityManager;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\RequestOptions;
use Mautic\CoreBundle\Entity\CommonEntity;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\CoreBundle\Helper\CacheStorageHelper;
use Mautic\CoreBundle\Helper\EncryptionHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\LeadBundle\DataObject\LeadManipulator;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\DoNotContact as DoNotContactModel;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Entity\IntegrationEntity;
use Mautic\PluginBundle\Entity\IntegrationEntityRepository;
use Mautic\PluginBundle\Event\PluginIntegrationAuthCallbackUrlEvent;
use Mautic\PluginBundle\Event\PluginIntegrationFormBuildEvent;
use Mautic\PluginBundle\Event\PluginIntegrationFormDisplayEvent;
use Mautic\PluginBundle\Event\PluginIntegrationKeyEvent;
use Mautic\PluginBundle\Event\PluginIntegrationRequestEvent;
use Mautic\PluginBundle\Exception\ApiErrorException;
use Mautic\PluginBundle\Helper\Cleaner;
use Mautic\PluginBundle\Helper\oAuthHelper;
use Mautic\PluginBundle\Model\IntegrationEntityModel;
use Mautic\PluginBundle\PluginEvents;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @method pushLead(Lead $lead, array $config = [])
 * @method pushLeadToCampaign(Lead $lead, mixed $integrationCampaign, mixed $integrationMemberStatus)
 * @method getLeads(array $params, string $query, &$executed, array $result = [], $object = 'Lead')
 * @method getCompanies(array $params)
 *
 * @deprecated To be removed in Mautic 6.0. Please use the IntegrationsBundle instead, which is meant to be a drop-in replacement for AbstractIntegration.
 */
abstract class AbstractIntegration implements UnifiedIntegrationInterface
{
    public const FIELD_TYPE_STRING   = 'string';

    public const FIELD_TYPE_BOOL     = 'boolean';

    public const FIELD_TYPE_NUMBER   = 'number';

    public const FIELD_TYPE_DATETIME = 'datetime';

    public const FIELD_TYPE_DATE     = 'date';

    protected bool $coreIntegration = false;

    protected Integration $settings;

    protected array $keys = [];

    protected ?CacheStorageHelper $cache;

    protected ?SessionInterface $session;

    protected ?Request $request;

    /**
     * Used for notifications.
     *
     * @var \Doctrine\ORM\Tools\Pagination\Paginator<\Mautic\UserBundle\Entity\User>
     */
    protected ?\Doctrine\ORM\Tools\Pagination\Paginator $adminUsers = null;

    protected array $notifications              = [];

    protected ?string $lastIntegrationError     = null;

    protected array $mauticDuplicates           = [];

    protected array $salesforceIdMapping        = [];

    protected array $deleteIntegrationEntities  = [];

    protected array $persistIntegrationEntities = [];

    protected array $commandParameters         = [];

    public function __construct(
        protected EventDispatcherInterface $dispatcher,
        CacheStorageHelper $cacheStorageHelper,
        protected EntityManager $em,
        SessionInterface $session,
        RequestStack $requestStack,
        protected RouterInterface $router,
        protected TranslatorInterface $translator,
        protected LoggerInterface $logger,
        protected EncryptionHelper $encryptionHelper,
        protected LeadModel $leadModel,
        protected CompanyModel $companyModel,
        protected PathsHelper $pathsHelper,
        protected NotificationModel $notificationModel,
        protected FieldModel $fieldModel,
        protected IntegrationEntityModel $integrationEntityModel,
        protected DoNotContactModel $doNotContact
    ) {
        $this->cache                  = $cacheStorageHelper->getCache($this->getName());
        $this->session                = (!defined('IN_MAUTIC_CONSOLE')) ? $session : null;
        $this->request                = (!defined('IN_MAUTIC_CONSOLE')) ? $requestStack->getCurrentRequest() : null;
    }

    public function setCommandParameters(array $params): void
    {
        $this->commandParameters = $params;
    }

    /**
     * @return CacheStorageHelper
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * @return TranslatorInterface
     */
    public function getTranslator()
    {
        return $this->translator;
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
     * Determines if DNC records should be updated by date or by priority.
     */
    public function updateDncByDate(): bool
    {
        return false;
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
     */
    public function getDataPriority(): bool
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
     * Get a list of tooltips for the specified supported features.
     * This allows you to add detail / informational tooltips to your
     * supported feature checkbox group.
     *
     * Example:
     *  'cloud_storage' => 'mautic.integration.form.features.cloud_storage.tooltip'
     *
     * @return array<string, string>
     */
    public function getSupportedFeatureTooltips()
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
        return '@MauticPlugin/Integration/form.html.twig';
    }

    /**
     * Allows integration to set a custom theme folder.
     *
     * @return string
     */
    public function getFormTheme()
    {
        return '@MauticPlugin/FormTheme/Integration/layout.html.twig';
    }

    /**
     * Set the social integration entity.
     */
    public function setIntegrationSettings(Integration $settings): void
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
    public function persistIntegrationSettings(): void
    {
        $this->em->persist($this->settings);
        $this->em->flush();
    }

    /**
     * Merge api keys.
     *
     * @param bool|false $return Returns the key array rather than setting them
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

        // merge remaining new keys
        $withKeys = array_merge($withKeys, $mergeKeys);

        if ($return) {
            $this->keys = $this->dispatchIntegrationKeyEvent(
                PluginEvents::PLUGIN_ON_INTEGRATION_KEYS_MERGE,
                $withKeys
            );

            return $this->keys;
        } else {
            $this->encryptAndSetApiKeys($withKeys, $settings);

            // reset for events that depend on rebuilding auth objects
            $this->setIntegrationSettings($settings);
        }
    }

    /**
     * Encrypts and saves keys to the entity.
     */
    public function encryptAndSetApiKeys(array $keys, Integration $entity): void
    {
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
            $decrypted = $this->decryptApiKeys($keys, true);
            if (0 !== count($keys) && 0 === count($decrypted)) {
                $decrypted = $this->decryptApiKeys($keys);
                $this->encryptAndSetApiKeys($decrypted, $entity);
                $this->em->flush($entity);
            }
            $decryptedKeys[$serialized] = $this->dispatchIntegrationKeyEvent(
                PluginEvents::PLUGIN_ON_INTEGRATION_KEYS_DECRYPT,
                $decrypted
            );
        }

        return $decryptedKeys[$serialized];
    }

    /**
     * Encrypts API keys.
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
     * @param bool $mainDecryptOnly
     *
     * @return array
     */
    public function decryptApiKeys(array $keys, $mainDecryptOnly = false)
    {
        $decrypted = [];

        foreach ($keys as $name => $key) {
            $key = $this->encryptionHelper->decrypt($key, $mainDecryptOnly);
            if (false === $key) {
                continue;
            }
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
        return match ($this->getAuthenticationType()) {
            'oauth1a' => 'consumer_id',
            'oauth2'  => 'client_id',
            'key'     => 'key',
            default   => '',
        };
    }

    /**
     * Get the array key for client secret.
     *
     * @return string
     */
    public function getClientSecretKey()
    {
        return match ($this->getAuthenticationType()) {
            'oauth1a' => 'consumer_secret',
            'oauth2'  => 'client_secret',
            'basic'   => 'password',
            default   => '',
        };
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
        return match ($this->getAuthenticationType()) {
            'oauth2'  => 'access_token',
            'oauth1a' => 'oauth_token',
            default   => '',
        };
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
        return match ($this->getAuthenticationType()) {
            'oauth1a' => [
                'consumer_id'     => 'mautic.integration.keyfield.consumerid',
                'consumer_secret' => 'mautic.integration.keyfield.consumersecret',
            ],
            'oauth2' => [
                'client_id'     => 'mautic.integration.keyfield.clientid',
                'client_secret' => 'mautic.integration.keyfield.clientsecret',
            ],
            'key' => [
                'key' => 'mautic.integration.keyfield.api',
            ],
            'basic' => [
                'username' => 'mautic.integration.keyfield.username',
                'password' => 'mautic.integration.keyfield.password',
            ],
            default => [],
        };
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
        // remove control characters that will break json_decode from parsing
        $data = preg_replace('/[[:cntrl:]]/', '', $data);
        if (!$parsed = json_decode($data, true)) {
            parse_str($data, $parsed);
        }

        return $parsed;
    }

    /**
     * Generic error parser.
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
     * @param string $url
     * @param array  $parameters
     * @param string $method
     * @param array  $settings   Set $settings['return_raw'] to receive a ResponseInterface
     *
     * @return mixed|string|ResponseInterface
     */
    public function makeRequest($url, $parameters = [], $method = 'GET', $settings = [])
    {
        // If not authorizing the session itself, check isAuthorized which will refresh tokens if applicable
        if (empty($settings['authorize_session'])) {
            $this->isAuthorized();
        }

        $method   = strtoupper($method);
        $authType = (empty($settings['auth_type'])) ? $this->getAuthenticationType() : $settings['auth_type'];

        [$parameters, $headers] = $this->prepareRequest($url, $parameters, $method, $settings, $authType);

        if (empty($settings['ignore_event_dispatch'])) {
            $event = $this->dispatcher->dispatch(
                new PluginIntegrationRequestEvent($this, $url, $parameters, $headers, $method, $settings, $authType),
                PluginEvents::PLUGIN_ON_INTEGRATION_REQUEST
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

        if ('GET' == $method && !empty($parameters)) {
            $parameters = array_merge($settings['query'], $parameters);
            $query      = http_build_query($parameters);
            $url .= (!str_contains($url, '?')) ? '?'.$query : '&'.$query;
        } elseif (!empty($settings['query'])) {
            $query = http_build_query($settings['query']);
            $url .= (!str_contains($url, '?')) ? '?'.$query : '&'.$query;
        }

        if (isset($postAppend)) {
            $url .= $postAppend;
        }

        // Check for custom content-type header
        if (!empty($settings['content_type'])) {
            $settings['encoding_headers_set'] = true;
            $headers[]                        = "Content-Type: {$settings['content_type']}";
        }

        if ('GET' !== $method) {
            if (!empty($parameters)) {
                if ('oauth1a' == $authType) {
                    $parameters = http_build_query($parameters);
                }
                if (!empty($settings['encode_parameters'])) {
                    if ('json' == $settings['encode_parameters']) {
                        // encode the arguments as JSON
                        $parameters = json_encode($parameters);
                        if (empty($settings['encoding_headers_set'])) {
                            $headers[] = 'Content-Type: application/json';
                        }
                    }
                }
            } elseif (isset($settings['post_data'])) {
                $parameters = $settings['post_data'];
            }
        }

        /**
         * Set some cURL settings for backward compatibility
         * https://docs.guzzlephp.org/en/latest/faq.html?highlight=curl#how-can-i-add-custom-curl-options.
         */
        $options = [
            CURLOPT_HEADER         => 1,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_FOLLOWLOCATION => 0,
            CURLOPT_REFERER        => $this->getRefererUrl(),
            CURLOPT_USERAGENT      => $this->getUserAgent(),
        ];

        if (isset($settings['curl_options']) && is_array($settings['curl_options'])) {
            $options = $settings['curl_options'] + $options;
        }

        if (isset($settings['ssl_verifypeer'])) {
            $options[CURLOPT_SSL_VERIFYPEER] = $settings['ssl_verifypeer'];
        }

        $client = $this->makeHttpClient($options);

        $parseHeaders = (isset($settings['headers'])) ? array_merge($headers, $settings['headers']) : $headers;
        // HTTP library requires that headers are in key => value pairs
        $headers = [];
        if (is_array($parseHeaders)) {
            foreach ($parseHeaders as $key => $value) {
                // Ignore string keys which assume it is already parsed and avoids splitting up a value that includes colons (such as a date/time)
                if (!is_string($key) && str_contains($value, ':')) {
                    [$key, $value]     = explode(':', $value);
                    $key               = trim($key);
                    $value             = trim($value);
                }

                $headers[$key] = $value;
            }
        }

        try {
            $timeout = (isset($settings['request_timeout'])) ? (int) $settings['request_timeout'] : 10;
            switch ($method) {
                case 'GET':
                    $result = $client->get($url, [
                        RequestOptions::HEADERS => $headers,
                        RequestOptions::TIMEOUT => $timeout,
                    ]);
                    break;
                case 'POST':
                case 'PUT':
                case 'PATCH':
                    $payloadKey = is_string($parameters) ? RequestOptions::BODY : RequestOptions::FORM_PARAMS;
                    $result     = $client->request($method, $url, [
                        $payloadKey             => $parameters,
                        RequestOptions::HEADERS => $headers,
                        RequestOptions::TIMEOUT => $timeout,
                    ]);
                    break;
                case 'DELETE':
                    $result = $client->delete($url, [
                        RequestOptions::HEADERS => $headers,
                        RequestOptions::TIMEOUT => $timeout,
                    ]);
                    break;
            }
        } catch (\GuzzleHttp\Exception\RequestException $exception) {
            return [
                'error' => [
                    'message' => $exception->getResponse()->getBody()->getContents(),
                    'code'    => $exception->getCode(),
                ],
            ];
        }
        if (empty($settings['ignore_event_dispatch'])) {
            $event->setResponse($result);
            $this->dispatcher->dispatch(
                $event,
                PluginEvents::PLUGIN_ON_INTEGRATION_RESPONSE
            );
        }
        if (!empty($settings['return_raw'])) {
            return $result;
        } else {
            return $this->parseCallbackResponse($result->getBody(), !empty($settings['authorize_session']));
        }
    }

    /**
     * @param bool $persist
     */
    public function createIntegrationEntity(
        $integrationEntity,
        $integrationEntityId,
        $internalEntity,
        $internalEntityId,
        array $internal = null,
        $persist = true
    ): ?IntegrationEntity {
        $date = (defined('MAUTIC_DATE_MODIFIED_OVERRIDE')) ? \DateTime::createFromFormat('U', MAUTIC_DATE_MODIFIED_OVERRIDE)
            : new \DateTime();
        $entity = new IntegrationEntity();
        $entity->setDateAdded($date)
            ->setLastSyncDate($date)
            ->setIntegration($this->getName())
            ->setIntegrationEntity($integrationEntity)
            ->setIntegrationEntityId($integrationEntityId)
            ->setInternalEntity($internalEntity)
            ->setInternal($internal)
            ->setInternalEntityId($internalEntityId);

        if ($persist) {
            $this->em->getRepository(IntegrationEntity::class)->saveEntity($entity);
        }

        return $entity;
    }

    /**
     * @return IntegrationEntityRepository
     */
    public function getIntegrationEntityRepository()
    {
        return $this->em->getRepository(IntegrationEntity::class);
    }

    /**
     * Method to prepare the request parameters. Builds array of headers and parameters.
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
                                $useClientSecretKey => $this->keys[$clientSecretKey] ?? '',
                                'grant_type'        => $grantType,
                            ]
                        );

                        if (!empty($settings['refresh_token']) && !empty($this->keys[$settings['refresh_token']])) {
                            $parameters[$settings['refresh_token']] = $this->keys[$settings['refresh_token']];
                        }

                        if ('authorization_code' === $grantType) {
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
                            // "Content-Type: application/x-www-form-urlencoded;charset=UTF-8"
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

        if ('oauth2' == $authType) {
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
            UrlGeneratorInterface::ABSOLUTE_URL // absolute
        );

        /** @var PluginIntegrationAuthCallbackUrlEvent $event */
        $event = $this->dispatcher->dispatch(
            new PluginIntegrationAuthCallbackUrlEvent($this, $defaultUrl),
            PluginEvents::PLUGIN_ON_INTEGRATION_GET_AUTH_CALLBACK_URL
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
                        throw new ApiErrorException($this->translator->trans('mautic.integration.auth.invalid.state'));
                    }
                }

                if (!empty($settings['use_refresh_token'])) {
                    // Try refresh token
                    $refreshTokenKeys = $this->getRefreshTokenKeys();

                    if (!empty($refreshTokenKeys)) {
                        [$refreshTokenKey, $expiryKey] = $refreshTokenKeys;

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
     * @return bool|string false if no error; otherwise the error string
     */
    public function extractAuthKeys($data, $tokenOverride = null)
    {
        // check to see if an entity exists
        $entity = $this->getIntegrationSettings();
        if (null == $entity) {
            $entity = new Integration();
            $entity->setName($this->getName());
        }
        // Prepare the keys for extraction such as renaming, setting expiry, etc
        $data = $this->prepareResponseForExtraction($data);

        // parse the response
        $authTokenKey = $tokenOverride ?: $this->getAuthTokenKey();
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

        // save the data
        $this->em->persist($entity);
        $this->em->flush();

        $this->setIntegrationSettings($entity);

        return $error;
    }

    /**
     * Called in extractAuthKeys before key comparison begins to give opportunity to set expiry, rename keys, etc.
     *
     * @return mixed
     */
    public function prepareResponseForExtraction($data)
    {
        return $data;
    }

    /**
     * Checks to see if the integration is configured by checking that required keys are populated.
     */
    public function isConfigured(): bool
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
                    [$refreshTokenKey, $expiryKey] = $refreshTokenKeys;
                    if (!empty($this->keys[$refreshTokenKey]) && !empty($expiryKey) && isset($this->keys[$expiryKey])
                        && time() > $this->keys[$expiryKey]
                    ) {
                        // token has expired so try to refresh it
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
     * @return string
     */
    public function getBearerToken($inAuthorization = false)
    {
        return '';
    }

    /**
     * Get an array of public activity.
     *
     * @return array|void
     */
    public function getPublicActivity($identifier, &$socialCache)
    {
        return [];
    }

    /**
     * Get an array of public data.
     *
     * @return mixed[]|void
     */
    public function getUserData($identifier, &$socialCache)
    {
        return [];
    }

    /**
     * Generates current URL to set as referer for curl calls.
     */
    protected function getRefererUrl(): ?string
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
     * @param mixed[] $settings
     *
     * @return mixed[]
     */
    public function getAvailableLeadFields(array $settings = []): array
    {
        if (empty($settings['ignore_field_cache'])) {
            $cacheSuffix = $settings['cache_suffix'] ?? '';
            if ($fields = $this->cache->get('leadFields'.$cacheSuffix)) {
                return $fields;
            }
        }

        return [];
    }

    /**
     * @return array
     */
    public function cleanUpFields(Integration $entity, array $mauticLeadFields, array $mauticCompanyFields)
    {
        $featureSettings        = $entity->getFeatureSettings();
        $submittedFields        = $featureSettings['leadFields'] ?? [];
        $submittedCompanyFields = $featureSettings['companyFields'] ?? [];
        $submittedObjects       = $featureSettings['objects'] ?? [];
        $missingRequiredFields  = [];

        // add special case in order to prevent it from being removed
        $mauticLeadFields['mauticContactId']                   = '';
        $mauticLeadFields['mauticContactTimelineLink']         = '';
        $mauticLeadFields['mauticContactIsContactableByEmail'] = '';

        // make sure now non-existent aren't saved
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
        $cleanup = function (&$mappedFields, $integrationFields, $mauticFields, $fieldType) use (&$missingRequiredFields, &$featureSettings): void {
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

            // Check that the remaining fields have an updateKey set
            foreach ($mappedFields as $field => $mauticField) {
                if (!isset($featureSettings[$updateKey][$field])) {
                    // Assume it's mapped to Mautic
                    $featureSettings[$updateKey][$field] = 1;
                }
            }

            // Check if required fields are missing
            $required = $this->getRequiredFields($integrationFields, $fieldType);
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
            // BC compatibility If extends fields to objects - 0 === contacts
            if (isset($availableIntegrationFields[0])) {
                $leadFields = array_merge($leadFields, $availableIntegrationFields[0]);
            }

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
     * @param string $fieldType
     *
     * @return array
     */
    public function getRequiredFields(array $fields, $fieldType = '')
    {
        // use $fieldType to determine if email should be required. we use email as unique identifier for contacts only,
        // if any other fieldType use integrations own field types
        $requiredFields = [];
        foreach ($fields as $field => $details) {
            if ('leadFields' === $fieldType) {
                if ((is_array($details) && !empty($details['required'])) || 'email' === $field
                    || (isset($details['optionLabel'])
                        && 'email' == strtolower(
                            $details['optionLabel']
                        ))
                ) {
                    $requiredFields[$field] = $field;
                }
            } else {
                if (is_array($details) && !empty($details['required'])
                ) {
                    $requiredFields[$field] = $field;
                }
            }
        }

        return $requiredFields;
    }

    /**
     * Match lead data with integration fields.
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
            $fields = $lead->getProfileFields();
            $leadId = $lead->getId();
        } else {
            $fields = $lead;
            $leadId = $lead['id'];
        }

        $object          = $config['object'] ?? null;
        $leadFields      = $config['leadFields'];
        $availableFields = $this->getAvailableLeadFields($config);

        if ($object) {
            $availableFields = $availableFields[$config['object']];
        } else {
            $availableFields = $availableFields[0] ?? $availableFields;
        }

        $unknown = $this->translator->trans('mautic.integration.form.lead.unknown');
        $matched = [];

        foreach ($availableFields as $key => $field) {
            $integrationKey = $matchIntegrationKey = $this->convertLeadFieldKey($key, $field);
            if (!isset($config['leadFields'][$integrationKey])) {
                continue;
            }

            if (isset($leadFields[$integrationKey])) {
                if ('mauticContactTimelineLink' === $leadFields[$integrationKey]) {
                    $matched[$integrationKey] = $this->getContactTimelineLink($leadId);

                    continue;
                }
                if ('mauticContactIsContactableByEmail' === $leadFields[$integrationKey]) {
                    $matched[$integrationKey] = $this->getLeadDoNotContact($leadId);

                    continue;
                }
                if ('mauticContactId' === $leadFields[$integrationKey]) {
                    $matched[$integrationKey] = $lead->getId();
                    continue;
                }
                $mauticKey = $leadFields[$integrationKey];
                if (isset($fields[$mauticKey]) && '' !== $fields[$mauticKey] && null !== $fields[$mauticKey]) {
                    $matched[$matchIntegrationKey] = $this->cleanPushData(
                        $fields[$mauticKey],
                        $field['type'] ?? 'string'
                    );
                }
            }

            if (!empty($field['required']) && empty($matched[$matchIntegrationKey])) {
                $matched[$matchIntegrationKey] = $unknown;
            }
        }

        return $matched;
    }

    /**
     * Match Company data with integration fields.
     *
     * @return array
     */
    public function populateCompanyData($entity, $config = [])
    {
        if (!isset($config['companyFields'])) {
            $config = $this->mergeConfigToFeatureSettings($config);

            if (empty($config['companyFields'])) {
                return [];
            }
        }

        if ($entity instanceof Lead) {
            $fields = $entity->getPrimaryCompany();
        } else {
            $fields = $entity['primaryCompany'];
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
                    $matched[$integrationKey] = $this->cleanPushData($fields[$mauticKey], $field['type'] ?? 'string');
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
     * @param array       $config
     * @param string|null $object
     *
     * @return array
     */
    public function populateMauticLeadData($data, $config = [], $object = null)
    {
        // Glean supported fields from what was returned by the integration
        $gleanedData = $data;

        if (null == $object) {
            $object = 'lead';
        }
        if ('company' == $object) {
            if (!isset($config['companyFields'])) {
                $config = $this->mergeConfigToFeatureSettings($config);

                if (empty($config['companyFields'])) {
                    return [];
                }
            }

            $fields = $config['companyFields'];
        }
        if ('lead' == $object) {
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
            if (isset($fields[$key]) && isset($gleanedData[$key])
                && $this->translator->trans('mautic.integration.form.lead.unknown') !== $gleanedData[$key]
            ) {
                $matched[$fields[$key]] = $gleanedData[$key];
            }
        }

        return $matched;
    }

    /**
     * Create or update existing Mautic lead from the integration's profile data.
     *
     * @param mixed      $data        Profile data from integration
     * @param bool|true  $persist     Set to false to not persist lead to the database in this method
     * @param array|null $socialCache
     * @param mixed|null $identifiers
     *
     * @return Lead
     */
    public function getMauticLead($data, $persist = true, $socialCache = null, $identifiers = null)
    {
        if (is_object($data)) {
            // Convert to array in all levels
            $data = json_encode(json_decode($data, true));
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
        /** @var LeadModel $leadModel */
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
            $existingLeads = $this->em->getRepository(Lead::class)
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

        if ($persist && !empty($lead->getChanges(true))) {
            // Only persist if instructed to do so as it could be that calling code needs to manipulate the lead prior to executing event listeners
            try {
                $lead->setManipulator(new LeadManipulator(
                    'plugin',
                    $this->getName(),
                    null,
                    $this->getDisplayName()
                ));
                $leadModel->saveEntity($lead, false);
            } catch (\Exception $exception) {
                $this->logger->warning($exception->getMessage());

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
     */
    public function convertLeadFieldKey(string $key, $field): string
    {
        return $key;
    }

    /**
     * Sets whether fields should be sorted alphabetically or by the order the integration feeds.
     */
    public function sortFieldsAlphabetically(): bool
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
                        foreach ($values as $v) {
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
     */
    public function checkImageExists($url): bool
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

        return 200 == $retcode;
    }

    /**
     * @return NotificationModel
     */
    public function getNotificationModel()
    {
        return $this->notificationModel;
    }

    public function logIntegrationError(\Exception $e, Lead $contact = null): void
    {
        $logger = $this->logger;

        if ($e instanceof ApiErrorException) {
            if (null === $this->adminUsers) {
                $this->adminUsers = $this->em->getRepository(\Mautic\UserBundle\Entity\User::class)->getEntities(
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
                        'text-danger ri-error-warning-line-circle',
                        null,
                        $user
                    );
                }

                $this->notifications[$messageHash] = true;
            }
        }

        $logger->error('INTEGRATION ERROR: '.$this->getName().' - '.(('dev' == MAUTIC_ENV) ? (string) $e : $e->getMessage()));
    }

    /**
     * @return string|null
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
     * @return array<mixed>
     */
    public function getFormNotes($section)
    {
        if ('leadfield_match' == $section) {
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
    public function appendToForm(&$builder, $data, $formArea): void
    {
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array<mixed>         $options
     */
    public function modifyForm($builder, $options): void
    {
        $this->dispatcher->dispatch(
            new PluginIntegrationFormBuildEvent($this, $builder, $options),
            PluginEvents::PLUGIN_ON_INTEGRATION_FORM_BUILD
        );
    }

    /**
     * Returns settings for the integration form.
     *
     * @return array<string, mixed>
     */
    public function getFormSettings(): array
    {
        $type               = $this->getAuthenticationType();
        $enableDataPriority = $this->getDataPriority();
        switch ($type) {
            case 'oauth1a':
            case 'oauth2':
                $callback              = true;
                $requiresAuthorization = true;
                break;
            default:
                $callback              = false;
                $requiresAuthorization = false;
                break;
        }

        return [
            'requires_callback'      => $callback,
            'requires_authorization' => $requiresAuthorization,
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
            new PluginIntegrationFormDisplayEvent($this, $this->getFormSettings()),
            PluginEvents::PLUGIN_ON_INTEGRATION_FORM_DISPLAY
        );

        return $event->getSettings();
    }

    /**
     * Get available fields for choices in the config UI.
     *
     * @param mixed[] $settings
     *
     * @return mixed[]
     */
    public function getFormLeadFields(array $settings = [])
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
     * @return string|null
     */
    public function getPostAuthTemplate()
    {
        return null;
    }

    /**
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
     * @param array $keys
     *
     * @return array
     */
    protected function dispatchIntegrationKeyEvent($eventName, $keys = [])
    {
        /** @var PluginIntegrationKeyEvent $event */
        $event = $this->dispatcher->dispatch(
            new PluginIntegrationKeyEvent($this, $keys),
            $eventName
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
     * @param string $fieldType
     *
     * @return bool|float|string
     */
    public function cleanPushData($value, $fieldType = self::FIELD_TYPE_STRING)
    {
        return Cleaner::clean($value, $fieldType);
    }

    /**
     * @return \Monolog\Logger|LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param bool|\Exception $error
     *
     * @return int Number ignored due to being duplicates
     *
     * @throws ApiErrorException
     * @throws \Exception
     */
    protected function cleanupFromSync(&$leadsToSync = [], $error = false)
    {
        $duplicates = 0;
        if ($this->mauticDuplicates) {
            // Create integration entities for these to be ignored until they are updated
            foreach ($this->mauticDuplicates as $id => $dup) {
                $this->persistIntegrationEntities[] = $this->createIntegrationEntity('Lead', null, $dup, $id, [], false);
                ++$duplicates;
            }

            $this->mauticDuplicates = [];
        }

        $integrationEntityRepo = $this->getIntegrationEntityRepository();
        if (!empty($leadsToSync)) {
            // Let's only sync thos that have actual changes to prevent a loop
            $integrationEntityRepo->saveEntities($leadsToSync);
            $integrationEntityRepo->deleteEntity($leadsToSync);
            $leadsToSync = [];
        }

        // Persist updated entities if applicable
        if ($this->persistIntegrationEntities) {
            $integrationEntityRepo->saveEntities($this->persistIntegrationEntities);
            $this->persistIntegrationEntities = [];
        }

        // If there are any deleted, mark it as so to prevent them from being queried over and over or recreated
        if ($this->deleteIntegrationEntities) {
            $integrationEntityRepo->deleteEntities($this->deleteIntegrationEntities);
            $this->deleteIntegrationEntities = [];
        }
        $integrationEntityRepo->deleteEntities($this->deleteIntegrationEntities);

        if ($error) {
            if ($error instanceof \Exception) {
                throw $error;
            }

            throw new ApiErrorException($error);
        }

        return $duplicates;
    }

    /**
     * @param array $mapping array of [$mauticId => ['entity' => FormEntity, 'integration_entity_id' => $integrationId]]
     * @param array $params
     */
    protected function buildIntegrationEntities(array $mapping, $integrationEntity, $internalEntity, $params = [])
    {
        $integrationEntityRepo = $this->getIntegrationEntityRepository();
        $integrationEntities   = $integrationEntityRepo->getIntegrationEntities(
            $this->getName(),
            $integrationEntity,
            $internalEntity,
            array_keys($mapping)
        );

        // Find those that don't exist and create them
        $createThese = array_diff_key($mapping, $integrationEntities);

        foreach ($mapping as $internalEntityId => $entity) {
            if (is_array($entity)) {
                $integrationEntityId  = $entity['integration_entity_id'];
                $internalEntityObject = $entity['entity'];
            } else {
                $integrationEntityId  = $entity;
                $internalEntityObject = null;
            }

            if (isset($createThese[$internalEntityId])) {
                $entity = $this->createIntegrationEntity(
                    $integrationEntity,
                    $integrationEntityId,
                    $internalEntity,
                    $internalEntityId,
                    [],
                    false
                );
                $entity->setLastSyncDate($this->getLastSyncDate($internalEntityObject, $params, false));
                $integrationEntities[$internalEntityId] = $entity;
            } else {
                $integrationEntities[$internalEntityId]->setLastSyncDate($this->getLastSyncDate($internalEntityObject, $params, false));
            }
        }

        $integrationEntityRepo->saveEntities($integrationEntities);
        $integrationEntityRepo->detachEntities($integrationEntities);
    }

    /**
     * @param CommonEntity|null $entity
     * @param array             $params
     * @param bool              $ignoreEntityChanges
     *
     * @return bool|\DateTime|null
     */
    protected function getLastSyncDate($entity = null, $params = [], $ignoreEntityChanges = true)
    {
        $isNew = ($entity instanceof FormEntity) && $entity->isNew();
        if (!$isNew && !$ignoreEntityChanges && isset($params['start']) && $entity && method_exists($entity, 'getChanges')) {
            // Check to see if this contact was modified prior to the fetch so that the push catches it
            /** @var FormEntity $entity */
            $changes = $entity->getChanges(true);
            if (empty($changes) || isset($changes['dateModified'])) {
                $startSyncDate      = \DateTime::createFromFormat(\DateTime::ISO8601, $params['start']);
                $entityDateModified = $entity->getDateModified();

                if (isset($changes['dateModified'])) {
                    $originalDateModified = \DateTime::createFromFormat(\DateTime::ISO8601, $changes['dateModified'][0]);
                } elseif ($entityDateModified) {
                    $originalDateModified = $entityDateModified;
                } else {
                    $originalDateModified = $entity->getDateAdded();
                }

                if ($originalDateModified >= $startSyncDate) {
                    // Return null so that the push sync catches
                    return null;
                }
            }
        }

        return (defined('MAUTIC_DATE_MODIFIED_OVERRIDE')) ? \DateTime::createFromFormat('U', MAUTIC_DATE_MODIFIED_OVERRIDE)
            : new \DateTime();
    }

    /**
     * @return mixed
     */
    public function prepareFieldsForSync($fields, $keys, $object = null)
    {
        return $fields;
    }

    /**
     * Function used to format unformated fields coming from FieldsTypeTrait
     * (usually used in campaign actions).
     *
     * @return array
     */
    public function formatMatchedFields($fields)
    {
        $formattedFields = [];

        if (isset($fields['m_1'])) {
            $xfields = count($fields) / 3;
            for ($i = 1; $i < $xfields; ++$i) {
                if (isset($fields['i_'.$i]) && isset($fields['m_'.$i])) {
                    $formattedFields[$fields['i_'.$i]] = $fields['m_'.$i];
                } else {
                    continue;
                }
            }
        }

        if (!empty($formattedFields)) {
            $fields = $formattedFields;
        }

        return $fields;
    }

    /**
     * @param string $channel
     *
     * @return int
     */
    public function getLeadDoNotContact($leadId, $channel = 'email')
    {
        $isDoNotContact = 0;
        if ($lead = $this->leadModel->getEntity($leadId)) {
            $isContactableReason = $this->doNotContact->isContactable($lead, $channel);
            if (DoNotContact::IS_CONTACTABLE !== $isContactableReason) {
                $isDoNotContact = 1;
            }
        }

        return $isDoNotContact;
    }

    /**
     * Get pseudo fields from mautic, these are lead properties we want to map to integration fields.
     *
     * @return mixed
     */
    public function getCompoundMauticFields($lead)
    {
        if ($lead['internal_entity_id']) {
            $lead['mauticContactId']                   = $lead['internal_entity_id'];
            $lead['mauticContactTimelineLink']         = $this->getContactTimelineLink($lead['internal_entity_id']);
            $lead['mauticContactIsContactableByEmail'] = $this->getLeadDoNotContact($lead['internal_entity_id']);
        }

        return $lead;
    }

    /**
     * @return bool
     */
    public function isCompoundMauticField($fieldName)
    {
        $compoundFields = [
            'mauticContactTimelineLink' => 'mauticContactTimelineLink',
            'mauticContactId'           => 'mauticContactId',
        ];

        if (true === $this->updateDncByDate()) {
            $compoundFields['mauticContactIsContactableByEmail'] = 'mauticContactIsContactableByEmail';
        }

        return isset($compoundFields[$fieldName]);
    }

    /**
     * Update the record in each system taking the last modified record.
     *
     * @param string $channel
     *
     * @return int
     *
     * @throws ApiErrorException
     */
    public function getLeadDoNotContactByDate($channel, $records, $object, $lead, $integrationData, $params = [])
    {
        return $records;
    }

    /**
     * Because so many integrations extend this class and mautic.http.client is not in the
     * constructor at the time of writing, let's just create a new client here. In addition,
     * we add some custom cURL options.
     *
     * @param mixed[] $options
     */
    protected function makeHttpClient(array $options): Client
    {
        return new Client(['handler' => HandlerStack::create(new CurlHandler([
            'options' => $options,
        ]))]);
    }
}
