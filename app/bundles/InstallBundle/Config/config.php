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
        'other' => [
            'mautic.install.configurator.step.check' => [
                'class'     => 'Mautic\InstallBundle\Configurator\Step\CheckStep',
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
                'class'     => 'Mautic\InstallBundle\Configurator\Step\DoctrineStep',
                'arguments' => [
                    'mautic.configurator',
                ],
                'tag'          => 'mautic.configurator.step',
                'tagArguments' => [
                    'priority' => 1,
                ],
            ],
            'mautic.install.configurator.step.email' => [
                'class'     => 'Mautic\InstallBundle\Configurator\Step\EmailStep',
                'arguments' => [
                    'session',
                ],
                'tag'          => 'mautic.configurator.step',
                'tagArguments' => [
                    'priority' => 3,
                ],
            ],
            'mautic.install.configurator.step.user' => [
                'class'     => 'Mautic\InstallBundle\Configurator\Step\UserStep',
                'arguments' => [
                    'session',
                ],
                'tag'          => 'mautic.configurator.step',
                'tagArguments' => [
                    'priority' => 2,
                ],
            ],
        ],
    ],
];
