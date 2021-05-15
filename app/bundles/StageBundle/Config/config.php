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
            'mautic_stage_index' => [
                'path'       => '/stages/{page}',
                'controller' => 'MauticStageBundle:Stage:index',
            ],
            'mautic_stage_action' => [
                'path'       => '/stages/{objectAction}/{objectId}',
                'controller' => 'MauticStageBundle:Stage:execute',
            ],
        ],
        'api' => [
            'mautic_api_stagesstandard' => [
                'standard_entity' => true,
                'name'            => 'stages',
                'path'            => '/stages',
                'controller'      => 'MauticStageBundle:Api\StageApi',
            ],
            'mautic_api_stageddcontact' => [
                'path'       => '/stages/{id}/contact/{contactId}/add',
                'controller' => 'MauticStageBundle:Api\StageApi:addContact',
                'method'     => 'POST',
            ],
            'mautic_api_stageremovecontact' => [
                'path'       => '/stages/{id}/contact/{contactId}/remove',
                'controller' => 'MauticStageBundle:Api\StageApi:removeContact',
                'method'     => 'POST',
            ],
        ],
    ],

    'menu' => [
        'main' => [
            'mautic.stages.menu.index' => [
                'route'     => 'mautic_stage_index',
                'iconClass' => 'fa-tachometer',
                'access'    => ['stage:stages:view'],
                'priority'  => 25,
            ],
        ],
    ],

    'categories' => [
        'stage' => null,
    ],

    'services' => [
        'events' => [
            'mautic.stage.campaignbundle.subscriber' => [
                'class'     => \Mautic\StageBundle\EventListener\CampaignSubscriber::class,
                'arguments' => [
                    'mautic.lead.model.lead',
                    'mautic.stage.model.stage',
                    'translator',
                ],
            ],
            'mautic.stage.subscriber' => [
                'class'     => \Mautic\StageBundle\EventListener\StageSubscriber::class,
                'arguments' => [
                    'mautic.helper.ip_lookup',
                    'mautic.core.model.auditlog',
                ],
            ],
            'mautic.stage.leadbundle.subscriber' => [
                'class'     => \Mautic\StageBundle\EventListener\LeadSubscriber::class,
                'arguments' => [
                    'mautic.lead.repository.stages_lead_log',
                    'mautic.stage.repository.lead_stage_log',
                    'translator',
                    'router',
                ],
            ],
            'mautic.stage.search.subscriber' => [
                'class'     => \Mautic\StageBundle\EventListener\SearchSubscriber::class,
                'arguments' => [
                    'mautic.stage.model.stage',
                    'mautic.security',
                    'mautic.helper.templating',
                ],
            ],
            'mautic.stage.dashboard.subscriber' => [
                'class'     => \Mautic\StageBundle\EventListener\DashboardSubscriber::class,
                'arguments' => [
                    'mautic.stage.model.stage',
                ],
            ],
            'mautic.stage.stats.subscriber' => [
                'class'     => \Mautic\StageBundle\EventListener\StatsSubscriber::class,
                'arguments' => [
                    'mautic.security',
                    'doctrine.orm.entity_manager',
                ],
            ],
        ],
        'forms' => [
            'mautic.stage.type.form' => [
                'class'     => \Mautic\StageBundle\Form\Type\StageType::class,
                'arguments' => [
                    'mautic.security',
                ],
            ],
            'mautic.stage.type.action' => [
                'class' => 'Mautic\StageBundle\Form\Type\StageActionType',
            ],
            'mautic.stage.type.action_list' => [
                'class'     => 'Mautic\StageBundle\Form\Type\StageActionListType',
                'arguments' => [
                    'mautic.stage.model.stage',
                ],
            ],
            'mautic.stage.type.action_change' => [
                'class' => 'Mautic\StageBundle\Form\Type\StageActionChangeType',
            ],
            'mautic.stage.type.stage_list' => [
                'class'     => 'Mautic\StageBundle\Form\Type\StageListType',
                'arguments' => [
                    'mautic.stage.model.stage',
                ],
            ],
            'mautic.point.type.genericstage_settings' => [
                'class' => 'Mautic\StageBundle\Form\Type\GenericStageSettingsType',
            ],
        ],
        'models' => [
            'mautic.stage.model.stage' => [
                'class'     => 'Mautic\StageBundle\Model\StageModel',
                'arguments' => [
                    'mautic.lead.model.lead',
                    'session',
                    'mautic.helper.user',
                ],
            ],
        ],
        'repositories' => [
            'mautic.stage.repository.lead_stage_log' => [
                'class'     => Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [
                    \Mautic\StageBundle\Entity\LeadStageLog::class,
                ],
            ],
        ],
    ],
];
