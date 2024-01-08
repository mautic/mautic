<?php

return [
    'menu' => [
        'main' => [
            'items' => [
                'mautic.dynamicContent.dynamicContent' => [
                    'route'    => 'mautic_dynamicContent_index',
                    'access'   => ['dynamiccontent:dynamiccontents:viewown', 'dynamiccontent:dynamiccontents:viewother'],
                    'parent'   => 'mautic.core.components',
                    'priority' => 90,
                ],
            ],
        ],
    ],
    'routes' => [
        'main' => [
            'mautic_dynamicContent_index' => [
                'path'       => '/dwc/{page}',
                'controller' => 'Mautic\DynamicContentBundle\Controller\DynamicContentController::indexAction',
            ],
            'mautic_dynamicContent_action' => [
                'path'       => '/dwc/{objectAction}/{objectId}',
                'controller' => 'Mautic\DynamicContentBundle\Controller\DynamicContentController::executeAction',
            ],
        ],
        'public' => [
            'mautic_api_dynamicContent_index' => [
                'path'       => '/dwc',
                'controller' => 'Mautic\DynamicContentBundle\Controller\DynamicContentApiController::getAction',
            ],
            'mautic_api_dynamicContent_action' => [
                'path'       => '/dwc/{objectAlias}',
                'controller' => 'Mautic\DynamicContentBundle\Controller\DynamicContentApiController::processAction',
            ],
        ],
        'api' => [
            'mautic_api_dynamicContent_standard' => [
                'standard_entity' => true,
                'name'            => 'dynamicContents',
                'path'            => '/dynamiccontents',
                'controller'      => \Mautic\DynamicContentBundle\Controller\Api\DynamicContentApiController::class,
            ],
        ],
    ],
    'services' => [
        'forms' => [
            'mautic.form.type.dwc_entry_filters' => [
                'class'     => \Mautic\DynamicContentBundle\Form\Type\DwcEntryFiltersType::class,
                'arguments' => [
                    'translator',
                ],
                'methodCalls' => [
                    'setConnection' => [
                        'database_connection',
                    ],
                ],
            ],
        ],
        'repositories' => [
            'mautic.dynamicContent.repository.stat' => [
                'class'     => Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => \Mautic\DynamicContentBundle\Entity\Stat::class,
            ],
        ],
        'other' => [
            'mautic.helper.dynamicContent' => [
                'class'     => \Mautic\DynamicContentBundle\Helper\DynamicContentHelper::class,
                'arguments' => [
                    'mautic.dynamicContent.model.dynamicContent',
                    'mautic.campaign.executioner.realtime',
                    'event_dispatcher',
                    'mautic.lead.model.lead',
                ],
            ],
        ],
    ],
];
