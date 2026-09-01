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
            'mautic_user_logincheck' => [
                'path'       => '/login_check',
                'controller' => 'Mautic\UserBundle\Controller\SecurityController::loginCheckAction',
            ],
            'mautic_user_logout' => [
                'path' => '/logout',
            ],
            'lightsaml_sp.login' => [
                'path'       => '/saml/login',
                'controller' => 'LightSaml\SpBundle\Controller\DefaultController::loginAction',
            ],
            'lightsaml_sp.login_check' => [
                'path' => '/saml/login_check',
            ],
        ],

        'api' => [
            'mautic_api_usersstandard' => [
                'standard_entity' => true,
                'name'            => 'users',
                'path'            => '/users',
                'controller'      => Mautic\UserBundle\Controller\Api\UserApiController::class,
            ],
            'mautic_api_rolesstandard' => [
                'standard_entity' => true,
                'name'            => 'roles',
                'path'            => '/roles',
                'controller'      => Mautic\UserBundle\Controller\Api\RoleApiController::class,
            ],
        ],
        'public' => [
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
