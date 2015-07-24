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
            'mautic.configurator' => array(
                'class'     => 'Mautic\InstallBundle\Configurator\Configurator',
                'arguments' => array(
                    '%kernel.root_dir%',
                    'mautic.factory'
                )
            )
        )
    )
);