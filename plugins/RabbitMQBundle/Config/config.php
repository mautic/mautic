<?php

return [
    'name'        => 'RabbitMQ',
    'description' => 'Enables integration with RabbitMQ.',
    'author'      => '@dragan-mf',
    'version'     => '1.0.0',

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
