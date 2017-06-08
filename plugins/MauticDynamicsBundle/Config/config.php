<?php

/*
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return [
    'name'        => 'Dynamics',
    'description' => 'Enables integration with Microsoft Dynamics CRM',
    'version'     => '1.0',
    'author'      => 'Mautic',

    'routes' => [
        'public' => [
            'mautic_plugin_dynamics_callback' => [
                'path'       => '/plugin/dynamics/callback',
                'controller' => 'MauticDynamicsBundle:Public:callback',
            ],
        ],
        'main' => [
            'mautic_plugin_dynamics_action' => [
                'path'       => '/plugin/dynamics/{objectAction}/{objectId}',
                'controller' => 'MauticDynamicsBundle:Dynamics:execute',
            ],
        ],
    ],
    'services' => [
        'integrations' => [
            'mautic.integration.dynamics' => [
                'class'     => \MauticPlugin\MauticDynamicsBundle\Integration\DynamicsIntegration::class,
                'arguments' => [
                ],
            ],
        ],
    ],
];
