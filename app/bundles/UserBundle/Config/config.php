<?php

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
