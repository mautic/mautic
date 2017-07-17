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
        'main' => [
            'mautic_config_action' => [
                'path'       => '/config/{objectAction}/{objectId}',
                'controller' => 'MauticConfigBundle:Config:execute',
            ],
            'mautic_sysinfo_index' => [
                'path'       => '/sysinfo',
                'controller' => 'MauticConfigBundle:Sysinfo:index',
            ],
        ],
    ],

    'menu' => [
        'admin' => [
            'mautic.config.menu.index' => [
                'route'           => 'mautic_config_action',
                'routeParameters' => ['objectAction' => 'edit'],
                'iconClass'       => 'fa-cogs',
                'id'              => 'mautic_config_index',
                'access'          => 'admin',
            ],
            'mautic.sysinfo.menu.index' => [
                'route'     => 'mautic_sysinfo_index',
                'iconClass' => 'fa-life-ring',
                'id'        => 'mautic_sysinfo_index',
                'access'    => 'admin',
                'checks'    => [
                   'parameters' => [
                       'sysinfo_disabled' => false,
                   ],
                ],
            ],
        ],
    ],

    'services' => [
        'events' => [
            'mautic.config.subscriber' => [
                'class'     => 'Mautic\ConfigBundle\EventListener\ConfigSubscriber',
                'arguments' => [
                    'mautic.helper.core_parameters',
                ],
            ],
        ],

        'forms' => [
            'mautic.form.type.config' => [
                'class'     => 'Mautic\ConfigBundle\Form\Type\ConfigType',
                'arguments' => 'translator',
                'alias'     => 'config',
            ],
        ],
        'models' => [
            'mautic.config.model.config' => [
                'class' => 'Mautic\ConfigBundle\Model\ConfigModel',
            ],
            'mautic.config.model.sysinfo' => [
                'class'     => 'Mautic\ConfigBundle\Model\SysinfoModel',
                'arguments' => [
                    'mautic.helper.paths',
                    'mautic.helper.core_parameters',
                ],
            ],
        ],
    ],
];
