<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return array(
    'menu'     => array(
        'admin' => array(
            'mautic.user.users' => array(
                'access'    => 'user:users:view',
                'route'     => 'mautic_user_index',
                'iconClass' => 'fa-users',
            ),
            'mautic.user.roles' => array(
                'access'    => 'user:roles:view',
                'route'     => 'mautic_role_index',
                'iconClass' => 'fa-lock'
            )
        )
    ),

    'routes'   => array(
        'main' => array(
            'login'                     => array(
                'path'       => '/login',
                'controller' => 'MauticUserBundle:Security:login',
            ),
            'mautic_user_logincheck'    => array(
                'path' => '/login_check',
                'controller' => 'MauticUserBundle:Security:loginCheck',
            ),
            'mautic_user_logout'        => array(
                'path' => '/logout'
            ),
            'mautic_sso_login'    => array(
                'path'       => '/sso_login/{integration}',
                'controller' => 'MauticUserBundle:Security:ssoLogin'
            ),
            'mautic_sso_login_check'    => array(
                'path'       => '/sso_login_check/{integration}',
                'controller' => 'MauticUserBundle:Security:ssoLoginCheck',
            ),
            'mautic_user_index'         => array(
                'path'       => '/users/{page}',
                'controller' => 'MauticUserBundle:User:index'
            ),
            'mautic_user_action'        => array(
                'path'       => '/users/{objectAction}/{objectId}',
                'controller' => 'MauticUserBundle:User:execute'
            ),
            'mautic_role_index'         => array(
                'path'       => '/roles/{page}',
                'controller' => 'MauticUserBundle:Role:index'
            ),
            'mautic_role_action'        => array(
                'path'       => '/roles/{objectAction}/{objectId}',
                'controller' => 'MauticUserBundle:Role:execute'
            ),
            'mautic_user_account'       => array(
                'path'       => '/account',
                'controller' => 'MauticUserBundle:Profile:index'
            ),
        ),
        'api'  => array(
            'mautic_api_getusers'        => array(
                'path'       => '/users',
                'controller' => 'MauticUserBundle:Api\UserApi:getEntities',
            ),
            'mautic_api_getuser'         => array(
                'path'         => '/users/{id}',
                'controller'   => 'MauticUserBundle:Api\UserApi:getEntity'
            ),
            'mautic_api_getself'         => array(
                'path'       => '/users/self',
                'controller' => 'MauticUserBundle:Api\UserApi:getSelf',
            ),
            'mautic_api_checkpermission' => array(
                'path'         => '/users/{id}/permissioncheck',
                'controller'   => 'MauticUserBundle:Api\UserApi:isGranted',
                'method'       => 'POST'
            ),
            'mautic_api_getuserroles'    => array(
                'path'       => '/users/list/roles',
                'controller' => 'MauticUserBundle:Api\UserApi:getRoles',
            ),
            'mautic_api_getroles'        => array(
                'path'       => '/roles',
                'controller' => 'MauticUserBundle:Api\RoleApi:getEntities',
            ),
            'mautic_api_getrole'         => array(
                'path'         => '/roles/{id}',
                'controller'   => 'MauticUserBundle:Api\RoleApi:getEntity'
            )
        ),
        'public' => array(
            'mautic_user_passwordreset' => array(
                'path'       => '/passwordreset',
                'controller' => 'MauticUserBundle:Public:passwordReset'
            ),
            'mautic_user_passwordresetconfirm' => array(
                'path'       => '/passwordresetconfirm',
                'controller' => 'MauticUserBundle:Public:passwordResetConfirm'
            )
        )
    ),

    'services' => array(
        'events' => array(
            'mautic.user.subscriber'        => array(
                'class'     => 'Mautic\UserBundle\EventListener\UserSubscriber',
                'arguments' => [
                    'mautic.helper.ip_lookup',
                    'mautic.core.model.auditlog'
                ]
            ),
            'mautic.user.search.subscriber' => array(
                'class'     => 'Mautic\UserBundle\EventListener\SearchSubscriber',
                'arguments' => [
                    'mautic.user.model.user',
                    'mautic.user.model.role'
                ]
            )
        ),
        'forms'  => array(
            'mautic.form.type.user'           => array(
                'class'     => 'Mautic\UserBundle\Form\Type\UserType',
                'arguments' => [
                    'translator',
                    'doctrine.orm.entity_manager',
                    'mautic.user.model.user',
                    'mautic.helper.language',
                    'mautic.helper.core_parameters',
                ],
                'alias'     => 'user'
            ),
            'mautic.form.type.role'           => array(
                'class' => 'Mautic\UserBundle\Form\Type\RoleType',
                'alias' => 'role'
            ),
            'mautic.form.type.permissions'    => array(
                'class' => 'Mautic\UserBundle\Form\Type\PermissionsType',
                'alias' => 'permissions'
            ),
            'mautic.form.type.permissionlist' => array(
                'class' => 'Mautic\UserBundle\Form\Type\PermissionListType',
                'alias' => 'permissionlist'
            ),
            'mautic.form.type.passwordreset'  => array(
                'class' => 'Mautic\UserBundle\Form\Type\PasswordResetType',
                'alias' => 'passwordreset'
            ),
            'mautic.form.type.passwordresetconfirm'  => array(
                'class' => 'Mautic\UserBundle\Form\Type\PasswordResetConfirmType',
                'alias' => 'passwordresetconfirm'
            ),
            'mautic.form.type.user_list'      => array(
                'class'     => 'Mautic\UserBundle\Form\Type\UserListType',
                'arguments' => 'mautic.user.model.user',
                'alias'     => 'user_list'
            ),
            'mautic.form.type.role_list'      => array(
                'class'     => 'Mautic\UserBundle\Form\Type\RoleListType',
                'arguments' => 'mautic.user.model.role',
                'alias'     => 'role_list'
            )
        ),
        'other'  => array(
            // Authentication
            'mautic.user.manager'                    => array(
                'class'     => 'Doctrine\ORM\EntityManager',
                'arguments' => 'Mautic\UserBundle\Entity\User',
                'factory'   => array('@doctrine', 'getManagerForClass')
            ),
            'mautic.user.repository'                 => array(
                'class'     => 'Mautic\UserBundle\Entity\UserRepository',
                'arguments' => 'Mautic\UserBundle\Entity\User',
                'factory'   => array('@mautic.user.manager', 'getRepository')
            ),
            'mautic.permission.manager'              => array(
                'class'     => 'Doctrine\ORM\EntityManager',
                'arguments' => 'Mautic\UserBundle\Entity\Permission',
                'factory'   => array('@doctrine', 'getManagerForClass')
            ),
            'mautic.permission.repository'           => array(
                'class'     => 'Mautic\UserBundle\Entity\PermissionRepository',
                'arguments' => 'Mautic\UserBundle\Entity\Permission',
                'factory'   => array('@mautic.permission.manager', 'getRepository')
            ),
            'mautic.user.form_authenticator' => array(
                'class'  => 'Mautic\UserBundle\Security\Authenticator\FormAuthenticator',
                'arguments' => array(
                    'mautic.helper.integration',
                    'security.password_encoder',
                    'event_dispatcher',
                    'request_stack'
                )
            ),
            'mautic.user.preauth_authenticator' => array(
                'class'     => 'Mautic\UserBundle\Security\Authenticator\PreAuthAuthenticator',
                'arguments' => array(
                    'mautic.helper.integration',
                    'event_dispatcher',
                    'request_stack',
                    '', // providerKey
                    '' // User provider
                ),
                'public'    => false
            ),
            'mautic.user.provider'                   => array(
                'class'     => 'Mautic\UserBundle\Security\Provider\UserProvider',
                'arguments' => array(
                    'mautic.user.repository',
                    'mautic.permission.repository',
                    'session',
                    'event_dispatcher',
                    'security.encoder_factory'
                )
            ),
            'mautic.security.authentication_listener' => array(
                'class' => 'Mautic\UserBundle\Security\Firewall\AuthenticationListener',
                'arguments' => array(
                    'mautic.security.authentication_handler',
                    'security.token_storage',
                    'security.authentication.manager',
                    'monolog.logger',
                    'event_dispatcher',
                    '' // providerKey
                ),
                'public' => false
            ),
            'mautic.security.authentication_handler' => array(
                'class'     => 'Mautic\UserBundle\Security\Authentication\AuthenticationHandler',
                'arguments' => array(
                    'router',
                    'session'
                )
            ),
            'mautic.security.logout_handler'         => array(
                'class'     => 'Mautic\UserBundle\Security\Authentication\LogoutHandler',
                'arguments' => [
                    'mautic.user.model.user',
                    'event_dispatcher',
                    'mautic.helper.user'
                ]
            )
        ),
        'models' =>  array(
            'mautic.user.model.role' => array(
                'class' => 'Mautic\UserBundle\Model\RoleModel'
            ),
            'mautic.user.model.user' => array(
                'class' => 'Mautic\UserBundle\Model\UserModel',
                'arguments' => array(
                    'mautic.helper.mailer'
                )
            )
        )
    )
);
