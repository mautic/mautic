<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return array(
    'routes'   => array(
        'public' => array(
            'mautic_installer_home'  => array(
                'path'       => '/installer',
                'controller' => 'MauticInstallBundle:Install:step'
            ),
            'mautic_installer_remove_slash'  => array(
                'path'       => '/installer/',
                'controller' => 'MauticCoreBundle:Common:removeTrailingSlash'
            ),
            'mautic_installer_step'  => array(
                'path'       => '/installer/step/{index}',
                'controller' => 'MauticInstallBundle:Install:step'
            ),
            'mautic_installer_final' => array(
                'path'       => '/installer/final',
                'controller' => 'MauticInstallBundle:Install:final'
            )
        )
    ),

    'services' => array(
        'other' => array(
            'mautic.install.configurator.step.check'    => array(
                'class'        => 'Mautic\InstallBundle\Configurator\Step\CheckStep',
                'arguments'    => array(
                    'mautic.configurator',
                    '%kernel.root_dir%',
                    'request_stack',
                ),
                'tag'          => 'mautic.configurator.step',
                'tagArguments' => array(
                    'priority' => 0,
                ),
            ),
            'mautic.install.configurator.step.doctrine' => array(
                'class'        => 'Mautic\InstallBundle\Configurator\Step\DoctrineStep',
                'arguments'    => array(
                    'mautic.configurator',
                ),
                'tag'          => 'mautic.configurator.step',
                'tagArguments' => array(
                    'priority' => 1,
                ),
            ),
            'mautic.install.configurator.step.email'    => array(
                'class'        => 'Mautic\InstallBundle\Configurator\Step\EmailStep',
                'arguments'    => array(
                    'session',
                ),
                'tag'          => 'mautic.configurator.step',
                'tagArguments' => array(
                    'priority' => 3,
                ),
            ),
            'mautic.install.configurator.step.user'     => array(
                'class'        => 'Mautic\InstallBundle\Configurator\Step\UserStep',
                'arguments'    => array(
                    'session',
                ),
                'tag'          => 'mautic.configurator.step',
                'tagArguments' => array(
                    'priority' => 2,
                ),
            ),
        )
    )
);
