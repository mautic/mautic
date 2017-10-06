<?php

return [
    'name'        => 'Recombee',
    'description' => 'Enables integrations with Recombee for products personalization',
    'author'      => 'kuzmany.biz',
    'version'     => '1.0.0',
    'services'    => [
        'events' => [
            'mautic.plugin.recombee.webhook.subscriber' => [
                'class'       => MauticPlugin\MauticRecombeeBundle\EventListener\WebhookSubscriber::class,
                'methodCalls' => [
                    'setWebhookModel' => ['mautic.webhook.model.webhook'],
                ],
            ],
        ],
        'other' => [
            'mautic.recombee.helper' => [
                'class'     => MauticPlugin\MauticRecombeeBundle\Helper\RecombeeHelper::class,
                'arguments' => [
                    'mautic.helper.integration',
                    'request_stack',
                    'mautic.lead.model.lead',
                ],
            ],
        ],
    ],
    'routes' => [
        'public' => [
            'mautic_recombee_webhook' => [
                'path'       => '/recombee/hook',
                'controller' => 'MauticRecombeeBundle:Webhook:process',
            ],
        ],
        'api' => [
            'mautic_recombee_api' => [
                'path'       => '/recombee/{component}/{user}/{action}/{item}',
                'controller' => 'MauticRecombeeBundle:Api\RecombeeApi:process',
                'method'     => 'POST',
            ],
        ],
    ],
];
