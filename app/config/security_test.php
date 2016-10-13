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

$this->import('security_api.php');
