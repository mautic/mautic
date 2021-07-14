<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return [
    'menu' => [
        'admin' => [
            'mautic.user.users' => [
                'access'    => 'user:users:view',
                'route'     => 'mautic_user_index',
                'iconClass' => 'fa-users',
            ],
            'mautic.user.roles' => [
                'access'    => 'user:roles:view',
                'route'     => 'mautic_role_index',
                'iconClass' => 'fa-lock',
            ],
        ],
    ],

    'routes' => [
        'main' => [
            'login' => [
                'path'       => '/login',
                'controller' => 'MauticUserBundle:Security:login',
            ],
            'mautic_user_logincheck' => [
                'path'       => '/login_check',
                'controller' => 'MauticUserBundle:Security:loginCheck',
            ],
            'mautic_user_logout' => [
                'path' => '/logout',
            ],
            'mautic_sso_login' => [
                'path'       => '/sso_login/{integration}',
                'controller' => 'MauticUserBundle:Security:ssoLogin',
            ],
            'mautic_sso_login_check' => [
                'path'       => '/sso_login_check/{integration}',
                'controller' => 'MauticUserBundle:Security:ssoLoginCheck',
            ],
            'lightsaml_sp.login' => [
                'path'       => '/saml/login',
                'controller' => 'LightSamlSpBundle:Default:login',
            ],
            'lightsaml_sp.login_check' => [
                'path' => '/saml/login_check',
            ],
            'mautic_user_index' => [
                'path'       => '/users/{page}',
                'controller' => 'MauticUserBundle:User:index',
            ],
            'mautic_user_action' => [
                'path'       => '/users/{objectAction}/{objectId}',
                'controller' => 'MauticUserBundle:User:execute',
            ],
            'mautic_role_index' => [
                'path'       => '/roles/{page}',
                'controller' => 'MauticUserBundle:Role:index',
            ],
            'mautic_role_action' => [
                'path'       => '/roles/{objectAction}/{objectId}',
                'controller' => 'MauticUserBundle:Role:execute',
            ],
            'mautic_user_account' => [
                'path'       => '/account',
                'controller' => 'MauticUserBundle:Profile:index',
            ],
        ],

        'api' => [
            'mautic_api_usersstandard' => [
                'standard_entity' => true,
                'name'            => 'users',
                'path'            => '/users',
                'controller'      => 'MauticUserBundle:Api\UserApi',
            ],
            'mautic_api_getself' => [
                'path'       => '/users/self',
                'controller' => 'MauticUserBundle:Api\UserApi:getSelf',
            ],
            'mautic_api_checkpermission' => [
                'path'       => '/users/{id}/permissioncheck',
                'controller' => 'MauticUserBundle:Api\UserApi:isGranted',
                'method'     => 'POST',
            ],
            'mautic_api_getuserroles' => [
                'path'       => '/users/list/roles',
                'controller' => 'MauticUserBundle:Api\UserApi:getRoles',
            ],
            'mautic_api_rolesstandard' => [
                'standard_entity' => true,
                'name'            => 'roles',
                'path'            => '/roles',
                'controller'      => 'MauticUserBundle:Api\RoleApi',
            ],
        ],
        'public' => [
            'mautic_user_passwordreset' => [
                'path'       => '/passwordreset',
                'controller' => 'MauticUserBundle:Public:passwordReset',
            ],
            'mautic_user_passwordresetconfirm' => [
                'path'       => '/passwordresetconfirm',
                'controller' => 'MauticUserBundle:Public:passwordResetConfirm',
            ],
            'lightsaml_sp.metadata' => [
                'path'       => '/saml/metadata.xml',
                'controller' => 'LightSamlSpBundle:Default:metadata',
            ],
            'lightsaml_sp.discovery' => [
                'path'       => '/saml/discovery',
                'controller' => 'LightSamlSpBundle:Default:discovery',
            ],
        ],
    ],

    'services' => [
        'events' => [
            'mautic.user.subscriber' => [
                'class'     => \Mautic\UserBundle\EventListener\UserSubscriber::class,
                'arguments' => [
                    'mautic.helper.ip_lookup',
                    'mautic.core.model.auditlog',
                ],
            ],
            'mautic.user.search.subscriber' => [
                'class'     => \Mautic\UserBundle\EventListener\SearchSubscriber::class,
                'arguments' => [
                    'mautic.user.model.user',
                    'mautic.user.model.role',
                    'mautic.security',
                    'mautic.helper.templating',
                ],
            ],
            'mautic.user.config.subscriber' => [
                'class' => \Mautic\UserBundle\EventListener\ConfigSubscriber::class,
            ],
            'mautic.user.route.subscriber' => [
                'class'     => \Mautic\UserBundle\EventListener\SAMLSubscriber::class,
                'arguments' => [
                    'mautic.helper.core_parameters',
                    'router',
                ],
            ],
            'mautic.user.security_subscriber' => [
                'class'     => \Mautic\UserBundle\EventListener\SecuritySubscriber::class,
                'arguments' => [
                    'mautic.helper.ip_lookup',
                    'mautic.core.model.auditlog',
                ],
            ],
        ],
        'forms' => [
            'mautic.form.type.user' => [
                'class'     => \Mautic\UserBundle\Form\Type\UserType::class,
                'arguments' => [
                    'translator',
                    'mautic.user.model.user',
                    'mautic.helper.language',
                ],
            ],
            'mautic.form.type.role' => [
                'class' => \Mautic\UserBundle\Form\Type\RoleType::class,
            ],
            'mautic.form.type.permissions' => [
                'class' => \Mautic\UserBundle\Form\Type\PermissionsType::class,
            ],
            'mautic.form.type.permissionlist' => [
                'class' => \Mautic\UserBundle\Form\Type\PermissionListType::class,
            ],
            'mautic.form.type.passwordreset' => [
                'class' => \Mautic\UserBundle\Form\Type\PasswordResetType::class,
            ],
            'mautic.form.type.passwordresetconfirm' => [
                'class' => \Mautic\UserBundle\Form\Type\PasswordResetConfirmType::class,
            ],
            'mautic.form.type.user_list' => [
                'class'     => \Mautic\UserBundle\Form\Type\UserListType::class,
                'arguments' => 'mautic.user.model.user',
            ],
            'mautic.form.type.role_list' => [
                'class'     => \Mautic\UserBundle\Form\Type\RoleListType::class,
                'arguments' => 'mautic.user.model.role',
            ],
            'mautic.form.type.userconfig' => [
                'class'     => \Mautic\UserBundle\Form\Type\ConfigType::class,
                'arguments' => [
                    'mautic.helper.core_parameters',
                    'translator',
                ],
            ],
        ],
        'other' => [
            // Authentication
            'mautic.user.manager' => [
                'class'     => 'Doctrine\ORM\EntityManager',
                'arguments' => 'Mautic\UserBundle\Entity\User',
                'factory'   => ['@doctrine', 'getManagerForClass'],
            ],
            'mautic.user.repository' => [
                'class'     => 'Mautic\UserBundle\Entity\UserRepository',
                'arguments' => 'Mautic\UserBundle\Entity\User',
                'factory'   => ['@mautic.user.manager', 'getRepository'],
            ],
            'mautic.user.token.repository' => [
                'class'     => 'Mautic\UserBundle\Entity\UserTokenRepository',
                'arguments' => 'Mautic\UserBundle\Entity\UserToken',
                'factory'   => ['@doctrine', 'getRepository'],
            ],
            'mautic.permission.manager' => [
                'class'     => 'Doctrine\ORM\EntityManager',
                'arguments' => 'Mautic\UserBundle\Entity\Permission',
                'factory'   => ['@doctrine', 'getManagerForClass'],
            ],
            'mautic.permission.repository' => [
                'class'     => 'Mautic\UserBundle\Entity\PermissionRepository',
                'arguments' => 'Mautic\UserBundle\Entity\Permission',
                'factory'   => ['@mautic.permission.manager', 'getRepository'],
            ],
            'mautic.user.form_authenticator' => [
                'class'     => 'Mautic\UserBundle\Security\Authenticator\FormAuthenticator',
                'arguments' => [
                    'mautic.helper.integration',
                    'security.password_encoder',
                    'event_dispatcher',
                    'request_stack',
                ],
            ],
            'mautic.user.preauth_authenticator' => [
                'class'     => 'Mautic\UserBundle\Security\Authenticator\PreAuthAuthenticator',
                'arguments' => [
                    'mautic.helper.integration',
                    'event_dispatcher',
                    'request_stack',
                    '', // providerKey
                    '', // User provider
                ],
                'public' => false,
            ],
            'mautic.user.provider' => [
                'class'     => 'Mautic\UserBundle\Security\Provider\UserProvider',
                'arguments' => [
                    'mautic.user.repository',
                    'mautic.permission.repository',
                    'session',
                    'event_dispatcher',
                    'security.password_encoder',
                ],
            ],
            'mautic.security.authentication_listener' => [
                'class'     => 'Mautic\UserBundle\Security\Firewall\AuthenticationListener',
                'arguments' => [
                    'mautic.security.authentication_handler',
                    'security.token_storage',
                    'security.authentication.manager',
                    'monolog.logger',
                    'event_dispatcher',
                    '', // providerKey
                    'mautic.permission.repository',
                    'doctrine.orm.default_entity_manager',
                ],
                'public' => false,
            ],
            'mautic.security.authentication_handler' => [
                'class'     => \Mautic\UserBundle\Security\Authentication\AuthenticationHandler::class,
                'arguments' => [
                    'router',
                ],
            ],
            'mautic.security.logout_handler' => [
                'class'     => 'Mautic\UserBundle\Security\Authentication\LogoutHandler',
                'arguments' => [
                    'mautic.user.model.user',
                    'event_dispatcher',
                    'mautic.helper.user',
                ],
            ],

            // SAML
            'mautic.security.saml.credential_store' => [
                'class'     => \Mautic\UserBundle\Security\SAML\Store\CredentialsStore::class,
                'arguments' => [
                    'mautic.helper.core_parameters',
                    '%mautic.saml_idp_entity_id%',
                ],
                'tag'       => 'lightsaml.own_credential_store',
            ],

            'mautic.security.saml.trust_store' => [
                'class'     => \Mautic\UserBundle\Security\SAML\Store\TrustOptionsStore::class,
                'arguments' => [
                    'mautic.helper.core_parameters',
                    '%mautic.saml_idp_entity_id%',
                ],
                'tag'       => 'lightsaml.trust_options_store',
            ],

            'mautic.security.saml.entity_descriptor_store' => [
                'class'     => \Mautic\UserBundle\Security\SAML\Store\EntityDescriptorStore::class,
                'arguments' => [
                    'mautic.helper.core_parameters',
                ],
                'tag'       => 'lightsaml.idp_entity_store',
            ],

            'mautic.security.saml.id_store' => [
                'class'     => \Mautic\UserBundle\Security\SAML\Store\IdStore::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                    'lightsaml.system.time_provider',
                ],
            ],

            'mautic.security.saml.username_mapper' => [
                'class'     => \Mautic\UserBundle\Security\SAML\User\UserMapper::class,
                'arguments' => [
                    [
                        'email'     => '%mautic.saml_idp_email_attribute%',
                        'username'  => '%mautic.saml_idp_username_attribute%',
                        'firstname' => '%mautic.saml_idp_firstname_attribute%',
                        'lastname'  => '%mautic.saml_idp_lastname_attribute%',
                    ],
                ],
            ],

            'mautic.security.saml.user_creator' => [
                'class'     => \Mautic\UserBundle\Security\SAML\User\UserCreator::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                    'mautic.security.saml.username_mapper',
                    'mautic.user.model.user',
                    'security.password_encoder',
                    '%mautic.saml_idp_default_role%',
                ],
            ],
        ],
        'models' => [
            'mautic.user.model.role' => [
                'class' => 'Mautic\UserBundle\Model\RoleModel',
            ],
            'mautic.user.model.user' => [
                'class'     => 'Mautic\UserBundle\Model\UserModel',
                'arguments' => [
                    'mautic.helper.mailer',
                    'mautic.user.model.user_token_service',
                ],
            ],
            'mautic.user.model.user_token_service' => [
                'class'     => \Mautic\UserBundle\Model\UserToken\UserTokenService::class,
                'arguments' => [
                    'mautic.helper.random',
                    'mautic.user.repository.user_token',
                ],
            ],
        ],
        'repositories' => [
            'mautic.user.repository.user_token' => [
                'class'     => \Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [
                    \Mautic\UserBundle\Entity\UserToken::class,
                ],
            ],
        ],
        'fixtures' => [
            'mautic.user.fixture.role' => [
                'class'     => \Mautic\UserBundle\DataFixtures\ORM\LoadRoleData::class,
                'tag'       => \Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass::FIXTURE_TAG,
                'arguments' => ['mautic.user.model.role'],
            ],
            'mautic.user.fixture.user' => [
                'class'     => \Mautic\UserBundle\DataFixtures\ORM\LoadUserData::class,
                'tag'       => \Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass::FIXTURE_TAG,
                'arguments' => ['security.password_encoder'],
            ],
        ],
    ],
    'parameters' => [
        'saml_idp_metadata'            => '',
        'saml_idp_entity_id'           => '',
        'saml_idp_own_certificate'     => '',
        'saml_idp_own_private_key'     => '',
        'saml_idp_own_password'        => '',
        'saml_idp_email_attribute'     => '',
        'saml_idp_username_attribute'  => '',
        'saml_idp_firstname_attribute' => '',
        'saml_idp_lastname_attribute'  => '',
        'saml_idp_default_role'        => '',
    ],
];
