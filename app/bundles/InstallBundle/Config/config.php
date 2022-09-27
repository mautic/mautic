<?php

return [
    'routes' => [
        'public' => [
            'mautic_installer_home' => [
                'path'       => '/installer',
                'controller' => 'Mautic\InstallBundle\Controller\InstallController::stepAction',
            ],
            'mautic_installer_remove_slash' => [
                'path'       => '/installer/',
                'controller' => 'Mautic\CoreBundle\Controller\CommonController::removeTrailingSlashAction',
            ],
            'mautic_installer_step' => [
                'path'       => '/installer/step/{index}',
                'controller' => 'Mautic\InstallBundle\Controller\InstallController::stepAction',
            ],
            'mautic_installer_final' => [
                'path'       => '/installer/final',
                'controller' => 'Mautic\InstallBundle\Controller\InstallController::finalAction',
            ],
            'mautic_installer_catchcall' => [
                'path'         => '/installer/{noerror}',
                'controller'   => 'Mautic\InstallBundle\Controller\InstallController::stepAction',
                'requirements' => [
                    'noerror' => '^(?).+',
                ],
            ],
        ],
    ],

    'services' => [
        'fixtures' => [
            'mautic.install.fixture.lead_field' => [
                'class'     => \Mautic\InstallBundle\InstallFixtures\ORM\LeadFieldData::class,
                'tag'       => \Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass::FIXTURE_TAG,
                'arguments' => [],
            ],
            'mautic.install.fixture.role' => [
                'class'     => \Mautic\InstallBundle\InstallFixtures\ORM\RoleData::class,
                'tag'       => \Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass::FIXTURE_TAG,
                'arguments' => [],
            ],
            'mautic.install.fixture.report_data' => [
                'class'     => \Mautic\InstallBundle\InstallFixtures\ORM\LoadReportData::class,
                'tag'       => \Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass::FIXTURE_TAG,
                'arguments' => [],
            ],
            'mautic.install.fixture.grape_js' => [
                'class'     => \Mautic\InstallBundle\InstallFixtures\ORM\GrapesJsData::class,
                'tag'       => \Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass::FIXTURE_TAG,
                'arguments' => [],
            ],
        ],
        'forms' => [
            \Mautic\InstallBundle\Configurator\Form\CheckStepType::class => [
                'class' => \Mautic\InstallBundle\Configurator\Form\CheckStepType::class,
            ],
            \Mautic\InstallBundle\Configurator\Form\DoctrineStepType::class => [
                'class' => \Mautic\InstallBundle\Configurator\Form\DoctrineStepType::class,
            ],
            \Mautic\InstallBundle\Configurator\Form\EmailStepType::class => [
                'class'     => \Mautic\InstallBundle\Configurator\Form\EmailStepType::class,
                'arguments' => [
                    'translator',
                    'mautic.email.transport_type',
                ],
            ],
            \Mautic\InstallBundle\Configurator\Form\UserStepType::class => [
                'class'     => \Mautic\InstallBundle\Configurator\Form\UserStepType::class,
                'arguments' => ['session'],
            ],
        ],
        'commands' => [
            'mautic.install.command.install' => [
                'tag'       => 'console.command',
                'class'     => \Mautic\InstallBundle\Command\InstallCommand::class,
                'arguments' => [
                    'mautic.install.service',
                    'doctrine',
                ],
            ],
        ],
        'other' => [
            'mautic.install.configurator.step.check' => [
                'class'     => \Mautic\InstallBundle\Configurator\Step\CheckStep::class,
                'arguments' => [
                    'mautic.configurator',
                    '%kernel.root_dir%',
                    'request_stack',
                    'mautic.cipher.openssl',
                ],
                'tag'          => 'mautic.configurator.step',
                'tagArguments' => [
                    'priority' => 0,
                ],
            ],
            'mautic.install.configurator.step.doctrine' => [
                'class'     => \Mautic\InstallBundle\Configurator\Step\DoctrineStep::class,
                'arguments' => [
                    'mautic.configurator',
                ],
                'tag'          => 'mautic.configurator.step',
                'tagArguments' => [
                    'priority' => 1,
                ],
            ],
            'mautic.install.configurator.step.email' => [
                'class'     => \Mautic\InstallBundle\Configurator\Step\EmailStep::class,
                'arguments' => [
                    'session',
                ],
                'tag'          => 'mautic.configurator.step',
                'tagArguments' => [
                    'priority' => 3,
                ],
            ],
            'mautic.install.configurator.step.user' => [
                'class'        => \Mautic\InstallBundle\Configurator\Step\UserStep::class,
                'tag'          => 'mautic.configurator.step',
                'tagArguments' => [
                    'priority' => 2,
                ],
            ],
            'mautic.install.service' => [
                'class'     => \Mautic\InstallBundle\Install\InstallService::class,
                'arguments' => [
                    'mautic.configurator',
                    'mautic.helper.cache',
                    'mautic.helper.paths',
                    'doctrine.orm.entity_manager',
                    'translator',
                    'kernel',
                    'validator',
                    'security.password_encoder',
                    'service_container',
                ],
            ],
            'mautic.install.leadcolumns' => [
                'class'     => \Mautic\InstallBundle\EventListener\DoctrineEventSubscriber::class,
                'tag'       => 'doctrine.event_subscriber',
                'arguments' => [],
            ],
        ],
    ],
];
