<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$container->loadFromExtension(
    'security',
    array(
        'providers'      => array(
            'user_provider' => array(
                'id' => 'mautic.user.provider'
            )
        ),
        'encoders'       => array(
            'Symfony\Component\Security\Core\User\User' => array(
                'algorithm'  => 'bcrypt',
                'iterations' => 12,
            ),
            'Mautic\UserBundle\Entity\User'             => array(
                'algorithm'  => 'bcrypt',
                'iterations' => 12,
            )
        ),
        'role_hierarchy' => array(
            'ROLE_ADMIN' => 'ROLE_USER',
        ),
        'firewalls'      => array(
            'install'              => array(
                'pattern'   => '^/installer',
                'anonymous' => true,
                'context'   => 'mautic',
                'security'  => false
            ),
            'dev'                  => array(
                'pattern'   => '^/(_(profiler|wdt)|css|images|js)/',
                'security'  => true,
                'anonymous' => true
            ),
            'login'                => array(
                'pattern'   => '^/s/login$',
                'anonymous' => true,
                'context'   => 'mautic'
            ),
            'sso_login'            => array(
                'pattern'            => '^/s/sso_login',
                'anonymous'          => true,
                'mautic_plugin_auth' => true,
                'context'            => 'mautic'
            ),
            'oauth2_token'         => array(
                'pattern'  => '^/oauth/v2/token',
                'security' => false
            ),
            'oauth2_area'          => array(
                'pattern'    => '^/oauth/v2/authorize',
                'form_login' => array(
                    'provider'   => 'user_provider',
                    'check_path' => '/oauth/v2/authorize_login_check',
                    'login_path' => '/oauth/v2/authorize_login'
                ),
                'anonymous'  => true
            ),
            'oauth1_request_token' => array(
                'pattern'  => '^/oauth/v1/request_token',
                'security' => false
            ),
            'oauth1_access_token'  => array(
                'pattern'  => '^/oauth/v1/access_token',
                'security' => false
            ),
            'oauth1_area'          => array(
                'pattern'    => '^/oauth/v1/authorize',
                'form_login' => array(
                    'provider'   => 'user_provider',
                    'check_path' => '/oauth/v1/authorize_login_check',
                    'login_path' => '/oauth/v1/authorize_login'
                ),
                'anonymous'  => true
            ),
            'api'                  => array(
                'pattern'            => '^/api',
                'fos_oauth'          => true,
                'bazinga_oauth'      => true,
                'mautic_plugin_auth' => true,
                'stateless'          => true
            ),
            'main'                 => array(
                'pattern'     => "^/s/",
                'simple_form' => array(
                    'authenticator'        => 'mautic.user.form_authenticator',
                    'csrf_token_generator' => 'security.csrf.token_manager',
                    'success_handler'      => 'mautic.security.authentication_handler',
                    'failure_handler'      => 'mautic.security.authentication_handler',
                    'login_path'           => '/s/login',
                    'check_path'           => '/s/login_check'
                ),
                'logout'      => array(
                    'handlers' => array(
                        'mautic.security.logout_handler'
                    ),
                    'path'     => '/s/logout',
                    'target'   => '/s/login'
                ),
                'remember_me' => array(
                    'secret'   => '%mautic.rememberme_key%',
                    'lifetime' => '%mautic.rememberme_lifetime%',
                    'path'     => '%mautic.rememberme_path%',
                    'domain'   => '%mautic.rememberme_domain%'
                ),
                'context'     => 'mautic'
            ),
            'public'               => array(
                'pattern'   => '^/',
                'anonymous' => true,
                'context'   => 'mautic'
            ),
        ),
        'access_control' => array(
            array('path' => '^/api', 'roles' => 'IS_AUTHENTICATED_FULLY')
        )
    )
);

$this->import('security_api.php');

// List config keys we do not want the user to change via the config UI
$restrictedConfigFields = array(
    'db_driver',
    'db_host',
    'db_table_prefix',
    'db_name',
    'db_user',
    'db_password',
    'db_path',
    'db_port',
    'secret_key'
);

// List config keys that are dev mode only
if ($container->getParameter('kernel.environment') == 'prod') {
    $restrictedConfigFields = array_merge($restrictedConfigFields, array('transifex_username', 'transifex_password'));
}

$container->setParameter('mautic.security.restrictedConfigFields', $restrictedConfigFields);

/**
 * Optional security parameters
 * mautic.security.disableUpdates = disables remote checks for updates
 * mautic.security.restrictedConfigFields.displayMode = accepts either remove or mask; mask will disable the input with a "Set by system" message
 */
$container->setParameter('mautic.security.disableUpdates', false);