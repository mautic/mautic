<?php

declare(strict_types=1);

return [
    'name'        => 'OpenID Connect',
    'description' => 'Enable login via OpenID Connect',
    'version'     => '0.1.0',
    'author'      => 'Acquia',

    'services' => [
        'events' => [
            'mautic.open_id.subscriber.register_scopes' => [
                'class' => \Mautic\OpenIdBundle\EventListener\RegisterScopesSubscriber::class,
            ],
            'mautic.open_id.subscriber.template_render' => [
                'class'     => \Mautic\OpenIdBundle\EventListener\InjectCustomTemplateSubscriber::class,
                'arguments' => [
                    'mautic.open_id.settings',
                    'templating.helper.slots',
                    'twig',
                    'templating.engine.php',
                ],
            ],
            'mautic.open_id.subscriber.kernel_request' => [
                'class'     => \Mautic\OpenIdBundle\EventListener\KernelRequestSubscriber::class,
                'arguments' => [
                    'mautic.open_id.settings',
                    'security.token_storage',
                    'router',
                    'monolog.logger.mautic',
                ],
            ],
            'mautic.open_id.subscriber.config' => [
                'class'     => \Mautic\OpenIdBundle\EventListener\ConfigEventSubscriber::class,
                'arguments' => [
                    'mautic.open_id.client.factory',
                    'translator',
                    'monolog.logger.mautic',
                ],
            ],
        ],
        'forms' => [
            'mautic.open_id.form.type.subject_id' => [
                'class'     => \Mautic\OpenIdBundle\Form\Type\SubjectIdType::class,
                'arguments' => [
                    'mautic.open_id.linker',
                    'translator',
                ],
            ],
        ],
        'helpers' => [
            'mautic.open_id.form.transformer.subject_to_user' => [
                'class' => \Mautic\OpenIdBundle\Form\Transformer\SubjectToUserTransformer::class,
            ],
        ],
        'other' => [
            'mautic.open_id.linker' => [
                'class'     => \Mautic\OpenIdBundle\Service\Linker::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                ],
            ],
            'mautic.open_id.user_provider' => [
                'class'     => \Mautic\OpenIdBundle\Security\Provider\UserProvider::class,
                'arguments' => [
                    'mautic.open_id.linker',
                    'mautic.open_id.user.factory',
                    'mautic.user.provider',
                    'security.helper',
                ],
            ],
            'mautic.open_id.user.factory' => [
                'class'     => \Mautic\OpenIdBundle\Factory\UserFactory::class,
                'arguments' => [
                    'mautic.open_id.settings',
                    'mautic.user.repository',
                    'mautic.role.repository',
                    'monolog.logger.mautic',
                ],
            ],
            'mautic.open_id.client.factory' => [
                'class'     => \Mautic\OpenIdBundle\Factory\ClientFactory::class,
                'arguments' => ['router', 'event_dispatcher', 'session'],
            ],
            'mautic.open_id.client' => [
                'class'     => \Mautic\OpenIdBundle\Service\ClientInterface::class,
                'factory'   => ['@mautic.open_id.client.factory', 'create'],
                'arguments' => ['mautic.open_id.client_credentials'],
            ],
            'mautic.open_id.user_credentials.factory' => [
                'class'     => \Mautic\OpenIdBundle\Factory\UserCredentialsFactory::class,
                'arguments' => [
                    'mautic.open_id.client',
                    'monolog.logger.mautic',
                ],
            ],
            'mautic.open_id.security.authentication_handler' => [
                'class'     => \Mautic\OpenIdBundle\Security\Authenticator\Authenticator::class,
                'arguments' => [
                    'mautic.open_id.settings',
                    'mautic.open_id.user_credentials.factory',
                    'mautic.open_id.repository.subject_id',
                    'router',
                    'mautic.core.service.flashbag',
                    'translator',
                ],
            ],
            'mautic.open_id.form.extension.user' => [
                'class'        => \Mautic\OpenIdBundle\Form\Extension\UserTypeExtension::class,
                'arguments'    => [
                    'mautic.open_id.settings',
                    'mautic.open_id.repository.subject_id',
                ],
                'tag'          => 'form.type_extension',
                'tagArguments' => [
                    'extended_type' => \Mautic\UserBundle\Form\Type\UserType::class,
                ],
            ],
            'mautic.open_id.form.extension.config' => [
                'class'        => \Mautic\OpenIdBundle\Form\Extension\ConfigTypeExtension::class,
                'tag'          => 'form.type_extension',
                'tagArguments' => [
                    'extended_type' => \Mautic\UserBundle\Form\Type\ConfigType::class,
                ],
                'arguments'    => [
                    'mautic.open_id.settings',
                    'mautic.open_id.client_credentials',
                    'mautic.open_id.repository.subject_id',
                ],
            ],
            'mautic.open_id.settings.factory' => [
                'class'     => \Mautic\OpenIdBundle\Factory\SettingsFactory::class,
                'arguments' => ['mautic.role.repository'],
            ],
            'mautic.open_id.settings' => [
                'class'     => \Mautic\OpenIdBundle\DTO\Settings::class,
                'factory'   => ['@mautic.open_id.settings.factory', 'create'],
                'arguments' => [
                    '%mautic.open_id_is_enabled%',
                    '%mautic.open_id_is_required%',
                    '%mautic.open_id_is_user_registration_allowed%',
                    '%mautic.open_id_registered_user_role%',
                ],
            ],
            'mautic.open_id.client_credentials' => [
                'class'     => \Mautic\OpenIdBundle\DTO\ClientCredentials::class,
                'arguments' => [
                    '%mautic.open_id_client_url%',
                    '%mautic.open_id_client_id%',
                    '%mautic.open_id_client_secret%',
                    '%mautic.open_id_mapping_field%',
                ],
            ],
        ],
        'models' => [
        ],
        'integrations' => [
        ],
        'repositories' => [
            'mautic.open_id.repository.subject_id' => [
                'class'     => \Mautic\OpenIdBundle\Repository\SubjectIdRepository::class,
                'arguments' => \Mautic\OpenIdBundle\Entity\SubjectId::class,
                'factory'   => ['@doctrine', 'getRepository'],
            ],
        ],
        'controllers' => [
            'mautic.open_id.controller.security' => [
                'class'     => \Mautic\OpenIdBundle\Controller\SecurityController::class,
                'arguments' => [
                    'mautic.open_id.settings',
                    'mautic.open_id.client',
                    'monolog.logger.mautic',
                ],
            ],
        ],
    ],
    'routes' => [
        'main' => [
        ],
        'public' => [
            'open_id_login' => [
                'path'       => '/s/open_id/login',
                'controller' => 'OpenIdBundle:Security:login',
                'method'     => 'GET',
            ],
            'open_id_login_check' => [
                'path'       => '/s/open_id/login_check',
                'controller' => 'OpenIdBundle:Security:loginCheck',
                'method'     => 'POST',
            ],
            'open_id_login_required' => [
                'path'       => '/s/open_id/required',
                'controller' => 'OpenIdBundle:Security:required',
                'method'     => 'GET',
            ],
        ],
        'api' => [
        ],
    ],
    'parameters' => [
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
