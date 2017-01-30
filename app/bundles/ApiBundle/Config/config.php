<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return [
    'routes' => [
        'public' => [
            // OAuth1.0a
            'bazinga_oauth_server_requesttoken' => [
                'path'       => '/oauth/v1/request_token',
                'controller' => 'bazinga.oauth.controller.server:requestTokenAction',
                'method'     => 'GET|POST',
            ],
            'bazinga_oauth_login_allow' => [
                'path'       => '/oauth/v1/authorize',
                'controller' => 'MauticApiBundle:oAuth1/Authorize:allow',
                'method'     => 'GET',
            ],
            'bazinga_oauth_server_authorize' => [
                'path'       => '/oauth/v1/authorize',
                'controller' => 'bazinga.oauth.controller.server:authorizeAction',
                'method'     => 'POST',
            ],
            'mautic_oauth1_server_auth_login' => [
                'path'       => '/oauth/v1/authorize_login',
                'controller' => 'MauticApiBundle:oAuth1/Security:login',
                'method'     => 'GET|POST',
            ],
            'mautic_oauth1_server_auth_login_check' => [
                'path'       => '/oauth/v1/authorize_login_check',
                'controller' => 'MauticApiBundle:oAuth1/Security:loginCheck',
                'method'     => 'GET|POST',
            ],
            'bazinga_oauth_server_accesstoken' => [
                'path'       => '/oauth/v1/access_token',
                'controller' => 'bazinga.oauth.controller.server:accessTokenAction',
                'method'     => 'GET|POST',
            ],

            // OAuth2
            'fos_oauth_server_token' => [
                'path'       => '/oauth/v2/token',
                'controller' => 'fos_oauth_server.controller.token:tokenAction',
                'method'     => 'GET|POST',
            ],
            'fos_oauth_server_authorize' => [
                'path'       => '/oauth/v2/authorize',
                'controller' => 'MauticApiBundle:oAuth2/Authorize:authorize',
                'method'     => 'GET|POST',
            ],
            'mautic_oauth2_server_auth_login' => [
                'path'       => '/oauth/v2/authorize_login',
                'controller' => 'MauticApiBundle:oAuth2/Security:login',
                'method'     => 'GET|POST',
            ],
            'mautic_oauth2_server_auth_login_check' => [
                'path'       => '/oauth/v2/authorize_login_check',
                'controller' => 'MauticApiBundle:oAuth2/Security:loginCheck',
                'method'     => 'GET|POST',
            ],
        ],
        'main' => [
            // Clients
            'mautic_client_index' => [
                'path'       => '/credentials/{page}',
                'controller' => 'MauticApiBundle:Client:index',
            ],
            'mautic_client_action' => [
                'path'       => '/credentials/{objectAction}/{objectId}',
                'controller' => 'MauticApiBundle:Client:execute',
            ],
        ],
    ],

    'menu' => [
        'admin' => [
            'items' => [
                'mautic.api.client.menu.index' => [
                    'route'     => 'mautic_client_index',
                    'iconClass' => 'fa-puzzle-piece',
                    'access'    => 'api:clients:view',
                    'checks'    => [
                        'parameters' => [
                            'api_enabled' => true,
                        ],
                    ],
                ],
            ],
        ],
    ],

    'services' => [
        'events' => [
            'mautic.api.subscriber' => [
                'class'     => 'Mautic\ApiBundle\EventListener\ApiSubscriber',
                'arguments' => [
                    'mautic.helper.ip_lookup',
                    'mautic.helper.core_parameters',
                    'mautic.core.model.auditlog',
                ],
            ],
            'mautic.api.configbundle.subscriber' => [
                'class' => 'Mautic\ApiBundle\EventListener\ConfigSubscriber',
            ],
            'mautic.api.search.subscriber' => [
                'class'     => 'Mautic\ApiBundle\EventListener\SearchSubscriber',
                'arguments' => [
                    'mautic.api.model.client',
                ],
            ],
        ],
        'forms' => [
            'mautic.form.type.apiclients' => [
                'class'     => 'Mautic\ApiBundle\Form\Type\ClientType',
                'arguments' => 'mautic.factory',
                'alias'     => 'client',
            ],
            'mautic.form.type.apiconfig' => [
                'class' => 'Mautic\ApiBundle\Form\Type\ConfigType',
                'alias' => 'apiconfig',
            ],
        ],
        'other' => [
            'mautic.api.oauth.event_listener' => [
                'class'     => 'Mautic\ApiBundle\EventListener\OAuthEventListener',
                'arguments' => [
                    'doctrine.orm.entity_manager',
                    'mautic.security',
                    'translator',
                ],
                'tags' => [
                    'kernel.event_listener',
                    'kernel.event_listener',
                ],
                'tagArguments' => [
                    [
                        'event'  => 'fos_oauth_server.pre_authorization_process',
                        'method' => 'onPreAuthorizationProcess',
                    ],
                    [
                        'event'  => 'fos_oauth_server.post_authorization_process',
                        'method' => 'onPostAuthorizationProcess',
                    ],
                ],
            ],
            'mautic.api.oauth1.nonce_provider' => [
                'class'     => 'Mautic\ApiBundle\Provider\NonceProvider',
                'arguments' => 'doctrine.orm.entity_manager',
            ],
            'bazinga.oauth.security.authentication.provider.class'    => 'Mautic\ApiBundle\Security\OAuth1\Authentication\Provider\OAuthProvider',
            'bazinga.oauth.security.authentication.listener.class'    => 'Mautic\ApiBundle\Security\OAuth1\Firewall\OAuthListener',
            'bazinga.oauth.event_listener.request.class'              => 'Mautic\ApiBundle\EventListener\OAuth1\OAuthRequestListener',
            'fos_oauth_server.security.authentication.listener.class' => 'Mautic\ApiBundle\Security\OAuth2\Firewall\OAuthListener',
            'jms_serializer.metadata.annotation_driver.class'         => 'Mautic\ApiBundle\Serializer\Driver\AnnotationDriver',
            'jms_serializer.metadata.php_driver.class'                => 'Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver',

            'mautic.validator.oauthcallback' => [
                'class' => 'Mautic\ApiBundle\Form\Validator\Constraints\OAuthCallbackValidator',
                'tag'   => 'validator.constraint_validator',
                'alias' => 'oauth_callback',
            ],
        ],
        'models' => [
            'mautic.api.model.client' => [
                'class'     => 'Mautic\ApiBundle\Model\ClientModel',
                'arguments' => [
                    'request_stack',
                ],
            ],
        ],
    ],

    'parameters' => [
        'api_enabled'                       => false,
        'api_enable_basic_auth'             => false,
        'api_oauth2_access_token_lifetime'  => 60,
        'api_oauth2_refresh_token_lifetime' => 14,
        'api_batch_max_limit'               => 200,
    ],
];
