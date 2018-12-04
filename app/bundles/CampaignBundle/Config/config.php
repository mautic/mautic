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
            'mautic_campaignevent_action'  => [
                'path'       => '/campaigns/events/{objectAction}/{objectId}',
                'controller' => 'MauticCampaignBundle:Event:execute',
            ],
            'mautic_campaignsource_action' => [
                'path'       => '/campaigns/sources/{objectAction}/{objectId}',
                'controller' => 'MauticCampaignBundle:Source:execute',
            ],
            'mautic_campaign_index'        => [
                'path'       => '/campaigns/{page}',
                'controller' => 'MauticCampaignBundle:Campaign:index',
            ],
            'mautic_campaign_action'       => [
                'path'       => '/campaigns/{objectAction}/{objectId}',
                'controller' => 'MauticCampaignBundle:Campaign:execute',
            ],
            'mautic_campaign_contacts'     => [
                'path'       => '/campaigns/view/{objectId}/contact/{page}',
                'controller' => 'MauticCampaignBundle:Campaign:contacts',
            ],
            'mautic_campaign_preview'      => [
                'path'       => '/campaign/preview/{objectId}',
                'controller' => 'MauticEmailBundle:Public:preview',
            ],
        ],
        'api'  => [
            'mautic_api_campaignsstandard'            => [
                'standard_entity' => true,
                'name'            => 'campaigns',
                'path'            => '/campaigns',
                'controller'      => 'MauticCampaignBundle:Api\CampaignApi',
            ],
            'mautic_api_campaigneventsstandard'       => [
                'standard_entity'     => true,
                'supported_endpoints' => [
                    'getone',
                    'getall',
                ],
                'name'                => 'events',
                'path'                => '/campaigns/events',
                'controller'          => 'MauticCampaignBundle:Api\EventApi',
            ],
            'mautic_api_campaigns_events_contact'     => [
                'path'       => '/campaigns/events/contact/{contactId}',
                'controller' => 'MauticCampaignBundle:Api\EventLogApi:getContactEvents',
                'method'     => 'GET',
            ],
            'mautic_api_campaigns_edit_contact_event' => [
                'path'       => '/campaigns/events/{eventId}/contact/{contactId}/edit',
                'controller' => 'MauticCampaignBundle:Api\EventLogApi:editContactEvent',
                'method'     => 'PUT',
            ],
            'mautic_api_campaigns_batchedit_events'   => [
                'path'       => '/campaigns/events/batch/edit',
                'controller' => 'MauticCampaignBundle:Api\EventLogApi:editEvents',
                'method'     => 'PUT',
            ],
            'mautic_api_campaign_contact_events'      => [
                'path'       => '/campaigns/{campaignId}/events/contact/{contactId}',
                'controller' => 'MauticCampaignBundle:Api\EventLogApi:getContactEvents',
                'method'     => 'GET',
            ],
            'mautic_api_campaigngetcontacts'          => [
                'path'       => '/campaigns/{id}/contacts',
                'controller' => 'MauticCampaignBundle:Api\CampaignApi:getContacts',
            ],
            'mautic_api_campaignaddcontact'           => [
                'path'       => '/campaigns/{id}/contact/{leadId}/add',
                'controller' => 'MauticCampaignBundle:Api\CampaignApi:addLead',
                'method'     => 'POST',
            ],
            'mautic_api_campaignremovecontact'        => [
                'path'       => '/campaigns/{id}/contact/{leadId}/remove',
                'controller' => 'MauticCampaignBundle:Api\CampaignApi:removeLead',
                'method'     => 'POST',
            ],
            'mautic_api_contact_clone_campaign' => [
                'path'       => '/campaigns/clone/{campaignId}',
                'controller' => 'MauticCampaignBundle:Api\CampaignApi:cloneCampaign',
                'method'     => 'POST',
            ],

            // @deprecated 2.6.0 to be removed 3.0
            'bc_mautic_api_campaignaddcontact'        => [
                'path'       => '/campaigns/{id}/contact/add/{leadId}',
                'controller' => 'MauticCampaignBundle:Api\CampaignApi:addLead',
                'method'     => 'POST',
            ],
            'bc_mautic_api_campaignremovecontact'     => [
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

    'services'   => [
        'events'       => [
            'mautic.campaign.subscriber'                => [
                'class'     => \Mautic\CampaignBundle\EventListener\CampaignSubscriber::class,
                'arguments' => [
                    'mautic.helper.ip_lookup',
                    'mautic.core.model.auditlog',
                ],
            ],
            'mautic.campaign.leadbundle.subscriber'     => [
                'class'     => 'Mautic\CampaignBundle\EventListener\LeadSubscriber',
                'arguments' => [
                    'mautic.campaign.model.campaign',
                    'mautic.lead.model.lead',
                ],
            ],
            'mautic.campaign.calendarbundle.subscriber' => [
                'class' => 'Mautic\CampaignBundle\EventListener\CalendarSubscriber',
            ],
            'mautic.campaign.pointbundle.subscriber'    => [
                'class' => 'Mautic\CampaignBundle\EventListener\PointSubscriber',
            ],
            'mautic.campaign.search.subscriber'         => [
                'class'     => 'Mautic\CampaignBundle\EventListener\SearchSubscriber',
                'arguments' => [
                    'mautic.campaign.model.campaign',
                ],
            ],
            'mautic.campaign.dashboard.subscriber'      => [
                'class'     => 'Mautic\CampaignBundle\EventListener\DashboardSubscriber',
                'arguments' => [
                    'mautic.campaign.model.campaign',
                    'mautic.campaign.model.event',
                ],
            ],
            'mautic.campaignconfigbundle.subscriber'    => [
                'class' => 'Mautic\CampaignBundle\EventListener\ConfigSubscriber',
            ],
            'mautic.campaign.stats.subscriber'          => [
                'class'     => \Mautic\CampaignBundle\EventListener\StatsSubscriber::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                ],
            ],
            'mautic.campaign.report.subscriber'         => [
                'class'     => \Mautic\CampaignBundle\EventListener\ReportSubscriber::class,
                'arguments' => [
                    'mautic.lead.model.company_report_data',
                ],
            ],
            'mautic.campaign.action.change_membership.subscriber' => [
                'class'     => \Mautic\CampaignBundle\EventListener\CampaignActionChangeMembershipSubscriber::class,
                'arguments' => [
                    'mautic.campaign.membership.manager',
                    'mautic.campaign.model.campaign',
                ],
            ],
            'mautic.campaign.action.jump_to_event.subscriber' => [
                'class'     => \Mautic\CampaignBundle\EventListener\CampaignActionJumpToEventSubscriber::class,
                'arguments' => [
                    'mautic.campaign.repository.event',
                    'mautic.campaign.event_executioner',
                    'translator',
                ],
            ],
        ],
        'forms'        => [
            'mautic.campaign.type.form'                 => [
                'class'     => 'Mautic\CampaignBundle\Form\Type\CampaignType',
                'arguments' => 'mautic.factory',
                'alias'     => 'campaign',
            ],
            'mautic.campaignrange.type.action'          => [
                'class' => 'Mautic\CampaignBundle\Form\Type\EventType',
                'alias' => 'campaignevent',
            ],
            'mautic.campaign.type.campaignlist'         => [
                'class'     => 'Mautic\CampaignBundle\Form\Type\CampaignListType',
                'arguments' => [
                    'mautic.campaign.model.campaign',
                    'translator',
                    'mautic.security',
                ],
                'alias'     => 'campaign_list',
            ],
            'mautic.campaign.type.trigger.leadchange'   => [
                'class' => 'Mautic\CampaignBundle\Form\Type\CampaignEventLeadChangeType',
                'alias' => 'campaignevent_leadchange',
            ],
            'mautic.campaign.type.action.addremovelead' => [
                'class' => 'Mautic\CampaignBundle\Form\Type\CampaignEventAddRemoveLeadType',
                'alias' => 'campaignevent_addremovelead',
            ],
            'mautic.campaign.type.action.jump_to_event' => [
                'class' => \Mautic\CampaignBundle\Form\Type\CampaignEventJumpToEventType::class,
                'alias' => 'campaignevent_jump_to_event',
            ],
            'mautic.campaign.type.canvassettings'       => [
                'class' => 'Mautic\CampaignBundle\Form\Type\EventCanvasSettingsType',
                'alias' => 'campaignevent_canvassettings',
            ],
            'mautic.campaign.type.leadsource'           => [
                'class'     => 'Mautic\CampaignBundle\Form\Type\CampaignLeadSourceType',
                'arguments' => 'mautic.factory',
                'alias'     => 'campaign_leadsource',
            ],
            'mautic.form.type.campaignconfig'           => [
                'class'     => 'Mautic\CampaignBundle\Form\Type\ConfigType',
                'arguments' => 'translator',
                'alias'     => 'campaignconfig',
            ],
        ],
        'models'       => [
            'mautic.campaign.model.campaign'  => [
                'class'     => \Mautic\CampaignBundle\Model\CampaignModel::class,
                'arguments' => [
                    'mautic.lead.model.lead',
                    'mautic.lead.model.list',
                    'mautic.form.model.form',
                    'mautic.campaign.event_collector',
                    'mautic.campaign.helper.removed_contact_tracker',
                    'mautic.campaign.membership.manager',
                    'mautic.campaign.membership.builder',
                ],
            ],
            'mautic.campaign.model.event'     => [
                'class'     => \Mautic\CampaignBundle\Model\EventModel::class,
                'arguments' => [
                    'mautic.user.model.user',
                    'mautic.core.model.notification',
                    'mautic.campaign.model.campaign',
                    'mautic.lead.model.lead',
                    'mautic.helper.ip_lookup',
                    'mautic.campaign.executioner.realtime',
                    'mautic.campaign.executioner.kickoff',
                    'mautic.campaign.executioner.scheduled',
                    'mautic.campaign.executioner.inactive',
                    'mautic.campaign.event_executioner',
                    'mautic.campaign.event_collector',
                    'mautic.campaign.dispatcher.action',
                    'mautic.campaign.dispatcher.condition',
                    'mautic.campaign.dispatcher.decision',
                    'mautic.campaign.repository.lead_event_log',
                ],
            ],
            'mautic.campaign.model.event_log' => [
                'class'     => 'Mautic\CampaignBundle\Model\EventLogModel',
                'arguments' => [
                    'mautic.campaign.model.event',
                    'mautic.campaign.model.campaign',
                    'mautic.helper.ip_lookup',
                ],
            ],
        ],
        'repositories' => [
            'mautic.campaign.repository.campaign' => [
                'class'     => Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [
                    \Mautic\CampaignBundle\Entity\Campaign::class,
                ],
            ],
            'mautic.campaign.repository.lead' => [
                'class'     => Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [
                    \Mautic\CampaignBundle\Entity\Lead::class,
                ],
            ],
            'mautic.campaign.repository.event' => [
                'class'     => Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [
                    \Mautic\CampaignBundle\Entity\Event::class,
                ],
            ],
            'mautic.campaign.repository.lead_event_log' => [
                'class'     => Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [
                    \Mautic\CampaignBundle\Entity\LeadEventLog::class,
                ],
            ],
        ],
        'execution'    => [
            'mautic.campaign.contact_finder.kickoff'  => [
                'class'     => \Mautic\CampaignBundle\Executioner\ContactFinder\KickoffContactFinder::class,
                'arguments' => [
                    'mautic.lead.repository.lead',
                    'mautic.campaign.repository.campaign',
                    'monolog.logger.mautic',
                ],
            ],
            'mautic.campaign.contact_finder.scheduled'  => [
                'class'     => \Mautic\CampaignBundle\Executioner\ContactFinder\ScheduledContactFinder::class,
                'arguments' => [
                    'mautic.lead.repository.lead',
                    'monolog.logger.mautic',
                ],
            ],
            'mautic.campaign.contact_finder.inactive'     => [
                'class'     => \Mautic\CampaignBundle\Executioner\ContactFinder\InactiveContactFinder::class,
                'arguments' => [
                    'mautic.lead.repository.lead',
                    'mautic.campaign.repository.campaign',
                    'mautic.campaign.repository.lead',
                    'monolog.logger.mautic',
                ],
            ],
            'mautic.campaign.dispatcher.action'        => [
                'class'     => \Mautic\CampaignBundle\Executioner\Dispatcher\ActionDispatcher::class,
                'arguments' => [
                    'event_dispatcher',
                    'monolog.logger.mautic',
                    'mautic.campaign.scheduler',
                    'mautic.campaign.helper.notification',
                    'mautic.campaign.legacy_event_dispatcher',
                ],
            ],
            'mautic.campaign.dispatcher.condition'        => [
                'class'     => \Mautic\CampaignBundle\Executioner\Dispatcher\ConditionDispatcher::class,
                'arguments' => [
                    'event_dispatcher',
                ],
            ],
            'mautic.campaign.dispatcher.decision'        => [
                'class'     => \Mautic\CampaignBundle\Executioner\Dispatcher\DecisionDispatcher::class,
                'arguments' => [
                    'event_dispatcher',
                    'mautic.campaign.legacy_event_dispatcher',
                ],
            ],
            'mautic.campaign.event_logger' => [
                'class'     => \Mautic\CampaignBundle\Executioner\Logger\EventLogger::class,
                'arguments' => [
                    'mautic.helper.ip_lookup',
                    'mautic.tracker.contact',
                    'mautic.campaign.repository.lead_event_log',
                    'mautic.campaign.repository.lead',
                ],
            ],
            'mautic.campaign.event_collector' => [
                'class'     => \Mautic\CampaignBundle\EventCollector\EventCollector::class,
                'arguments' => [
                    'translator',
                    'event_dispatcher',
                ],
            ],
            'mautic.campaign.scheduler.datetime'      => [
                'class'     => \Mautic\CampaignBundle\Executioner\Scheduler\Mode\DateTime::class,
                'arguments' => [
                    'monolog.logger.mautic',
                ],
            ],
            'mautic.campaign.scheduler.interval'      => [
                'class'     => \Mautic\CampaignBundle\Executioner\Scheduler\Mode\Interval::class,
                'arguments' => [
                    'monolog.logger.mautic',
                    'mautic.helper.core_parameters',
                ],
            ],
            'mautic.campaign.scheduler'               => [
                'class'     => \Mautic\CampaignBundle\Executioner\Scheduler\EventScheduler::class,
                'arguments' => [
                    'monolog.logger.mautic',
                    'mautic.campaign.event_logger',
                    'mautic.campaign.scheduler.interval',
                    'mautic.campaign.scheduler.datetime',
                    'mautic.campaign.event_collector',
                    'event_dispatcher',
                    'mautic.helper.core_parameters',
                ],
            ],
            'mautic.campaign.executioner.action' => [
                'class'     => \Mautic\CampaignBundle\Executioner\Event\ActionExecutioner::class,
                'arguments' => [
                    'mautic.campaign.dispatcher.action',
                    'mautic.campaign.event_logger',
                ],
            ],
            'mautic.campaign.executioner.condition' => [
                'class'     => \Mautic\CampaignBundle\Executioner\Event\ConditionExecutioner::class,
                'arguments' => [
                    'mautic.campaign.dispatcher.condition',
                ],
            ],
            'mautic.campaign.executioner.decision' => [
                'class'     => \Mautic\CampaignBundle\Executioner\Event\DecisionExecutioner::class,
                'arguments' => [
                    'mautic.campaign.event_logger',
                    'mautic.campaign.dispatcher.decision',
                ],
            ],
            'mautic.campaign.event_executioner' => [
                'class'     => \Mautic\CampaignBundle\Executioner\EventExecutioner::class,
                'arguments' => [
                    'mautic.campaign.event_collector',
                    'mautic.campaign.event_logger',
                    'mautic.campaign.executioner.action',
                    'mautic.campaign.executioner.condition',
                    'mautic.campaign.executioner.decision',
                    'monolog.logger.mautic',
                    'mautic.campaign.scheduler',
                    'mautic.campaign.helper.removed_contact_tracker',
                    'mautic.campaign.repository.lead',
                ],
            ],
            'mautic.campaign.executioner.kickoff'     => [
                'class'     => \Mautic\CampaignBundle\Executioner\KickoffExecutioner::class,
                'arguments' => [
                    'monolog.logger.mautic',
                    'mautic.campaign.contact_finder.kickoff',
                    'translator',
                    'mautic.campaign.event_executioner',
                    'mautic.campaign.scheduler',
                ],
            ],
            'mautic.campaign.executioner.scheduled'     => [
                'class'     => \Mautic\CampaignBundle\Executioner\ScheduledExecutioner::class,
                'arguments' => [
                    'mautic.campaign.repository.lead_event_log',
                    'monolog.logger.mautic',
                    'translator',
                    'mautic.campaign.event_executioner',
                    'mautic.campaign.scheduler',
                    'mautic.campaign.contact_finder.scheduled',
                ],
            ],
            'mautic.campaign.executioner.realtime'     => [
                'class'     => \Mautic\CampaignBundle\Executioner\RealTimeExecutioner::class,
                'arguments' => [
                    'monolog.logger.mautic',
                    'mautic.lead.model.lead',
                    'mautic.campaign.repository.event',
                    'mautic.campaign.event_executioner',
                    'mautic.campaign.executioner.decision',
                    'mautic.campaign.event_collector',
                    'mautic.campaign.scheduler',
                    'mautic.tracker.contact',
                    'mautic.campaign.repository.lead',
                ],
            ],
            'mautic.campaign.executioner.inactive'     => [
                'class'     => \Mautic\CampaignBundle\Executioner\InactiveExecutioner::class,
                'arguments' => [
                    'mautic.campaign.contact_finder.inactive',
                    'monolog.logger.mautic',
                    'translator',
                    'mautic.campaign.scheduler',
                    'mautic.campaign.helper.inactivity',
                    'mautic.campaign.event_executioner',
                ],
            ],
            'mautic.campaign.helper.inactivity' => [
                'class'     => \Mautic\CampaignBundle\Executioner\Helper\InactiveHelper::class,
                'arguments' => [
                    'mautic.campaign.scheduler',
                    'mautic.campaign.contact_finder.inactive',
                    'mautic.campaign.repository.lead_event_log',
                    'mautic.campaign.repository.event',
                    'monolog.logger.mautic',
                ],
            ],
            'mautic.campaign.helper.removed_contact_tracker' => [
                'class' => \Mautic\CampaignBundle\Helper\RemovedContactTracker::class,
            ],
            'mautic.campaign.helper.notification' => [
                'class'     => \Mautic\CampaignBundle\Executioner\Helper\NotificationHelper::class,
                'arguments' => [
                    'mautic.user.model.user',
                    'mautic.core.model.notification',
                    'translator',
                    'router',
                ],
            ],
            // @deprecated 2.13.0 for BC support; to be removed in 3.0
            'mautic.campaign.legacy_event_dispatcher' => [
                'class'     => \Mautic\CampaignBundle\Executioner\Dispatcher\LegacyEventDispatcher::class,
                'arguments' => [
                    'event_dispatcher',
                    'mautic.campaign.scheduler',
                    'monolog.logger.mautic',
                    'mautic.lead.model.lead',
                    'mautic.campaign.helper.notification',
                    'mautic.factory',
                ],
            ],
        ],
        'membership' => [
            'mautic.campaign.membership.adder' => [
                'class'     => \Mautic\CampaignBundle\Membership\Action\Adder::class,
                'arguments' => [
                    'mautic.campaign.repository.lead',
                    'mautic.campaign.repository.lead_event_log',
                ],
            ],
            'mautic.campaign.membership.remover' => [
                'class'     => \Mautic\CampaignBundle\Membership\Action\Remover::class,
                'arguments' => [
                    'mautic.campaign.repository.lead',
                    'mautic.campaign.repository.lead_event_log',
                    'translator',
                    'mautic.helper.template.date',
                ],
            ],
            'mautic.campaign.membership.event_dispatcher' => [
                'class'     => \Mautic\CampaignBundle\Membership\EventDispatcher::class,
                'arguments' => [
                    'event_dispatcher',
                ],
            ],
            'mautic.campaign.membership.manager' => [
                'class'     => \Mautic\CampaignBundle\Membership\MembershipManager::class,
                'arguments' => [
                    'mautic.campaign.membership.adder',
                    'mautic.campaign.membership.remover',
                    'mautic.campaign.membership.event_dispatcher',
                    'mautic.campaign.repository.lead',
                    'monolog.logger.mautic',
                ],
            ],
            'mautic.campaign.membership.builder' => [
                'class'     => \Mautic\CampaignBundle\Membership\MembershipBuilder::class,
                'arguments' => [
                    'mautic.campaign.membership.manager',
                    'mautic.campaign.repository.lead',
                    'mautic.lead.repository.lead',
                    'event_dispatcher',
                    'translator',
                ],
            ],
        ],
        'commands' => [
            'mautic.campaign.command.trigger' => [
                'class'     => \Mautic\CampaignBundle\Command\TriggerCampaignCommand::class,
                'arguments' => [
                    'mautic.campaign.repository.campaign',
                    'event_dispatcher',
                    'translator',
                    'mautic.campaign.executioner.kickoff',
                    'mautic.campaign.executioner.scheduled',
                    'mautic.campaign.executioner.inactive',
                    'monolog.logger.mautic',
                    'mautic.helper.template.formatter',
                ],
                'tag' => 'console.command',
            ],
            'mautic.campaign.command.execute' => [
                'class'     => \Mautic\CampaignBundle\Command\ExecuteEventCommand::class,
                'arguments' => [
                    'mautic.campaign.executioner.scheduled',
                    'translator',
                    'mautic.helper.template.formatter',
                ],
                'tag' => 'console.command',
            ],
            'mautic.campaign.command.validate' => [
                'class'     => \Mautic\CampaignBundle\Command\ValidateEventCommand::class,
                'arguments' => [
                    'mautic.campaign.executioner.inactive',
                    'translator',
                    'mautic.helper.template.formatter',
                ],
                'tag' => 'console.command',
            ],
            'mautic.campaign.command.update' => [
                'class'     => \Mautic\CampaignBundle\Command\UpdateLeadCampaignsCommand::class,
                'arguments' => [
                    'mautic.campaign.repository.campaign',
                    'translator',
                    'mautic.campaign.membership.builder',
                    'monolog.logger.mautic',
                    'mautic.helper.template.formatter',
                ],
                'tag' => 'console.command',
            ],
        ],
    ],
    'parameters' => [
        'campaign_time_wait_on_event_false' => 'PT1H',
    ],
];
