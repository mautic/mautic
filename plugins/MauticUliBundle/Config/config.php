<?php

declare(strict_types=1);

return [
    'name'        => 'Unique Login Links (ULI)',
    'description' => 'Generate secure one-time login links for users via command line',
    'version'     => '1.0.0',
    'author'      => 'Mautic Community',

    'routes' => [
        'main'   => [],
        'public' => [
            'mautic_uli_login' => [
                'path'       => '/unique_login',
                'controller' => 'MauticPlugin\MauticUliBundle\Controller\UniqueLoginController::loginAction',
            ],
        ],
        'api'    => [],
    ],

    'menu' => [],

    'services' => [
        'commands' => [
            'mautic.uli.command.generate_unique_login' => [
                'class'     => MauticPlugin\MauticUliBundle\Command\GenerateUniqueLoginCommand::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                    'router',
                    'monolog.logger.mautic',
                    '%mautic.site_url%',
                ],
                'tag' => 'console.command',
            ],
        ],
        'other' => [
            'mautic.uli.repository.unique_login' => [
                'class'     => 'Doctrine\ORM\EntityRepository',
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => ['MauticPlugin\MauticUliBundle\Entity\UniqueLogin'],
            ],
        ],
    ],

    'parameters' => [
        'uli_token_lifetime' => 24, // hours
    ],
];