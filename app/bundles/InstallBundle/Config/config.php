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
                'class'     => Mautic\InstallBundle\InstallFixtures\ORM\LeadFieldData::class,
                'tag'       => Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass::FIXTURE_TAG,
                'arguments' => ['translator'],
            ],
            'mautic.install.fixture.role' => [
                'class'     => Mautic\InstallBundle\InstallFixtures\ORM\RoleData::class,
                'tag'       => Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass::FIXTURE_TAG,
                'arguments' => ['translator'],
            ],
            'mautic.install.fixture.report_data' => [
                'class'     => Mautic\InstallBundle\InstallFixtures\ORM\LoadReportData::class,
                'tag'       => Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass::FIXTURE_TAG,
                'arguments' => [],
            ],
        ],
    ],
];
