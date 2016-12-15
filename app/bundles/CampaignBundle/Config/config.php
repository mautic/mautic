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
            'mautic_campaignevent_action' => [
                'path'       => '/campaigns/events/{objectAction}/{objectId}',
                'controller' => 'MauticCampaignBundle:Event:execute',
            ],
            'mautic_campaignsource_action' => [
                'path'       => '/campaigns/sources/{objectAction}/{objectId}',
                'controller' => 'MauticCampaignBundle:Source:execute',
            ],
            'mautic_campaign_index' => [
                'path'       => '/campaigns/{page}',
                'controller' => 'MauticCampaignBundle:Campaign:index',
            ],
            'mautic_campaign_action' => [
                'path'       => '/campaigns/{objectAction}/{objectId}',
                'controller' => 'MauticCampaignBundle:Campaign:execute',
            ],
            'mautic_campaign_contacts' => [
                'path'       => '/campaigns/view/{objectId}/contact/{page}',
                'controller' => 'MauticCampaignBundle:Campaign:contacts',
            ],
        ],
        'api' => [
            'mautic_api_campaignsstandard' => [
                'standard_entity' => true,
                'name'            => 'campaigns',
                'path'            => '/campaigns',
                'controller'      => 'MauticCampaignBundle:Api\CampaignApi',
            ],
            'mautic_api_campaignaddcontact' => [
                'path'       => '/campaigns/{id}/contact/add/{leadId}',
                'controller' => 'MauticCampaignBundle:Api\CampaignApi:addLead',
                'method'     => 'POST',
            ],
            'mautic_api_campaignremovecontact' => [
                'path'       => '/campaigns/{id}/contact/remove/{leadId}',
                'controller' => 'MauticCampaignBundle:Api\CampaignApi:removeLead',
                'method'     => 'POST',
            ],
        ],
    ],

    'menu' => [
        'main' => [
            'mautic.campaign.menu.index' => [
                'iconClass' => 'fa-clock-o',
                'route'     => 'mautic_campaign_index',
                'access'    => 'campaign:campaigns:view',
                'priority'  => 50,
            ],
        ],
    ],

    'categories' => [
        'campaign' => null,
    ],

    'services' => [
        'events' => [
            'mautic.campaign.subscriber' => [
                'class'     => 'Mautic\CampaignBundle\EventListener\CampaignSubscriber',
                'arguments' => [
                    'mautic.helper.ip_lookup',
                    'mautic.core.model.auditlog',
                ],
            ],
            'mautic.campaign.leadbundle.subscriber' => [
                'class'     => 'Mautic\CampaignBundle\EventListener\LeadSubscriber',
                'arguments' => [
                    'mautic.campaign.model.campaign',
                    'mautic.lead.model.lead',
                ],
            ],
            'mautic.campaign.calendarbundle.subscriber' => [
                'class' => 'Mautic\CampaignBundle\EventListener\CalendarSubscriber',
            ],
            'mautic.campaign.pointbundle.subscriber' => [
                'class' => 'Mautic\CampaignBundle\EventListener\PointSubscriber',
            ],
            'mautic.campaign.search.subscriber' => [
                'class'     => 'Mautic\CampaignBundle\EventListener\SearchSubscriber',
                'arguments' => [
                    'mautic.campaign.model.campaign',
                ],
            ],
            'mautic.campaign.dashboard.subscriber' => [
                'class'     => 'Mautic\CampaignBundle\EventListener\DashboardSubscriber',
                'arguments' => [
                    'mautic.campaign.model.campaign',
                    'mautic.campaign.model.event',
                ],
            ],
            'mautic.campaignconfigbundle.subscriber' => [
                'class' => 'Mautic\CampaignBundle\EventListener\ConfigSubscriber',
            ],
            'mautic.campaign.stats.subscriber' => [
                'class'     => \Mautic\CampaignBundle\EventListener\StatsSubscriber::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                ],
            ],
        ],
        'forms' => [
            'mautic.campaign.type.form' => [
                'class'     => 'Mautic\CampaignBundle\Form\Type\CampaignType',
                'arguments' => 'mautic.factory',
                'alias'     => 'campaign',
            ],
            'mautic.campaignrange.type.action' => [
                'class' => 'Mautic\CampaignBundle\Form\Type\EventType',
                'alias' => 'campaignevent',
            ],
            'mautic.campaign.type.campaignlist' => [
                'class'     => 'Mautic\CampaignBundle\Form\Type\CampaignListType',
                'arguments' => 'mautic.factory',
                'alias'     => 'campaign_list',
            ],
            'mautic.campaign.type.trigger.leadchange' => [
                'class' => 'Mautic\CampaignBundle\Form\Type\CampaignEventLeadChangeType',
                'alias' => 'campaignevent_leadchange',
            ],
            'mautic.campaign.type.action.addremovelead' => [
                'class' => 'Mautic\CampaignBundle\Form\Type\CampaignEventAddRemoveLeadType',
                'alias' => 'campaignevent_addremovelead',
            ],
            'mautic.campaign.type.canvassettings' => [
                'class' => 'Mautic\CampaignBundle\Form\Type\EventCanvasSettingsType',
                'alias' => 'campaignevent_canvassettings',
            ],
            'mautic.campaign.type.leadsource' => [
                'class'     => 'Mautic\CampaignBundle\Form\Type\CampaignLeadSourceType',
                'arguments' => 'mautic.factory',
                'alias'     => 'campaign_leadsource',
            ],
            'mautic.form.type.campaignconfig' => [
                'class'     => 'Mautic\CampaignBundle\Form\Type\ConfigType',
                'arguments' => 'mautic.factory',
                'alias'     => 'campaignconfig',
            ],
        ],
        'models' => [
            'mautic.campaign.model.campaign' => [
                'class'     => 'Mautic\CampaignBundle\Model\CampaignModel',
                'arguments' => [
                    'mautic.helper.core_parameters',
                    'mautic.lead.model.lead',
                    'mautic.lead.model.list',
                    'mautic.form.model.form',
                ],
            ],
            'mautic.campaign.model.event' => [
                'class'     => 'Mautic\CampaignBundle\Model\EventModel',
                'arguments' => [
                    'mautic.helper.ip_lookup',
                    'mautic.helper.core_parameters',
                    'mautic.lead.model.lead',
                    'mautic.campaign.model.campaign',
                    'mautic.factory',
                ],
            ],
        ],
    ],
    'parameters' => [
        'campaign_time_wait_on_event_false' => 'PT1H',
    ],
];
