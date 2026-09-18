<?php

declare(strict_types=1);

return [
    'menu' => [
        'admin' => [
            'mautic.user_management' => [
                'id'        => 'mautic_user_management_root',
                'priority'  => 17,
                'access'    => ['user:users:view', 'user:roles:view'],
            ],
            'mautic.user.users' => [
                'access'    => 'user:users:view',
                'route'     => 'mautic_user_index',
                'parent'    => 'mautic.user_management',
                'iconClass' => 'ri-user-settings-line',
            ],
            'mautic.user.roles' => [
                'access'    => 'user:roles:view',
                'route'     => 'mautic_role_index',
                'parent'    => 'mautic.user_management',
                'iconClass' => 'ri-shield-user-line',
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
                'controller'      => Mautic\UserBundle\Controller\Api\UserApiController::class,
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
                'controller'      => Mautic\UserBundle\Controller\Api\RoleApiController::class,
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
            'mautic_user_invite_register' => [
                'path'       => '/invite/{token}',
                'controller' => 'Mautic\UserBundle\Controller\PublicController::inviteAction',
            ],
            'lightsaml_sp.metadata' => [
                'path'       => '/saml/metadata.xml',
                'controller' => 'LightSaml\SpBundle\Controller\DefaultController::metadataAction',
            ],
            'lightsaml_sp.discovery' => [
                'path'       => '/saml/discovery',
                'controller' => 'LightSaml\SpBundle\Controller\DefaultController::discoveryAction',
            ],
            'mautic_saml_login_retry' => [
                'path'       => '/saml/login_retry',
                'controller' => 'Mautic\UserBundle\Controller\SecurityController::samlLoginRetryAction',
            ],
            'mautic_oidc_login' => [
                'path'       => '/s/open_id/login',
                'controller' => 'Mautic\UserBundle\Controller\SecurityController::oidcLoginAction',
            ],
            'mautic_oidc_check' => [
                'path'       => '/s/open_id/login_check',
                'controller' => 'Mautic\UserBundle\Controller\SecurityController::oidcCheckAction',
            ],
            'mautic_oidc_required' => [
                'path'       => '/s/open_id/required',
                'controller' => 'Mautic\UserBundle\Controller\SecurityController::oidcRequiredAction',
            ],
        ],
    ],

    'services' => [
        'events' => [
            'mautic.user.subscriber' => [
                'class'     => Mautic\UserBundle\EventListener\UserSubscriber::class,
                'arguments' => [
                    'mautic.helper.ip_lookup',
                    'mautic.core.model.auditlog',
                ],
            ],
            'mautic.user.search.subscriber' => [
                'class'     => Mautic\UserBundle\EventListener\SearchSubscriber::class,
                'arguments' => [
                    'mautic.user.model.user',
                    'mautic.user.model.role',
                    'mautic.security',
                    'mautic.helper.templating',
                ],
            ],
            'mautic.user.config.subscriber' => [
                'class' => Mautic\UserBundle\EventListener\ConfigSubscriber::class,
            ],
            'mautic.user.route.subscriber' => [
                'class'     => Mautic\UserBundle\EventListener\SAMLSubscriber::class,
                'arguments' => [
                    'mautic.helper.core_parameters',
                    'router',
                ],
            ],
            'mautic.user.security_subscriber' => [
                'class'     => Mautic\UserBundle\EventListener\SecuritySubscriber::class,
                'arguments' => [
                    'mautic.helper.ip_lookup',
                    'mautic.core.model.auditlog',
                ],
            ],
            'mautic.user.password_subscriber' => [
                'class'     => Mautic\UserBundle\EventListener\PasswordSubscriber::class,
                'arguments' => [
                    'mautic.user.model.password_strength_estimator',
                    'mautic.user.repository',
                    'router',
                ],
            ],
        ],
        'forms' => [
            'mautic.form.type.user' => [
                'class'     => Mautic\UserBundle\Form\Type\UserType::class,
                'arguments' => [
                    'translator',
                    'mautic.user.model.user',
                    'mautic.helper.language',
                ],
            ],
            'mautic.form.type.role' => [
                'class' => Mautic\UserBundle\Form\Type\RoleType::class,
            ],
            'mautic.form.type.permissions' => [
                'class' => Mautic\UserBundle\Form\Type\PermissionsType::class,
            ],
            'mautic.form.type.permissionlist' => [
                'class' => Mautic\UserBundle\Form\Type\PermissionListType::class,
            ],
            'mautic.form.type.passwordreset' => [
                'class' => Mautic\UserBundle\Form\Type\PasswordResetType::class,
            ],
            'mautic.form.type.passwordresetconfirm' => [
                'class' => Mautic\UserBundle\Form\Type\PasswordResetConfirmType::class,
            ],
            'mautic.form.type.user_list' => [
                'class'     => Mautic\UserBundle\Form\Type\UserListType::class,
                'arguments' => 'mautic.user.model.user',
            ],
            'mautic.form.type.role_list' => [
                'class'     => Mautic\UserBundle\Form\Type\RoleListType::class,
                'arguments' => 'mautic.user.model.role',
            ],
            'mautic.form.type.userconfig' => [
                'class'     => Mautic\UserBundle\Form\Type\ConfigType::class,
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
            'mautic.role.repository' => [
                'class'     => 'Mautic\UserBundle\Entity\RoleRepository',
                'arguments' => 'Mautic\UserBundle\Entity\Role',
                'factory'   => ['@doctrine', 'getRepository'],
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
                'class'     => Mautic\UserBundle\Security\Authentication\AuthenticationHandler::class,
                'arguments' => [
                    'router',
                ],
            ],
            'mautic.security.saml.helper' => [
                'class'     => Mautic\UserBundle\Security\SAML\Helper::class,
                'arguments' => [
                    'mautic.helper.core_parameters',
                    'session',
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
                'class'     => Mautic\UserBundle\Security\SAML\Store\CredentialsStore::class,
                'arguments' => [
                    'mautic.helper.core_parameters',
                    '%mautic.saml_idp_entity_id%',
                ],
                'tag'       => 'lightsaml.own_credential_store',
            ],

            'mautic.security.saml.trust_store' => [
                'class'     => Mautic\UserBundle\Security\SAML\Store\TrustOptionsStore::class,
                'arguments' => [
                    'mautic.helper.core_parameters',
                    '%mautic.saml_idp_entity_id%',
                ],
                'tag'       => 'lightsaml.trust_options_store',
            ],

            'mautic.security.saml.entity_descriptor_provider' => [
                'class'     => LightSaml\Builder\EntityDescriptor\SimpleEntityDescriptorBuilder::class,
                'factory'   => [Mautic\UserBundle\Security\SAML\EntityDescriptorProviderFactory::class, 'build'],
                'arguments' => [
                    '%lightsaml.own.entity_id%',
                    'router',
                    '%lightsaml.route.login_check%',
                    'lightsaml.own.credential_store',
                ],
            ],

            'mautic.security.saml.entity_descriptor_store' => [
                'class'     => Mautic\UserBundle\Security\SAML\Store\EntityDescriptorStore::class,
                'arguments' => [
                    'mautic.helper.core_parameters',
                ],
                'tag'       => 'lightsaml.idp_entity_store',
            ],

            'mautic.security.saml.id_store' => [
                'class'     => Mautic\UserBundle\Security\SAML\Store\IdStore::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                    'lightsaml.system.time_provider',
                ],
            ],

            'mautic.security.saml.username_mapper' => [
                'class'     => Mautic\UserBundle\Security\SAML\User\UserMapper::class,
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
                'class'     => Mautic\UserBundle\Security\SAML\User\UserCreator::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                    'mautic.security.saml.username_mapper',
                    'mautic.user.model.user',
                    'security.password_encoder',
                    '%mautic.saml_idp_default_role%',
                ],
            ],
            'mautic.security.user_token_setter' => [
                'class'     => Mautic\UserBundle\Security\UserTokenSetter::class,
                'arguments' => ['mautic.user.repository', 'security.token_storage'],
            ],
            'mautic.security.password.strength.estimator' => [
                'class'     => ZxcvbnPhp\Zxcvbn::class,
            ],

            // OIDC Authentication
            'mautic.security.oidc.linker' => [
                'class'     => Mautic\UserBundle\Security\OIDC\User\Linker::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                ],
            ],
            'mautic.security.oidc.user_provider' => [
                'class'     => Mautic\UserBundle\Security\OIDC\UserProvider::class,
                'arguments' => [
                    'mautic.security.oidc.linker',
                    'mautic.security.oidc.user.factory',
                    'mautic.user.provider',
                    'security.helper',
                ],
            ],
            'mautic.security.oidc.user.factory' => [
                'class'     => Mautic\UserBundle\Security\OIDC\User\UserFactory::class,
                'arguments' => [
                    'mautic.security.oidc.settings',
                    'mautic.user.repository',
                    'mautic.role.repository',
                    'monolog.logger.mautic',
                ],
            ],
            'mautic.security.oidc.client.factory' => [
                'class'     => Mautic\UserBundle\Security\OIDC\Factory\ClientFactory::class,
                'arguments' => ['router', 'event_dispatcher', 'request_stack'],
            ],
            'mautic.security.oidc.client' => [
                'class'     => Mautic\UserBundle\Security\OIDC\Client\ClientInterface::class,
                'factory'   => ['@mautic.security.oidc.client.factory', 'create'],
                'arguments' => ['mautic.security.oidc.client_credentials'],
            ],
            'mautic.security.oidc.user_credentials.factory' => [
                'class'     => Mautic\UserBundle\Security\OIDC\Factory\UserCredentialsFactory::class,
                'arguments' => [
                    'mautic.security.oidc.client',
                    'monolog.logger.mautic',
                ],
            ],
            'mautic.security.oidc.authenticator' => [
                'class'     => Mautic\UserBundle\Security\OIDC\OidcAuthenticator::class,
                'arguments' => [
                    'mautic.security.oidc.settings',
                    'mautic.security.oidc.user_credentials.factory',
                    'mautic.security.oidc.linker',
                    'mautic.security.oidc.repository.subject_id',
                    'router',
                    'mautic.core.service.flashbag',
                    'translator',
                ],
            ],
            'mautic.security.oidc.settings.factory' => [
                'class'     => Mautic\UserBundle\Security\OIDC\Factory\SettingsFactory::class,
                'arguments' => ['mautic.role.repository'],
            ],
            'mautic.security.oidc.settings' => [
                'class'     => Mautic\UserBundle\Security\OIDC\DTO\Settings::class,
                'factory'   => ['@mautic.security.oidc.settings.factory', 'create'],
                'arguments' => [
                    '%mautic.open_id_is_enabled%',
                    '%mautic.open_id_is_required%',
                    '%mautic.open_id_is_user_registration_allowed%',
                    '%mautic.open_id_registered_user_role%',
                ],
            ],
            'mautic.security.oidc.client_credentials' => [
                'class'     => Mautic\UserBundle\Security\OIDC\DTO\ClientCredentials::class,
                'arguments' => [
                    '%mautic.open_id_client_url%',
                    '%mautic.open_id_client_id%',
                    '%mautic.open_id_client_secret%',
                    '%mautic.open_id_mapping_field%',
                ],
            ],
        ],
        'models' => [
            'mautic.user.model.password_strength_estimator' => [
                'class'     => Mautic\UserBundle\Model\PasswordStrengthEstimatorModel::class,
                'arguments' => [
                    'mautic.security.password.strength.estimator',
                ],
            ],
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
                'class'     => Mautic\UserBundle\Model\UserToken\UserTokenService::class,
                'arguments' => [
                    'mautic.helper.random',
                    'mautic.user.repository.user_token',
                ],
            ],
        ],
        'repositories' => [
            'mautic.user.repository.user_token' => [
                'class'     => Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [
                    Mautic\UserBundle\Entity\UserToken::class,
                ],
            ],
            'mautic.user.repository.company' => [
                'class'     => Mautic\LeadBundle\Entity\CompanyRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [
                    Mautic\LeadBundle\Entity\Company::class,
                ],
            ],
            'mautic.security.oidc.repository.subject_id' => [
                'class'     => Mautic\UserBundle\Entity\OidcSubjectIdRepository::class,
                'arguments' => Mautic\UserBundle\Entity\OidcSubjectId::class,
                'factory'   => ['@doctrine', 'getRepository'],
            ],
        ],
        'validator' => [
            'mautic.user.validator.not_weak_validator' => [
                'class'     => Mautic\UserBundle\Form\Validator\Constraints\NotWeakValidator::class,
                'arguments' => [
                    'mautic.user.model.password_strength_estimator',
                ],
                'tag' => 'validator.constraint_validator',
            ],
        ],
        'fixtures' => [
            'mautic.user.fixture.role' => [
                'class'     => Mautic\UserBundle\DataFixtures\ORM\LoadRoleData::class,
                'tag'       => Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass::FIXTURE_TAG,
                'arguments' => ['mautic.user.model.role'],
            ],
            'mautic.user.fixture.user' => [
                'class'     => Mautic\UserBundle\DataFixtures\ORM\LoadUserData::class,
                'tag'       => Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass::FIXTURE_TAG,
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

        // OIDC Parameters
        'open_id_is_enabled'                   => 0,
        'open_id_is_required'                  => 0,
        'open_id_is_user_registration_allowed' => 0,
        'open_id_registered_user_role'         => 2,
        'open_id_client_id'                    => '',
        'open_id_client_secret'                => '',
        'open_id_client_url'                   => '',
        'open_id_mapping_field'                => 'sub',
    ],
];
