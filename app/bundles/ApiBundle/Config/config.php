<?php

return [
    'routes' => [
        'public' => [
            // OAuth2
            'fos_oauth_server_token' => [
                'path'       => '/oauth/v2/token',
                'controller' => 'fos_oauth_server.controller.token:tokenAction',
                'method'     => 'GET|POST',
            ],
            'fos_oauth_server_authorize' => [
                'path'       => '/oauth/v2/authorize',
                'controller' => 'Mautic\ApiBundle\Controller\oAuth2\AuthorizeController::authorizeAction',
                'method'     => 'GET|POST',
            ],
            'mautic_oauth2_server_auth_login' => [
                'path'       => '/oauth/v2/authorize_login',
                'controller' => 'Mautic\ApiBundle\Controller\oAuth2\SecurityController::loginAction',
                'method'     => 'GET|POST',
            ],
            'mautic_oauth2_server_auth_login_check' => [
                'path'       => '/oauth/v2/authorize_login_check',
                'controller' => 'Mautic\ApiBundle\Controller\oAuth2\SecurityController::loginCheckAction',
                'method'     => 'GET|POST',
            ],
        ],
        'main' => [
            // Clients
            'mautic_client_index' => [
                'path'       => '/credentials/{page}',
                'controller' => 'Mautic\ApiBundle\Controller\ClientController::indexAction',
            ],
            'mautic_client_action' => [
                'path'       => '/credentials/{objectAction}/{objectId}',
                'controller' => 'Mautic\ApiBundle\Controller\ClientController::executeAction',
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
        'helpers' => [
            'mautic.api.helper.entity_result' => [
                'class' => \Mautic\ApiBundle\Helper\EntityResultHelper::class,
            ],
        ],
        'other' => [
            'mautic.api.oauth.event_listener' => [
                'class'     => \Mautic\ApiBundle\EventListener\PreAuthorizationEventListener::class,
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
            'fos_oauth_server.security.authentication.listener.class' => \Mautic\ApiBundle\Security\OAuth2\Firewall\OAuthListener::class,
            'mautic.validator.oauthcallback'                          => [
                'class' => \Mautic\ApiBundle\Form\Validator\Constraints\OAuthCallbackValidator::class,
                'tag'   => 'validator.constraint_validator',
            ],
        ],
    ],

    'parameters' => [
        'api_enabled'                       => false,
        'api_enable_basic_auth'             => false,
        'api_oauth2_access_token_lifetime'  => 60,
        'api_oauth2_refresh_token_lifetime' => 14,
        'api_batch_max_limit'               => 200,
        'api_rate_limiter_limit'            => 0,
        'api_rate_limiter_cache'            => [
            'adapter' => 'cache.adapter.filesystem',
        ],
    ],
];
