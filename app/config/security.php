<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
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
        'public' => array(
            'pattern'   => '^/p/',
            'anonymous' => true,
            'context'   => 'mautic'
        ),
        'login' => array(
            'pattern'   => '^/login$',
            'anonymous' => true,
            'context'   => 'mautic'
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
            'anonymous'  => true
        ),
        'api' => array(
            'pattern'   => '^/api',
            'fos_oauth' => true,
            'stateless' => true
        ),
        'main' => array(
            'pattern' => "^/",
            'form_login' => array(
                'csrf_provider' => 'form.csrf_provider'
            ),
            'logout' => array(),
            'remember_me' => array(
                'key'      => '%mautic.rememberme_key%',
                'lifetime' => '%mautic.rememberme_lifetime%',
                'path'     => '%mautic.rememberme_path%',
                'domain'   => '%mautic.rememberme_domain%'
            ),
            'context'   => 'mautic'
        ),
    ),
    'access_control' => array(
        array('path' => '^/api', 'roles' => 'IS_AUTHENTICATED_FULLY')
    )
));

$container->loadFromExtension('fos_oauth_server', array(
    'db_driver'           => 'orm',
    'client_class'        => 'Mautic\ApiBundle\Entity\Client',
    'access_token_class'  => 'Mautic\ApiBundle\Entity\AccessToken',
    'refresh_token_class' => 'Mautic\ApiBundle\Entity\RefreshToken',
    'auth_code_class'     => 'Mautic\ApiBundle\Entity\AuthCode',
    'service'             => array(
        'user_provider' => 'mautic.user.provider',
        'options'       => array(
            'supported_scopes' => 'user'
        )
    ),
    'template'            => array(
        'engine' => 'php'
    )
));
