<?php

declare(strict_types=1);

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
    'categories' => [
        'dynamicContent' => [
            'class' => Mautic\DynamicContentBundle\Entity\DynamicContent::class,
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
                'controller'      => Mautic\DynamicContentBundle\Controller\Api\DynamicContentApiController::class,
            ],
        ],
    ],
    'services' => [
        'events' => [
            'mautic.dynamicContent.campaignbundle.subscriber' => [
                'class'     => Mautic\DynamicContentBundle\EventListener\CampaignSubscriber::class,
                'arguments' => [
                    'mautic.dynamicContent.model.dynamicContent',
                    'mautic.cache.provider',
                    'event_dispatcher',
                ],
            ],
            'mautic.dynamicContent.js.subscriber' => [
                'class'     => Mautic\DynamicContentBundle\EventListener\BuildJsSubscriber::class,
                'arguments' => [
                    'templating.helper.assets',
                    'translator',
                    'request_stack',
                    'router',
                ],
            ],
            'mautic.dynamicContent.subscriber' => [
                'class'     => Mautic\DynamicContentBundle\EventListener\DynamicContentSubscriber::class,
                'arguments' => [
                    'mautic.page.model.trackable',
                    'mautic.page.helper.token',
                    'mautic.asset.helper.token',
                    'mautic.form.helper.token',
                    'mautic.focus.helper.token',
                    'mautic.core.model.auditlog',
                    'mautic.helper.dynamicContent',
                    'mautic.dynamicContent.model.dynamicContent',
                    'mautic.security',
                    'mautic.tracker.contact',
                    'mautic.lead.repository.company_lead',
                    'mautic.lead.repository.lead_list',
                ],
            ],
            'mautic.dynamicContent.subscriber.channel' => [
                'class' => Mautic\DynamicContentBundle\EventListener\ChannelSubscriber::class,
            ],
            'mautic.dynamicContent.stats.subscriber' => [
                'class'     => Mautic\DynamicContentBundle\EventListener\StatsSubscriber::class,
                'arguments' => [
                    'mautic.security',
                    'doctrine.orm.entity_manager',
                ],
            ],
            'mautic.dynamicContent.lead.subscriber' => [
                'class'     => Mautic\DynamicContentBundle\EventListener\LeadSubscriber::class,
                'arguments' => [
                    'translator',
                    'router',
                    'mautic.dynamicContent.repository.stat',
                ],
            ],
        ],
        'forms' => [
            'mautic.form.type.dwc' => [
                'class'     => Mautic\DynamicContentBundle\Form\Type\DynamicContentType::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                    'mautic.lead.model.list',
                    'mautic.lead.data_transformer.field_filter',
                    'mautic.lead.model.lead',
                ],
            ],
            'mautic.form.type.dwc_entry_filters' => [
                'class'     => Mautic\DynamicContentBundle\Form\Type\DwcEntryFiltersType::class,
                'arguments' => [
                    'translator',
                    'mautic.lead.model.list',
                ],
                'methodCalls' => [
                    'setConnection' => [
                        'database_connection',
                    ],
                ],
            ],
            'mautic.form.type.dwcsend_list' => [
                'class'     => Mautic\DynamicContentBundle\Form\Type\DynamicContentSendType::class,
                'arguments' => [
                    'router',
                ],
            ],
            'mautic.form.type.dwcdecision_list' => [
                'class'     => Mautic\DynamicContentBundle\Form\Type\DynamicContentDecisionType::class,
                'arguments' => [
                    'router',
                ],
            ],
            'mautic.form.type.dwc_list' => [
                'class' => Mautic\DynamicContentBundle\Form\Type\DynamicContentListType::class,
            ],
        ],
        'models' => [
            'mautic.dynamicContent.model.dynamicContent' => [
                'class'     => Mautic\DynamicContentBundle\Model\DynamicContentModel::class,
                'arguments' => [
                ],
            ],
        ],
        'repositories' => [
            'mautic.dynamicContent.repository.stat' => [
                'class'     => Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => Mautic\DynamicContentBundle\Entity\Stat::class,
            ],
        ],
        'other' => [
            'mautic.helper.dynamicContent' => [
                'class'     => Mautic\DynamicContentBundle\Helper\DynamicContentHelper::class,
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
