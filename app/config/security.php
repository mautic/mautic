<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$container->loadFromExtension(
    'security',
    [
        'providers' => [
            'user_provider' => [
                'id' => 'mautic.user.provider',
            ],
        ],
        'encoders' => [
            'Symfony\Component\Security\Core\User\User' => [
                'algorithm'  => 'bcrypt',
                'iterations' => 12,
            ],
            'Mautic\UserBundle\Entity\User' => [
                'algorithm'  => 'bcrypt',
                'iterations' => 12,
            ],
        ],
        'role_hierarchy' => [
            'ROLE_ADMIN' => 'ROLE_USER',
        ],
        'firewalls' => [
            'install' => [
                'pattern'   => '^/installer',
                'anonymous' => true,
                'context'   => 'mautic',
                'security'  => false,
            ],
            'dev' => [
                'pattern'   => '^/(_(profiler|wdt)|css|images|js)/',
                'security'  => true,
                'anonymous' => true,
            ],
            'login' => [
                'pattern'   => '^/s/login$',
                'anonymous' => true,
                'context'   => 'mautic',
            ],
            'sso_login' => [
                'pattern'            => '^/s/sso_login',
                'anonymous'          => true,
                'mautic_plugin_auth' => true,
                'context'            => 'mautic',
            ],
            'oauth2_token' => [
                'pattern'  => '^/oauth/v2/token',
                'security' => false,
            ],
            'oauth2_area' => [
                'pattern'    => '^/oauth/v2/authorize',
                'form_login' => [
                    'provider'   => 'user_provider',
                    'check_path' => '/oauth/v2/authorize_login_check',
                    'login_path' => '/oauth/v2/authorize_login',
                ],
                'anonymous' => true,
            ],
            'oauth1_request_token' => [
                'pattern'  => '^/oauth/v1/request_token',
                'security' => false,
            ],
            'oauth1_access_token' => [
                'pattern'  => '^/oauth/v1/access_token',
                'security' => false,
            ],
            'oauth1_area' => [
                'pattern'    => '^/oauth/v1/authorize',
                'form_login' => [
                    'provider'   => 'user_provider',
                    'check_path' => '/oauth/v1/authorize_login_check',
                    'login_path' => '/oauth/v1/authorize_login',
                ],
                'anonymous' => true,
            ],
            'api' => [
                'pattern'            => '^/api',
                'fos_oauth'          => true,
                'bazinga_oauth'      => true,
                'mautic_plugin_auth' => true,
                'stateless'          => true,
                'http_basic'         => '%mautic.api_enable_basic_auth%',
            ],
            'main' => [
                'pattern'     => '^/s/',
                'simple_form' => [
                    'authenticator'        => 'mautic.user.form_authenticator',
                    'csrf_token_generator' => 'security.csrf.token_manager',
                    'success_handler'      => 'mautic.security.authentication_handler',
                    'failure_handler'      => 'mautic.security.authentication_handler',
                    'login_path'           => '/s/login',
                    'check_path'           => '/s/login_check',
                ],
                'logout' => [
                    'handlers' => [
                        'mautic.security.logout_handler',
                    ],
                    'path'   => '/s/logout',
                    'target' => '/s/login',
                ],
                'remember_me' => [
                    'secret'   => '%mautic.rememberme_key%',
                    'lifetime' => '%mautic.rememberme_lifetime%',
                    'path'     => '%mautic.rememberme_path%',
                    'domain'   => '%mautic.rememberme_domain%',
                ],
                'context' => 'mautic',
            ],
            'public' => [
                'pattern'   => '^/',
                'anonymous' => true,
                'context'   => 'mautic',
            ],
        ],
        'access_control' => [
            ['path' => '^/api', 'roles' => 'IS_AUTHENTICATED_FULLY'],
        ],
    ]
);

$this->import('security_api.php');

// List config keys we do not want the user to change via the config UI
$restrictedConfigFields = [
    'db_driver',
    'db_host',
    'db_table_prefix',
    'db_name',
    'db_user',
    'db_password',
    'db_path',
    'db_port',
    'secret_key',
];

// List config keys that are dev mode only
if ($container->getParameter('kernel.environment') == 'prod') {
    $restrictedConfigFields = array_merge($restrictedConfigFields, ['transifex_username', 'transifex_password']);
}

$container->setParameter('mautic.security.restrictedConfigFields', $restrictedConfigFields);

/*
 * Optional security parameters
 * mautic.security.disableUpdates = disables remote checks for updates
 * mautic.security.restrictedConfigFields.displayMode = accepts either remove or mask; mask will disable the input with a "Set by system" message
 */
$container->setParameter('mautic.security.disableUpdates', false);
