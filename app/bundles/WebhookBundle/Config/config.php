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
                'class'     => 'Mautic\WebhookBundle\Form\Type\WebhookType',
                'arguments' => 'translator',
                'alias'     => 'webhook',
            ],
            'mautic.form.type.webhookconfig' => [
                'class' => 'Mautic\WebhookBundle\Form\Type\ConfigType',
                'alias' => 'webhookconfig',
            ],
        ],
        'events' => [
            'mautic.webhook.config.subscriber' => [
                'class' => 'Mautic\WebhookBundle\EventListener\ConfigSubscriber',
            ],
            'mautic.webhook.audit.subscriber' => [
                'class'     => 'Mautic\WebhookBundle\EventListener\WebhookSubscriber',
                'arguments' => [
                    'mautic.helper.ip_lookup',
                    'mautic.core.model.auditlog',
                ],
            ],
            'mautic.webhook.stats.subscriber' => [
                'class'     => \Mautic\WebhookBundle\EventListener\StatsSubscriber::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                ],
            ],
        ],
        'models' => [
            'mautic.webhook.model.webhook' => [
                'class'     => 'Mautic\WebhookBundle\Model\WebhookModel',
                'arguments' => [
                    'mautic.helper.core_parameters',
                    'jms_serializer',
                ],
            ],
        ],
    ],

    'parameters' => [
        'webhook_start'   => 0,
        'webhook_limit'   => 1000,
        'webhook_log_max' => 10,
        'queue_mode'      => 'immediate_process',
    ],
];
