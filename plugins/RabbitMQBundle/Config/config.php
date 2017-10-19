<?php

return [
    'name'        => 'RabbitMQ',
    'description' => 'Enables integration with RabbitMQ.',
    'author'      => '@dragan-mf',
    'version'     => '1.0.0',

    'routes' => [
        'public' => [
            'mautic_plugin_rabbitmq_hook_insert' => [
                'path'       => '/rabbitmq/hook/insert',
                'controller' => 'RabbitMQBundle:Public:insert'
            ],
            'mautic_plugin_rabbitmq_hook_update' => [
                'path'       => '/rabbitmq/hook/update',
                'controller' => 'RabbitMQBundle:Public:update'
            ],
            'mautic_plugin_rabbitmq_hook_delete' => [
                'path'       => '/rabbitmq/hook/delete',
                'controller' => 'RabbitMQBundle:Public:delete'
            ]
        ]
    ],

    'services' => [
        'events' => [
            'mautic.rabbitmq.lead.subscriber' => [
                'class' => 'MauticPlugin\RabbitMQBundle\EventListener\LeadSubscriber',
                'arguments' => [
                    'mautic.helper.integration'
                ]
            ],
        ],
        'integrations' => [
            'mautic.integration.rabbitmq' => [
                'class'     => \MauticPlugin\RabbitMQBundle\Integration\RabbitMQIntegration::class,
                'arguments' => [
                ],
        	]
    	]
    ]
];
