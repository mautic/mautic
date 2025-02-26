<?php

use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;

$firewalls = [
    'install' => [
        'pattern'  => '^/installer',
        'lazy'     => true,
        'context'  => 'mautic',
        'security' => false,
    ],
    'dev' => [
        'pattern'  => '^/(_(profiler|wdt)|css|images|js)/',
        'security' => true,
        'lazy'     => true,
    ],
    'login' => [
        'pattern' => '^/s/login$',
        'lazy'    => true,
        'context' => 'mautic',
    ],
    'sso_login' => [
        'pattern'            => '^/s/sso_login',
        'lazy'               => true,
        'mautic_plugin_auth' => true,
        'context'            => 'mautic',
    ],
    'saml_login' => [
        'pattern' => '^/s/saml/login$',
        'lazy'    => true,
        'context' => 'mautic',
    ],
    'saml_discovery' => [
        'pattern' => '^/saml/discovery$',
        'lazy'    => true,
        'context' => 'mautic',
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
        'lazy' => true,
    ],
    'api' => [
        'pattern'            => '^/api',
        'fos_oauth'          => true,
        'mautic_plugin_auth' => true,
        'stateless'          => true,
        'http_basic'         => true,
        'entry_point'        => 'fos_oauth_server.security.entry_point',
    ],
    'main' => [
        'pattern'       => '^/(s/|elfinder|efconnect)',
        'light_saml_sp' => [
            'provider'        => 'user_provider',
            'success_handler' => 'mautic.security.authentication_handler',
            'failure_handler' => 'mautic.security.authentication_handler',
            'user_creator'    => 'mautic.security.saml.user_creator',
            'username_mapper' => 'mautic.security.saml.username_mapper',

            // If saml is disabled, these still must contain a proper saml login URLs.
            // Otherwise, this prevents handling of the
            // \LightSaml\SpBundle\Security\Http\Authenticator\SamlServiceProviderAuthenticator::supports
            'login_path'      => '%env(MAUTIC_SAML_LOGIN_PATH)%', // '/s/saml/login',
            'check_path'      => '%env(MAUTIC_SAML_LOGIN_CHECK_PATH)%', // '/s/saml/login_check',
        ],
        'form_login' => [
            'enable_csrf'     => true,
            'success_handler' => 'mautic.security.authentication_handler',
            'failure_handler' => 'mautic.security.authentication_handler',
            'login_path'      => '/s/login',
            'check_path'      => '/s/login_check',
        ],
        'logout' => [
            'path'   => '/s/logout',
            'target' => '/s/login',
        ],
        'remember_me' => [
            'secret'   => '%mautic.rememberme_key%',
            'lifetime' => '%mautic.rememberme_lifetime%',
            'path'     => '%mautic.rememberme_path%',
            'domain'   => '%mautic.rememberme_domain%',
            'samesite' => 'lax',
        ],
        'entry_point' => Mautic\UserBundle\Security\EntryPoint\MainEntryPoint::class,
        'mautic_sso'  => [], // options are copied from `form_login` in \Mautic\UserBundle\DependencyInjection\Firewall\Factory\MauticSsoFactory
        'fos_oauth'   => true,
        'context'     => 'mautic',
    ],
    'public' => [
        'pattern' => '^/',
        'lazy'    => true,
        'context' => 'mautic',
    ],
];

if (!$container->getParameter('mautic.famework.csrf_protection')) {
    unset($firewalls['main']['simple_form']['csrf_token_generator']);
}

$container->loadFromExtension(
    'security',
    [
        'enable_authenticator_manager' => true,
        'providers'                    => [
            'user_provider' => [
                'id' => 'mautic.user.provider',
            ],
        ],
        'password_hashers' => [
            Symfony\Component\Security\Core\User\UserInterface::class => [
                'algorithm'  => 'bcrypt',
                'iterations' => 12,
            ],
            Mautic\UserBundle\Entity\User::class => [
                'algorithm'  => 'bcrypt',
                'iterations' => 12,
            ],
        ],
        'role_hierarchy' => [
            'ROLE_ADMIN' => 'ROLE_USER',
        ],
        'firewalls'      => $firewalls,
        'access_control' => [
            // First there should be URIs for login or definitely public ones.
            ['path' => '^/installer', 'roles' => AuthenticatedVoter::PUBLIC_ACCESS],
            ['path' => '^/(_(profiler|wdt)|css|images|js)/', 'roles' => AuthenticatedVoter::PUBLIC_ACCESS],
            ['path' => '^/s/login$', 'roles' => AuthenticatedVoter::PUBLIC_ACCESS],
            ['path' => '^/s/sso_login', 'roles' => AuthenticatedVoter::PUBLIC_ACCESS],
            ['path' => '^/s/saml/login$', 'roles' => AuthenticatedVoter::PUBLIC_ACCESS],
            ['path' => '^/saml/discovery$', 'roles' => AuthenticatedVoter::PUBLIC_ACCESS],
            ['path' => '^/oauth/v2/authorize', 'roles' => AuthenticatedVoter::PUBLIC_ACCESS],
            // Second should be URIs that are defined as non-public.
            ['path' => '^/api', 'roles' => AuthenticatedVoter::IS_AUTHENTICATED_FULLY],
            ['path' => '^/(s/|elfinder|efconnect)', 'roles' => AuthenticatedVoter::IS_AUTHENTICATED],
            // Last the URIs that are none of the above.
            ['path' => '^/', 'roles' => AuthenticatedVoter::PUBLIC_ACCESS],
        ],
    ]
);

$container->setParameter('mautic.saml_idp_entity_id', '%env(MAUTIC_SAML_ENTITY_ID)%');
$container->setParameter('mautic.saml_enabled', '%env(bool:MAUTIC_SAML_ENABLED)%');
$container->loadFromExtension(
    'light_saml_symfony_bridge',
    [
        'own' => [
            'entity_id' => '%mautic.saml_idp_entity_id%',
        ],
        'store' => [
            'id_state' => 'mautic.security.saml.id_store',
            'request'  => Mautic\UserBundle\Security\SAML\Store\Request\RequestStateStore::class,
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
if ('prod' == $container->getParameter('kernel.environment')) {
    $restrictedConfigFields = array_merge($restrictedConfigFields, ['transifex_username', 'transifex_password']);
}

$container->setParameter('mautic.security.restrictedConfigFields', $restrictedConfigFields);
$container->setParameter('mautic.security.restrictedConfigFields.displayMode', Mautic\ConfigBundle\Form\Helper\RestrictionHelper::MODE_REMOVE);

/*
 * Optional security parameters
 * mautic.security.disableUpdates = disables remote checks for updates
 * mautic.security.restrictedConfigFields.displayMode = accepts either remove or mask; mask will disable the input with a "Set by system" message
 */
$container->setParameter('mautic.security.disableUpdates', false);
