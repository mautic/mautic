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
                'controller'      => \Mautic\WebhookBundle\Controller\Api\WebhookApiController::class,
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
                    'iconClass' => 'fa-exchange',
                    'access'    => ['webhook:webhooks:viewown', 'webhook:webhooks:viewother'],
                    'route'     => 'mautic_webhook_index',
                ],
            ],
        ],
    ],

    'services' => [
        'others' => [
            'mautic.webhook.notificator.webhookkillnotificator' => [
                'class'     => \Mautic\WebhookBundle\Notificator\WebhookKillNotificator::class,
                'arguments' => [
                    'translator',
                    'router',
                    'mautic.core.model.notification',
                    'doctrine.orm.entity_manager',
                    'mautic.helper.mailer',
                    'mautic.helper.core_parameters',
                ],
            ],
            'mautic.webhook.campaign.helper' => [
                'class'     => \Mautic\WebhookBundle\Helper\CampaignHelper::class,
                'arguments' => [
                    'mautic.http.client',
                    'mautic.lead.model.company',
                    'event_dispatcher',
                ],
            ],
            'mautic.webhook.http.client' => [
                'class'     => \Mautic\WebhookBundle\Http\Client::class,
                'arguments' => [
                    'mautic.helper.core_parameters',
                    'mautic.http.client',
                ],
            ],
        ],
        'repositories' => [
            'mautic.webhook.repository.queue' => [
                'class'     => Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [
                    \Mautic\WebhookBundle\Entity\WebhookQueue::class,
                ],
            ],
        ],
    ],

    'parameters' => [
        'webhook_limit'                        => 10, // How many entities can be sent in one webhook
        'webhook_time_limit'                   => 600, // How long the webhook processing can run in seconds
        'webhook_log_max'                      => 1000, // How many recent logs to keep
        'clean_webhook_logs_in_background'     => false,
        'webhook_disable_limit'                => 100, // How many times the webhook response can fail until the webhook will be unpublished
        'webhook_timeout'                      => 15, // How long the CURL request can wait for response before Mautic hangs up. In seconds
        'queue_mode'                           => \Mautic\WebhookBundle\Model\WebhookModel::IMMEDIATE_PROCESS, // Trigger the webhook immediately or queue it for faster response times
        'events_orderby_dir'                   => \Doctrine\Common\Collections\Criteria::ASC, // Order the queued events chronologically or the other way around
        'webhook_email_details'                => true, // If enabled, email related webhooks send detailed data
    ],
];
