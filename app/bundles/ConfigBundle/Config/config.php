<?php

declare(strict_types=1);

return [
    'routes' => [
        'main' => [
            // Must be defined before mautic_config_action so configuration pages are not matched as a generic action
            'mautic_config_edit' => [
                'path'       => '/config/edit/{activeTabId}',
                'controller' => 'Mautic\ConfigBundle\Controller\ConfigController::editAction',
                'defaults'   => [
                    'activeTabId' => null,
                ],
            ],
            'mautic_config_action' => [
                'path'       => '/config/{objectAction}/{objectId}',
                'controller' => 'Mautic\ConfigBundle\Controller\ConfigController::executeAction',
            ],
            'mautic_sysinfo_index' => [
                'path'       => '/sysinfo',
                'controller' => 'Mautic\ConfigBundle\Controller\SysinfoController::indexAction',
            ],
        ],
    ],

    'menu' => [
        'admin' => [
            'mautic.config.menu.index' => [
                'route'           => 'mautic_config_action',
                'routeParameters' => ['objectAction' => 'edit'],
                'iconClass'       => 'ri-settings-5-line',
                'id'              => 'mautic_config_index',
                'parent'          => 'mautic.core.general',
                'access'          => 'admin',
                'priority'        => 16,
            ],
            'mautic.sysinfo.menu.index' => [
                'route'     => 'mautic_sysinfo_index',
                'iconClass' => 'ri-information-2-line',
                'id'        => 'mautic_sysinfo_index',
                'parent'    => 'mautic.core.general',
                'access'    => 'admin',
                'priority'  => 04,
                'checks'    => [
                    'parameters' => [
                        'sysinfo_disabled' => false,
                    ],
                ],
            ],
        ],
    ],

    'parameters' => [
        'config_allowed_parameters' => [
            'kernel.project_dir',
            'kernel.logs_dir',
        ],
    ],
];
