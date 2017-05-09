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
            'mautic_campaign_preview' => [
                'path'       => '/campaign/preview/{objectId}',
                'controller' => 'MauticEmailBundle:Public:preview',
            ],
        ],
        'api' => [
            'mautic_api_campaignsstandard' => [
                'standard_entity' => true,
                'name'            => 'campaigns',
                'path'            => '/campaigns',
                'controller'      => 'MauticCampaignBundle:Api\CampaignApi',
            ],
            'mautic_api_campaigneventsstandard' => [
                'standard_entity'     => true,
                'supported_endpoints' => [
                    'getone',
                    'getall',
                ],
                'name'       => 'events',
                'path'       => '/campaigns/events',
                'controller' => 'MauticCampaignBundle:Api\EventApi',
            ],
            'mautic_api_campaigns_events_contact' => [
                'path'       => '/campaigns/events/contact/{contactId}',
                'controller' => 'MauticCampaignBundle:Api\EventLogApi:getContactEvents',
                'method'     => 'GET',
            ],
            'mautic_api_campaigns_edit_contact_event' => [
                'path'       => '/campaigns/events/{eventId}/contact/{contactId}/edit',
                'controller' => 'MauticCampaignBundle:Api\EventLogApi:editContactEvent',
                'method'     => 'PUT',
            ],
            'mautic_api_campaigns_batchedit_events' => [
                'path'       => '/campaigns/events/batch/edit',
                'controller' => 'MauticCampaignBundle:Api\EventLogApi:editEvents',
                'method'     => 'PUT',
            ],
            'mautic_api_campaign_contact_events' => [
                'path'       => '/campaigns/{campaignId}/events/contact/{contactId}',
                'controller' => 'MauticCampaignBundle:Api\EventLogApi:getContactEvents',
                'method'     => 'GET',
            ],
            'mautic_api_campaigngetcontacts' => [
                'path'       => '/campaigns/{id}/contacts',
                'controller' => 'MauticCampaignBundle:Api\CampaignApi:getContacts',
            ],
            'mautic_api_campaignaddcontact' => [
                'path'       => '/campaigns/{id}/contact/{leadId}/add',
                'controller' => 'MauticCampaignBundle:Api\CampaignApi:addLead',
                'method'     => 'POST',
            ],
            'mautic_api_campaignremovecontact' => [
                'path'       => '/campaigns/{id}/contact/{leadId}/remove',
                'controller' => 'MauticCampaignBundle:Api\CampaignApi:removeLead',
                'method'     => 'POST',
            ],

            // @deprecated 2.6.0 to be removed 3.0
            'bc_mautic_api_campaignaddcontact' => [
                'path'       => '/campaigns/{id}/contact/add/{leadId}',
                'controller' => 'MauticCampaignBundle:Api\CampaignApi:addLead',
                'method'     => 'POST',
            ],
            'bc_mautic_api_campaignremovecontact' => [
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
            'mautic.campaign.report.subscriber' => [
                'class' => \Mautic\CampaignBundle\EventListener\ReportSubscriber::class,
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
                'arguments' => 'translator',
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
                    'mautic.user.model.user',
                    'mautic.core.model.notification',
                    'mautic.factory',
                ],
            ],
            'mautic.campaign.model.event_log' => [
                'class'     => 'Mautic\CampaignBundle\Model\EventLogModel',
                'arguments' => [
                    'mautic.campaign.model.event',
                    'mautic.campaign.model.campaign',
                ],
            ],
        ],
    ],
    'parameters' => [
        'campaign_time_wait_on_event_false' => 'PT1H',
    ],
];
