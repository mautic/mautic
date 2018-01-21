<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$firewalls = [
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
    'saml_login' => [
        'pattern'   => '^/s/saml/login$',
        'anonymous' => true,
        'context'   => 'mautic',
    ],
    'saml_discovery' => [
        'pattern'   => '^/saml/discovery$',
        'anonymous' => true,
        'context'   => 'mautic',
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
        'pattern'       => '^/s/',
        'light_saml_sp' => [
            'provider'        => 'user_provider',
            'success_handler' => 'mautic.security.authentication_handler',
            'failure_handler' => 'mautic.security.authentication_handler',
            'user_creator'    => 'mautic.security.saml.user_creator',
            'login_path'      => '/s/saml/login',
            'check_path'      => '/s/saml/login_check',
        ],
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
        'fos_oauth'     => true,
        'bazinga_oauth' => true,
        'context'       => 'mautic',
    ],
    'public' => [
        'pattern'   => '^/',
        'anonymous' => true,
        'context'   => 'mautic',
    ],
];

// If SAML is disabled, remove it from the firewall so that Symfony doesn't default to it
if (!$container->getParameter('mautic.saml_idp_metadata')) {
    unset(
        $firewalls['saml_login'],
        $firewalls['saml_discover'],
        $firewalls['main']['light_saml_sp']
    );
}

if (!$container->getParameter('mautic.api_enabled')) {
    unset(
        $firewalls['oauth2_token'],
        $firewalls['oauth2_area'],
        $firewalls['oauth1_request_token'],
        $firewalls['oauth1_access_token'],
        $firewalls['oauth1_area'],
        $firewalls['api'],
        $firewalls['main']['fos_oauth'],
        $firewalls['main']['bazinga_oauth']
    );
}

if (!$container->getParameter('mautic.famework.csrf_protection')) {
    unset($firewalls['main']['simple_form']['csrf_token_generator']);
}

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
        'firewalls'      => $firewalls,
        'access_control' => [
            ['path' => '^/api', 'roles' => 'IS_AUTHENTICATED_FULLY'],
        ],
    ]
);

$entityId = 'mautic';
if ($container->hasParameter('mautic.site_url')) {
    $parts = parse_url($container->getParameter('mautic.site_url'));

    if (!empty($parts['host'])) {
        $scheme   = (!empty($parts['scheme']) ? $parts['scheme'] : 'http');
        $entityId = $scheme.'://'.$parts['host'];
    }
}
$container->setParameter('mautic.saml_idp_entity_id', $entityId);
$container->loadFromExtension(
    'light_saml_symfony_bridge',
    [
        'own' => [
            'entity_id' => $entityId,
        ],
        'store' => [
            'id_state' => 'mautic.security.saml.id_store',
        ],
    ]
);

$container->loadFromExtension(
    'light_saml_sp',
    [
        'username_mapper' => [
            'email'     => '%mautic.saml_idp_email_attribute%',
            'username'  => '%mautic.saml_idp_username_attribute%',
            'firstname' => '%mautic.saml_idp_firstname_attribute%',
            'lastname'  => '%mautic.saml_idp_lastname_attribute%',
            'nameId'    => \Mautic\UserBundle\Security\User\UserMapper::NAME_ID,
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
$container->setParameter('mautic.security.restrictedConfigFields.displayMode', \Mautic\ConfigBundle\Form\Helper\RestrictionHelper::MODE_REMOVE);

/*
 * Optional security parameters
 * mautic.security.disableUpdates = disables remote checks for updates
 * mautic.security.restrictedConfigFields.displayMode = accepts either remove or mask; mask will disable the input with a "Set by system" message
 */
$container->setParameter('mautic.security.disableUpdates', false);
