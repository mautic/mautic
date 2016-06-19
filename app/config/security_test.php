<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$container->loadFromExtension('security', array(

    'providers' => array(
        'user_provider' => array(
            'id' => 'mautic.user.provider'
        )
    ),
    'encoders' => array(
        'Symfony\Component\Security\Core\User\User' => array(
            'algorithm'         => 'bcrypt',
            'iterations'        => 12,
        ),
        'Mautic\UserBundle\Entity\User' => array(
            'algorithm'         => 'bcrypt',
            'iterations'        => 12,
        )
    ),
    'role_hierarchy' => array(
        'ROLE_ADMIN' => 'ROLE_USER',
    ),
    'firewalls' => array(
        'dev' => array(
            'pattern' => '^/(_(profiler|wdt)|css|images|js)/',
            'security' => true,
            'anonymous' => true
        ),
        'login' => array(
            'pattern'   => '^/login$',
            'anonymous' => true,
            'security'  => true,
            'context'   => 'mautic_test'
        ),
        'oauth_token' => array(
            'pattern'  => '^/oauth/v2/token',
            'security' => false
        ),
        'oauth_authorize' => array(
            'pattern'    => '^/oauth/v2/auth',
            'form_login' => array(
                'provider'   => 'user_provider',
                'check_path' => '/oauth/v2/auth_login_check',
                'login_path' => '/oauth/v2/auth_login'
            ),
            'anonymous'  => true,
        ),
        'api' => array(
            'pattern'   => '^/api',
            'fos_oauth' => true,
            'stateless' => true,
        ),
        'test_firewall' => array(
            'pattern'    => "^/",
            'http_basic' => array(),
            'form_login' => array(
                'csrf_token_generator' => 'security.csrf.token_manager'
            ),
            'context'    => 'mautic_test',
        ),
        'main' => array(
            'pattern' => "^/",
            'form_login' => array(
                'csrf_token_generator' => 'security.csrf.token_manager'
            ),
            'logout' => array(),
            'remember_me' => array(
                'key'      => '%mautic.rememberme_key%',
                'lifetime' => '%mautic.rememberme_lifetime%',
                'path'     => '%mautic.rememberme_path%',
                'domain'   => '%mautic.rememberme_domain%'
            ),
            'context' => 'mautic_test'
        ),
    ),
    'access_control' => array(
        array('path' => '^/api', 'roles' => 'IS_AUTHENTICATED_FULLY')
    )
));

$container->setParameter('mautic.security.disableUpdates', false);

$this->import('security_api.php');