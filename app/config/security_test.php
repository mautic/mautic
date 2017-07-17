<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$container->loadFromExtension('security', [

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
        'dev' => [
            'pattern'   => '^/(_(profiler|wdt)|css|images|js)/',
            'security'  => true,
            'anonymous' => true,
        ],
        'login' => [
            'pattern'   => '^/login$',
            'anonymous' => true,
            'security'  => true,
            'context'   => 'mautic_test',
        ],
        'oauth_token' => [
            'pattern'  => '^/oauth/v2/token',
            'security' => false,
        ],
        'oauth_authorize' => [
            'pattern'    => '^/oauth/v2/auth',
            'form_login' => [
                'provider'   => 'user_provider',
                'check_path' => '/oauth/v2/auth_login_check',
                'login_path' => '/oauth/v2/auth_login',
            ],
            'anonymous' => true,
        ],
        'api' => [
            'pattern'   => '^/api',
            'fos_oauth' => true,
            'stateless' => true,
        ],
        'test_firewall' => [
            'pattern'    => '^/',
            'http_basic' => [],
            'form_login' => [
                'csrf_token_generator' => 'security.csrf.token_manager',
            ],
            'context' => 'mautic_test',
        ],
        'main' => [
            'pattern'    => '^/',
            'form_login' => [
                'csrf_token_generator' => 'security.csrf.token_manager',
            ],
            'logout'      => [],
            'remember_me' => [
                'key'      => '%mautic.rememberme_key%',
                'lifetime' => '%mautic.rememberme_lifetime%',
                'path'     => '%mautic.rememberme_path%',
                'domain'   => '%mautic.rememberme_domain%',
            ],
            'context' => 'mautic_test',
        ],
    ],
    'access_control' => [
        ['path' => '^/api', 'roles' => 'IS_AUTHENTICATED_FULLY'],
    ],
]);

$container->setParameter('mautic.security.disableUpdates', false);

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
