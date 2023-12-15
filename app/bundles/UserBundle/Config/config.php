<?php

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
                'controller' => 'Mautic\UserBundle\Controller\SecurityController::loginAction',
            ],
            'mautic_user_logincheck' => [
                'path'       => '/login_check',
                'controller' => 'Mautic\UserBundle\Controller\SecurityController::loginCheckAction',
            ],
            'mautic_user_logout' => [
                'path' => '/logout',
            ],
            'mautic_sso_login' => [
                'path'       => '/sso_login/{integration}',
                'controller' => 'Mautic\UserBundle\Controller\SecurityController::ssoLoginAction',
            ],
            'mautic_sso_login_check' => [
                'path'       => '/sso_login_check/{integration}',
                'controller' => 'Mautic\UserBundle\Controller\SecurityController::ssoLoginCheckAction',
            ],
            'lightsaml_sp.login' => [
                'path'       => '/saml/login',
                'controller' => 'LightSaml\SpBundle\Controller\DefaultController::loginAction',
            ],
            'lightsaml_sp.login_check' => [
                'path' => '/saml/login_check',
            ],
            'mautic_user_index' => [
                'path'       => '/users/{page}',
                'controller' => 'Mautic\UserBundle\Controller\UserController::indexAction',
            ],
            'mautic_user_action' => [
                'path'       => '/users/{objectAction}/{objectId}',
                'controller' => 'Mautic\UserBundle\Controller\UserController::executeAction',
            ],
            'mautic_role_index' => [
                'path'       => '/roles/{page}',
                'controller' => 'Mautic\UserBundle\Controller\RoleController::indexAction',
            ],
            'mautic_role_action' => [
                'path'       => '/roles/{objectAction}/{objectId}',
                'controller' => 'Mautic\UserBundle\Controller\RoleController::executeAction',
            ],
            'mautic_user_account' => [
                'path'       => '/account',
                'controller' => 'Mautic\UserBundle\Controller\ProfileController::indexAction',
            ],
        ],

        'api' => [
            'mautic_api_usersstandard' => [
                'standard_entity' => true,
                'name'            => 'users',
                'path'            => '/users',
                'controller'      => \Mautic\UserBundle\Controller\Api\UserApiController::class,
            ],
            'mautic_api_getself' => [
                'path'       => '/users/self',
                'controller' => 'Mautic\UserBundle\Controller\Api\UserApiController::getSelfAction',
            ],
            'mautic_api_checkpermission' => [
                'path'       => '/users/{id}/permissioncheck',
                'controller' => 'Mautic\UserBundle\Controller\Api\UserApiController::isGrantedAction',
                'method'     => 'POST',
            ],
            'mautic_api_getuserroles' => [
                'path'       => '/users/list/roles',
                'controller' => 'Mautic\UserBundle\Controller\Api\UserApiController::getRolesAction',
            ],
            'mautic_api_rolesstandard' => [
                'standard_entity' => true,
                'name'            => 'roles',
                'path'            => '/roles',
                'controller'      => \Mautic\UserBundle\Controller\Api\RoleApiController::class,
            ],
        ],
        'public' => [
            'mautic_user_passwordreset' => [
                'path'       => '/passwordreset',
                'controller' => 'Mautic\UserBundle\Controller\PublicController::passwordResetAction',
            ],
            'mautic_user_passwordresetconfirm' => [
                'path'       => '/passwordresetconfirm',
                'controller' => 'Mautic\UserBundle\Controller\PublicController::passwordResetConfirmAction',
            ],
            'lightsaml_sp.metadata' => [
                'path'       => '/saml/metadata.xml',
                'controller' => 'LightSaml\SpBundle\Controller\DefaultController::metadataAction',
            ],
            'lightsaml_sp.discovery' => [
                'path'       => '/saml/discovery',
                'controller' => 'LightSaml\SpBundle\Controller\DefaultController::discoveryAction',
            ],
        ],
    ],

    'services' => [
        'other' => [
            // Authentication
            'mautic.user.manager' => [
                'class'     => \Doctrine\ORM\EntityManager::class,
                'arguments' => \Mautic\UserBundle\Entity\User::class,
                'factory'   => ['@doctrine', 'getManagerForClass'],
            ],
            'mautic.permission.manager' => [
                'class'     => \Doctrine\ORM\EntityManager::class,
                'arguments' => \Mautic\UserBundle\Entity\Permission::class,
                'factory'   => ['@doctrine', 'getManagerForClass'],
            ],
            'mautic.user.form_guard_authenticator' => [
                'class'     => \Mautic\UserBundle\Security\Authenticator\FormAuthenticator::class,
                'arguments' => [
                    'mautic.helper.integration',
                    'security.password_hasher',
                    'event_dispatcher',
                    'request_stack',
                    'security.csrf.token_manager',
                    'router',
                ],
            ],
            'mautic.user.preauth_authenticator' => [
                'class'     => \Mautic\UserBundle\Security\Authenticator\PreAuthAuthenticator::class,
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
                'class'     => \Mautic\UserBundle\Security\Provider\UserProvider::class,
                'arguments' => [
                    'mautic.user.repository',
                    'mautic.permission.repository',
                    'session',
                    'event_dispatcher',
                    'security.password_hasher',
                ],
            ],
            'mautic.security.authentication_listener' => [
                'class'     => \Mautic\UserBundle\Security\Firewall\AuthenticationListener::class,
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
                'class'        => \Mautic\UserBundle\EventListener\LogoutListener::class,
                'tagArguments' => [
                    'event'      => \Symfony\Component\Security\Http\Event\LogoutEvent::class,
                ],
                'tag'          => 'kernel.event_listener',
                'arguments'    => [
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
                    'security.password_hasher',
                    '%mautic.saml_idp_default_role%',
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
                'arguments' => [\Mautic\UserBundle\Entity\UserToken::class],
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
            ],
            'mautic.user.repository' => [
                'class'     => \Doctrine\ORM\EntityRepository::class,
                'arguments' => \Mautic\UserBundle\Entity\User::class,
                'factory'   => ['@mautic.user.manager', 'getRepository'],
            ],
            'mautic.permission.repository' => [
                'class'     => \Doctrine\ORM\EntityRepository::class,
                'arguments' => \Mautic\UserBundle\Entity\Permission::class,
                'factory'   => ['@mautic.permission.manager', 'getRepository'],
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
                'arguments' => ['security.password_hasher'],
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
