<?php

return [
    'routes' => [
        'main' => [
            'mautic_campaignevent_action'  => [
                'path'       => '/campaigns/events/{objectAction}/{objectId}',
                'controller' => 'Mautic\CampaignBundle\Controller\EventController::executeAction',
            ],
            'mautic_campaignsource_action' => [
                'path'       => '/campaigns/sources/{objectAction}/{objectId}',
                'controller' => 'Mautic\CampaignBundle\Controller\SourceController::executeAction',
            ],
            'mautic_campaign_index'        => [
                'path'       => '/campaigns/{page}',
                'controller' => 'Mautic\CampaignBundle\Controller\CampaignController::indexAction',
            ],
            'mautic_campaign_action'       => [
                'path'       => '/campaigns/{objectAction}/{objectId}',
                'controller' => 'Mautic\CampaignBundle\Controller\CampaignController::executeAction',
            ],
            'mautic_campaign_contacts'     => [
                'path'       => '/campaigns/view/{objectId}/contact/{page}',
                'controller' => 'Mautic\CampaignBundle\Controller\CampaignController::contactsAction',
            ],
            'mautic_campaign_preview'      => [
                'path'       => '/campaign/preview/{objectId}',
                'controller' => 'Mautic\EmailBundle\Controller\PublicController::previewAction',
            ],
            'mautic_campaign_map_stats' => [
                'path'       => '/campaign-map-stats/{objectId}/{dateFrom}/{dateTo}',
                'controller' => 'Mautic\CampaignBundle\Controller\CampaignMapStatsController::viewAction',
            ],
            'mautic_campaign_metrics_email_weekdays' => [
                'path'       => '/campaign/metrics/email-weekdays/{objectId}/{dateFrom}/{dateTo}',
                'controller' => 'Mautic\CampaignBundle\Controller\CampaignMetricsController::emailWeekdaysAction',
            ],
            'mautic_campaign_metrics_email_hours' => [
                'path'       => '/campaign/metrics/email-hours/{objectId}/{dateFrom}/{dateTo}',
                'controller' => 'Mautic\CampaignBundle\Controller\CampaignMetricsController::emailHoursAction',
            ],
        ],
        'api'  => [
            'mautic_api_campaignsstandard'            => [
                'standard_entity' => true,
                'name'            => 'campaigns',
                'path'            => '/campaigns',
                'controller'      => Mautic\CampaignBundle\Controller\Api\CampaignApiController::class,
            ],
            'mautic_api_campaigneventsstandard'       => [
                'standard_entity'     => true,
                'supported_endpoints' => [
                    'getone',
                    'getall',
                ],
                'name'                => 'events',
                'path'                => '/campaigns/events',
                'controller'          => Mautic\CampaignBundle\Controller\Api\EventApiController::class,
            ],
            'mautic_api_campaigns_events_contact'     => [
                'path'       => '/campaigns/events/contact/{contactId}',
                'controller' => 'Mautic\CampaignBundle\Controller\Api\EventLogApiController::getContactEventsAction',
                'method'     => 'GET',
            ],
            'mautic_api_campaigns_edit_contact_event' => [
                'path'       => '/campaigns/events/{eventId}/contact/{contactId}/edit',
                'controller' => 'Mautic\CampaignBundle\Controller\Api\EventLogApiController::editContactEventAction',
                'method'     => 'PUT',
            ],
            'mautic_api_campaigns_batchedit_events'   => [
                'path'       => '/campaigns/events/batch/edit',
                'controller' => 'Mautic\CampaignBundle\Controller\Api\EventLogApiController::editEventsAction',
                'method'     => 'PUT',
            ],
            'mautic_api_campaign_contact_events'      => [
                'path'       => '/campaigns/{campaignId}/events/contact/{contactId}',
                'controller' => 'Mautic\CampaignBundle\Controller\Api\EventLogApiController::getContactEventsAction',
                'method'     => 'GET',
            ],
            'mautic_api_campaigngetcontacts'          => [
                'path'       => '/campaigns/{id}/contacts',
                'controller' => 'Mautic\CampaignBundle\Controller\Api\CampaignApiController::getContactsAction',
            ],
            'mautic_api_campaignaddcontact'           => [
                'path'       => '/campaigns/{id}/contact/{leadId}/add',
                'controller' => 'Mautic\CampaignBundle\Controller\Api\CampaignApiController::addLeadAction',
                'method'     => 'POST',
            ],
            'mautic_api_campaignremovecontact'        => [
                'path'       => '/campaigns/{id}/contact/{leadId}/remove',
                'controller' => 'Mautic\CampaignBundle\Controller\Api\CampaignApiController::removeLeadAction',
                'method'     => 'POST',
            ],
            'mautic_api_contact_clone_campaign' => [
                'path'       => '/campaigns/clone/{campaignId}',
                'controller' => 'Mautic\CampaignBundle\Controller\Api\CampaignApiController::cloneCampaignAction',
                'method'     => 'POST',
            ],
        ],
    ],

    'menu' => [
        'main' => [
            'mautic.campaign.menu.index' => [
                'iconClass' => 'ri-megaphone-fill',
                'route'     => 'mautic_campaign_index',
                'access'    => 'campaign:campaigns:view',
                'priority'  => 50,
            ],
        ],
    ],

    'categories' => [
        'campaign' => [
            'class' => Mautic\CampaignBundle\Entity\Campaign::class,
        ],
    ],

    'services' => [
        'execution'    => [
            'mautic.campaign.contact_finder.kickoff'  => [
                'class'     => Mautic\CampaignBundle\Executioner\ContactFinder\KickoffContactFinder::class,
                'arguments' => [
                    'mautic.lead.repository.lead',
                    'mautic.campaign.repository.campaign',
                    'monolog.logger.mautic',
                ],
            ],
            'mautic.campaign.contact_finder.scheduled'  => [
                'class'     => Mautic\CampaignBundle\Executioner\ContactFinder\ScheduledContactFinder::class,
                'arguments' => [
                    'mautic.lead.repository.lead',
                    'monolog.logger.mautic',
                ],
            ],
            'mautic.campaign.contact_finder.inactive'     => [
                'class'     => Mautic\CampaignBundle\Executioner\ContactFinder\InactiveContactFinder::class,
                'arguments' => [
                    'mautic.lead.repository.lead',
                    'mautic.campaign.repository.lead',
                    'monolog.logger.mautic',
                ],
            ],
            'mautic.campaign.dispatcher.action'        => [
                'class'     => Mautic\CampaignBundle\Executioner\Dispatcher\ActionDispatcher::class,
                'arguments' => [
                    'event_dispatcher',
                    'monolog.logger.mautic',
                    'mautic.campaign.scheduler',
                    'mautic.campaign.legacy_event_dispatcher',
                ],
            ],
            'mautic.campaign.dispatcher.condition'        => [
                'class'     => Mautic\CampaignBundle\Executioner\Dispatcher\ConditionDispatcher::class,
                'arguments' => [
                    'event_dispatcher',
                ],
            ],
            'mautic.campaign.dispatcher.decision'        => [
                'class'     => Mautic\CampaignBundle\Executioner\Dispatcher\DecisionDispatcher::class,
                'arguments' => [
                    'event_dispatcher',
                    'mautic.campaign.legacy_event_dispatcher',
                ],
            ],
            'mautic.campaign.event_logger' => [
                'class'     => Mautic\CampaignBundle\Executioner\Logger\EventLogger::class,
                'arguments' => [
                    'mautic.helper.ip_lookup',
                    'mautic.tracker.contact',
                    'mautic.campaign.repository.lead_event_log',
                    'mautic.campaign.repository.lead',
                    'mautic.campaign.model.summary',
                ],
            ],
            'mautic.campaign.event_collector' => [
                'class'     => Mautic\CampaignBundle\EventCollector\EventCollector::class,
                'arguments' => [
                    'translator',
                    'event_dispatcher',
                ],
            ],
            'mautic.campaign.scheduler.datetime'      => [
                'class'     => Mautic\CampaignBundle\Executioner\Scheduler\Mode\DateTime::class,
                'arguments' => [
                    'monolog.logger.mautic',
                ],
            ],
            'mautic.campaign.scheduler.interval'      => [
                'class'     => Mautic\CampaignBundle\Executioner\Scheduler\Mode\Interval::class,
                'arguments' => [
                    'monolog.logger.mautic',
                    'mautic.helper.core_parameters',
                ],
            ],
            'mautic.campaign.scheduler'               => [
                'class'     => Mautic\CampaignBundle\Executioner\Scheduler\EventScheduler::class,
                'arguments' => [
                    'monolog.logger.mautic',
                    'mautic.campaign.event_logger',
                    'mautic.campaign.scheduler.interval',
                    'mautic.campaign.scheduler.datetime',
                    'mautic.campaign.scheduler.optimized',
                    'mautic.campaign.event_collector',
                    'event_dispatcher',
                    'mautic.helper.core_parameters',
                ],
            ],
            'mautic.campaign.executioner.action' => [
                'class'     => Mautic\CampaignBundle\Executioner\Event\ActionExecutioner::class,
                'arguments' => [
                    'mautic.campaign.dispatcher.action',
                    'mautic.campaign.event_logger',
                ],
            ],
            'mautic.campaign.executioner.condition' => [
                'class'     => Mautic\CampaignBundle\Executioner\Event\ConditionExecutioner::class,
                'arguments' => [
                    'mautic.campaign.dispatcher.condition',
                ],
            ],
            'mautic.campaign.executioner.decision' => [
                'class'     => Mautic\CampaignBundle\Executioner\Event\DecisionExecutioner::class,
                'arguments' => [
                    'mautic.campaign.event_logger',
                    'mautic.campaign.dispatcher.decision',
                ],
            ],
            'mautic.campaign.event_executioner' => [
                'class'     => Mautic\CampaignBundle\Executioner\EventExecutioner::class,
                'arguments' => [
                    'mautic.campaign.event_collector',
                    'mautic.campaign.event_logger',
                    'mautic.campaign.executioner.action',
                    'mautic.campaign.executioner.condition',
                    'mautic.campaign.executioner.decision',
                    'monolog.logger.mautic',
                    'mautic.campaign.scheduler',
                    'mautic.campaign.helper.removed_contact_tracker',
                ],
            ],
            'mautic.campaign.executioner.kickoff'     => [
                'class'     => Mautic\CampaignBundle\Executioner\KickoffExecutioner::class,
                'arguments' => [
                    'monolog.logger.mautic',
                    'mautic.campaign.contact_finder.kickoff',
                    'translator',
                    'mautic.campaign.event_executioner',
                    'mautic.campaign.scheduler',
                ],
            ],
            'mautic.campaign.executioner.realtime'     => [
                'class'     => Mautic\CampaignBundle\Executioner\RealTimeExecutioner::class,
                'arguments' => [
                    'monolog.logger.mautic',
                    'mautic.lead.model.lead',
                    'mautic.campaign.repository.event',
                    'mautic.campaign.event_executioner',
                    'mautic.campaign.executioner.decision',
                    'mautic.campaign.event_collector',
                    'mautic.campaign.scheduler',
                    'mautic.tracker.contact',
                    'mautic.campaign.helper.decision',
                ],
            ],
            'mautic.campaign.helper.decision' => [
                'class'     => Mautic\CampaignBundle\Executioner\Helper\DecisionHelper::class,
                'arguments' => [
                    'mautic.campaign.repository.lead',
                ],
            ],
            'mautic.campaign.helper.inactivity' => [
                'class'     => Mautic\CampaignBundle\Executioner\Helper\InactiveHelper::class,
                'arguments' => [
                    'mautic.campaign.scheduler',
                    'mautic.campaign.contact_finder.inactive',
                    'mautic.campaign.repository.lead_event_log',
                    'mautic.campaign.repository.event',
                    'monolog.logger.mautic',
                    'mautic.campaign.helper.decision',
                ],
            ],
            'mautic.campaign.helper.removed_contact_tracker' => [
                'class' => Mautic\CampaignBundle\Helper\RemovedContactTracker::class,
            ],
            'mautic.campaign.helper.notification' => [
                'class'     => Mautic\CampaignBundle\Executioner\Helper\NotificationHelper::class,
                'arguments' => [
                    'mautic.user.model.user',
                    'mautic.core.model.notification',
                    'translator',
                    'router',
                    'mautic.helper.core_parameters',
                ],
            ],
            // @deprecated 2.13.0 for BC support; to be removed in 3.0
            'mautic.campaign.legacy_event_dispatcher' => [
                'class'     => Mautic\CampaignBundle\Executioner\Dispatcher\LegacyEventDispatcher::class,
                'arguments' => [
                    'event_dispatcher',
                    'mautic.campaign.scheduler',
                    'monolog.logger.mautic',
                    'mautic.tracker.contact',
                ],
            ],
        ],
        'membership' => [
            'mautic.campaign.membership.adder' => [
                'class'     => Mautic\CampaignBundle\Membership\Action\Adder::class,
                'arguments' => [
                    'mautic.campaign.repository.lead',
                    'mautic.campaign.repository.lead_event_log',
                ],
            ],
            'mautic.campaign.membership.remover' => [
                'class'     => Mautic\CampaignBundle\Membership\Action\Remover::class,
                'arguments' => [
                    'mautic.campaign.repository.lead',
                    'mautic.campaign.repository.lead_event_log',
                    'translator',
                    'mautic.helper.twig.date',
                ],
            ],
            'mautic.campaign.membership.event_dispatcher' => [
                'class'     => Mautic\CampaignBundle\Membership\EventDispatcher::class,
                'arguments' => [
                    'event_dispatcher',
                ],
            ],
            'mautic.campaign.membership.manager' => [
                'class'     => Mautic\CampaignBundle\Membership\MembershipManager::class,
                'arguments' => [
                    'mautic.campaign.membership.adder',
                    'mautic.campaign.membership.remover',
                    'mautic.campaign.membership.event_dispatcher',
                    'mautic.campaign.repository.lead',
                    'monolog.logger.mautic',
                ],
            ],
            'mautic.campaign.membership.builder' => [
                'class'     => Mautic\CampaignBundle\Membership\MembershipBuilder::class,
                'arguments' => [
                    'mautic.campaign.membership.manager',
                    'mautic.campaign.repository.lead',
                    'mautic.lead.repository.lead',
                    'translator',
                ],
            ],
        ],
        'services' => [
            'mautic.campaign.service.campaign'=> [
                /** @phpstan-ignore-next-line */
                'class'     => Mautic\CampaignBundle\Service\Campaign::class,
                'arguments' => [
                    'mautic.campaign.repository.campaign',
                    'mautic.email.repository.email',
                ],
            ],
        ],
        'fixtures' => [
            'mautic.campaign.fixture.campaign' => [
                'class'    => Mautic\CampaignBundle\DataFixtures\ORM\CampaignData::class,
                'tag'      => Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass::FIXTURE_TAG,
                'optional' => true,
            ],
        ],
    ],
    'parameters' => [
        'campaign_time_wait_on_event_false'                                                     => 'PT1H',
        'campaign_use_summary'                                                                  => 0,
        'campaign_by_range'                                                                     => 0,
        'delete_campaign_event_log_in_background'                                               => false,
        'campaign_email_stats_enabled'                                                          => true,
        'peak_interaction_timer_cache_timeout'                                                  => Mautic\LeadBundle\Services\PeakInteractionTimer::DEFAULT_CACHE_TIMEOUT,
        'peak_interaction_timer_best_default_hour_start'                                        => Mautic\LeadBundle\Services\PeakInteractionTimer::DEFAULT_BEST_HOUR_START,
        'peak_interaction_timer_best_default_hour_end'                                          => Mautic\LeadBundle\Services\PeakInteractionTimer::DEFAULT_BEST_HOUR_END,
        'peak_interaction_timer_best_default_days'                                              => Mautic\LeadBundle\Services\PeakInteractionTimer::DEFAULT_BEST_DAYS,
        'peak_interaction_timer_fetch_interactions_from'                                        => Mautic\LeadBundle\Services\PeakInteractionTimer::DEFAULT_FETCH_INTERACTIONS_FROM,
        'peak_interaction_timer_fetch_limit'                                                    => Mautic\LeadBundle\Services\PeakInteractionTimer::DEFAULT_FETCH_LIMIT,
        'peak_interaction_timer_max_optimal_days'                                               => Mautic\LeadBundle\Services\PeakInteractionTimer::DEFAULT_MAX_OPTIMAL_DAYS,
    ],
];
