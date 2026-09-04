<?php

declare(strict_types=1);

return [
    'routes' => [
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
