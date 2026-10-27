<?php

declare(strict_types=1);

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
            'mautic_campaign_event_stats'     => [
                'path'       => '/campaigns/event/stats/{objectId}/{dateFromValue}/{dateToValue}',
                'controller' => 'Mautic\CampaignBundle\Controller\CampaignController::eventStatsAction',
            ],
            'mautic_campaign_graph'     => [
                'path'       => '/campaigns/graph/{objectId}/{dateFrom}/{dateTo}',
                'controller' => 'Mautic\CampaignBundle\Controller\CampaignController::graphAction',
            ],
            'mautic_campaign_preview'      => [
                'path'       => '/campaign/preview/{objectId}',
                'controller' => 'Mautic\EmailBundle\Controller\PublicController::previewAction',
            ],
            'mautic_campaign_map_stats'    => [
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
            'mautic_campaign_import_index' => [
                'path'       => '/campaign/import',
                'controller' => 'Mautic\CampaignBundle\Controller\ImportController::indexAction',
            ],
            'mautic_campaign_import_action' => [
                'path'       => '/campaign/import/{objectAction}',
                'controller' => 'Mautic\CampaignBundle\Controller\ImportController::executeAction',
            ],
            'mautic_campaign_metrics_event_details' => [
                'path'       => '/campaign/metrics/event-details/{objectId}',
                'controller' => 'Mautic\CampaignBundle\Controller\CampaignMetricsController::eventDetailsAction',
            ],
        ],
        'public' => [
            'mautic_campaign_share_download' => [
                'path'         => '/campaign-share/{token}',
                'controller'   => 'Mautic\CampaignBundle\Controller\CampaignShareDownloadController::downloadAction',
                'method'       => 'GET',
                'requirements' => [
                    'token' => '[a-f0-9]{32}',
                ],
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
            'mautic_api_export_campaign' => [
                'path'       => '/campaigns/export/{campaignId}',
                'controller' => 'Mautic\CampaignBundle\Controller\Api\CampaignApiController::exportCampaignAction',
                'method'     => 'GET',
            ],
            'mautic_api_import_campaign' => [
                'path'       => '/campaigns/import',
                'controller' => 'Mautic\CampaignBundle\Controller\Api\CampaignApiController::importCampaignAction',
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
        'import_campaigns_dir'                                                                  => '%kernel.project_dir%/var/import',
        'campaigns_resume_stuck_records_after'                                                  => '2025-10-01 00:00:00',
        'campaign_republish_behavior'                                                           => Mautic\CampaignBundle\Enum\RepublishBehavior::COUNT_ALL_TIME->value,
        'campaign_event_cache_ttl'                                                              => 600, // seconds
        'campaign_contact_count_cache_ttl'                                                      => 43200, // 12 hours in seconds
        'marketplace_website_url'                                                               => '%env(default::MARKETPLACE_WEBSITE_URL)%',
    ],
];
