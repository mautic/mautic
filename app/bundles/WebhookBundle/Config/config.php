<?php

return [
    'routes' => [
        'main' => [
            'mautic_webhook_index' => [
                'path'       => '/webhooks/{page}',
                'controller' => 'Mautic\WebhookBundle\Controller\WebhookController::indexAction',
            ],
            'mautic_webhook_action' => [
                'path'       => '/webhooks/{objectAction}/{objectId}',
                'controller' => 'Mautic\WebhookBundle\Controller\WebhookController::executeAction',
            ],
        ],
        'api' => [
            'mautic_api_webhookstandard' => [
                'standard_entity' => true,
                'name'            => 'hooks',
                'path'            => '/hooks',
                'controller'      => Mautic\WebhookBundle\Controller\Api\WebhookApiController::class,
            ],
            'mautic_api_webhookevents' => [
                'path'       => '/hooks/triggers',
                'controller' => 'Mautic\WebhookBundle\Controller\Api\WebhookApiController::getTriggersAction',
            ],
        ],
    ],

    'menu' => [
        'admin' => [
            'items' => [
                'mautic.webhook.webhooks' => [
                    'id'        => 'mautic_webhook_root',
                    'access'    => ['webhook:webhooks:viewown', 'webhook:webhooks:viewother'],
                    'route'     => 'mautic_webhook_index',
                    'parent'    => 'mautic.core.integrations',
                    'iconClass' => 'ri-webhook-fill',
                ],
            ],
        ],
    ],

    'services' => [
        'others' => [
            'mautic.webhook.campaign.helper' => [
                'class'     => Mautic\WebhookBundle\Helper\CampaignHelper::class,
                'arguments' => [
                    'mautic.http.client',
                    'mautic.lead.model.company',
                    'event_dispatcher',
                ],
            ],
        ],
    ],

    'parameters' => [
        'webhook_limit'                            => 10, // How many entities can be sent in one webhook
        'webhook_time_limit'                       => 600, // How long the webhook processing can run in seconds
        'webhook_log_max'                          => 1000, // How many recent logs to keep
        'webhook_health_check_time'                => 300, // Retry webhook after this time once it marked it as unhealthy in seconds.
        'webhook_retry_delay'                      => 3600, // Retry webhook_queue entry after given time after it is failed in seconds.
        'clean_webhook_logs_in_background'         => false,
        'webhook_disable_limit'                    => 100, // How many times the webhook response can fail until the webhook will be unpublished
        'webhook_timeout'                          => 15, // How long the CURL request can wait for response before Mautic hangs up. In seconds
        'queue_mode'                               => Mautic\WebhookBundle\Model\WebhookModel::IMMEDIATE_PROCESS, // Trigger the webhook immediately or queue it for faster response times
        'events_orderby_dir'                       => Doctrine\Common\Collections\Order::Ascending->value, // Order the queued events chronologically or the other way around
        'webhook_email_details'                    => true, // If enabled, email related webhooks send detailed data
        'disable_auto_unpublish'                   => false, // If enabled, webhooks will not be automatically unpublished on errors
        'first_webhook_failure_notification_time'  => 3600, // 1 hour
        'webhook_failure_notification_interval'    => 86400, // 1 day
        'webhook_allowed_private_addresses'        => [],
    ],
];
