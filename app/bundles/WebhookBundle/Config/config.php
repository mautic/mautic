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
            'mautic_webhook_index' => [
                'path'       => '/webhooks/{page}',
                'controller' => 'MauticWebhookBundle:Webhook:index',
            ],
            'mautic_webhook_action' => [
                'path'       => '/webhooks/{objectAction}/{objectId}',
                'controller' => 'MauticWebhookBundle:Webhook:execute',
            ],
        ],
        'api' => [
            'mautic_api_webhookstandard' => [
                'standard_entity' => true,
                'name'            => 'hooks',
                'path'            => '/hooks',
                'controller'      => 'MauticWebhookBundle:Api\WebhookApi',
            ],
            'mautic_api_webhookevents' => [
                'path'       => '/hooks/triggers',
                'controller' => 'MauticWebhookBundle:Api\WebhookApi:getTriggers',
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
        'forms' => [
            'mautic.form.type.webhook' => [
                'class'     => \Mautic\WebhookBundle\Form\Type\WebhookType::class,
            ],
            'mautic.form.type.webhookconfig' => [
                'class' => \Mautic\WebhookBundle\Form\Type\ConfigType::class,
            ],
            'mautic.campaign.type.action.sendwebhook' => [
                'class'     => \Mautic\WebhookBundle\Form\Type\CampaignEventSendWebhookType::class,
                'arguments' => [
                    'arguments' => 'translator',
                ],
            ],
            'mautic.webhook.notificator.webhookkillnotificator' => [
                'class'     => \Mautic\WebhookBundle\Notificator\WebhookKillNotificator::class,
                'arguments' => [
                    'translator',
                    'router',
                    'mautic.core.model.notification',
                    'doctrine.orm.entity_manager',
                    'mautic.helper.mailer',
                ],
            ],
        ],
        'events' => [
            'mautic.webhook.config.subscriber' => [
                'class' => \Mautic\WebhookBundle\EventListener\ConfigSubscriber::class,
            ],
            'mautic.webhook.audit.subscriber' => [
                'class'     => \Mautic\WebhookBundle\EventListener\WebhookSubscriber::class,
                'arguments' => [
                    'mautic.helper.ip_lookup',
                    'mautic.core.model.auditlog',
                    'mautic.webhook.notificator.webhookkillnotificator',
                ],
            ],
            'mautic.webhook.stats.subscriber' => [
                'class'     => \Mautic\WebhookBundle\EventListener\StatsSubscriber::class,
                'arguments' => [
                    'mautic.security',
                    'doctrine.orm.entity_manager',
                ],
            ],
            'mautic.webhook.campaign.subscriber' => [
                'class'     => \Mautic\WebhookBundle\EventListener\CampaignSubscriber::class,
                'arguments' => [
                    'mautic.webhook.campaign.helper',
                ],
            ],
        ],
        'models' => [
            'mautic.webhook.model.webhook' => [
                'class'     => \Mautic\WebhookBundle\Model\WebhookModel::class,
                'arguments' => [
                    'mautic.helper.core_parameters',
                    'jms_serializer',
                    'mautic.webhook.http.client',
                    'event_dispatcher',
                ],
            ],
        ],
        'others' => [
            'mautic.webhook.campaign.helper' => [
                'class'     => \Mautic\WebhookBundle\Helper\CampaignHelper::class,
                'arguments' => [
                    'mautic.http.connector',
                    'mautic.lead.model.company',
                    'event_dispatcher',
                ],
            ],
            'mautic.webhook.http.client' => [
                'class'     => \Mautic\WebhookBundle\Http\Client::class,
                'arguments' => [
                    'mautic.helper.core_parameters',
                    'mautic.guzzle.client',
                ],
            ],
        ],
    ],

    'parameters' => [
        'webhook_limit'         => 10, // How many entities can be sent in one webhook
        'webhook_time_limit'    => 600, // How long the webhook processing can run in seconds
        'webhook_log_max'       => 1000, // How many recent logs to keep
        'webhook_disable_limit' => 100, // How many times the webhook response can fail until the webhook will be unpublished
        'webhook_timeout'       => 15, // How long the CURL request can wait for response before Mautic hangs up. In seconds
        'queue_mode'            => \Mautic\WebhookBundle\Model\WebhookModel::IMMEDIATE_PROCESS, // Trigger the webhook immediately or queue it for faster response times
        'events_orderby_dir'    => \Doctrine\Common\Collections\Criteria::ASC, // Order the queued events chronologically or the other way around
    ],
];
