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
    'routes' => [
        'public' => [
            'mautic_installer_home' => [
                'path'       => '/installer',
                'controller' => 'MauticInstallBundle:Install:step',
            ],
            'mautic_installer_remove_slash' => [
                'path'       => '/installer/',
                'controller' => 'MauticCoreBundle:Common:removeTrailingSlash',
            ],
            'mautic_installer_step' => [
                'path'       => '/installer/step/{index}',
                'controller' => 'MauticInstallBundle:Install:step',
            ],
            'mautic_installer_final' => [
                'path'       => '/installer/final',
                'controller' => 'MauticInstallBundle:Install:final',
            ],
            'mautic_installer_catchcall' => [
                'path'         => '/installer/{noerror}',
                'controller'   => 'MauticInstallBundle:Install:step',
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
            'mautic.install.fixture.page_hit' => [
                'class'     => \Mautic\InstallBundle\InstallFixtures\ORM\PageHitIndex::class,
                'tag'       => \Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass::FIXTURE_TAG,
                'arguments' => [],
            ],
            'mautic.install.fixture.report_data' => [
                'class'     => \Mautic\InstallBundle\InstallFixtures\ORM\LoadReportData::class,
                'tag'       => \Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass::FIXTURE_TAG,
                'arguments' => [],
            ],
             'mautic.install.fixture.remove_duplicate_index' => [
                 'class'     => \Mautic\InstallBundle\InstallFixtures\ORM\RemoveDuplicateIndexData::class,
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
                'class'     => 'Mautic\InstallBundle\Install\InstallService',
                'arguments' => [
                    'mautic.configurator',
                    'mautic.helper.cache',
                    'mautic.helper.paths',
                    'doctrine.orm.entity_manager',
                    'translator',
                    'kernel',
                    'validator',
                    'security.password_encoder',
                ],
            ],
        ],
    ],
];
