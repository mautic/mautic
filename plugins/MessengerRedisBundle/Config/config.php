<?php

declare(strict_types=1);

return [
    'name'        => 'Messenger Redis Support for Mautic',
    'description' => 'Adds Redis as supported messenger for Mautic.',
    'version'     => '1.0',
    'author'      => 'Steer Campaign',

    'services' => [
        'others' => [
            'mautic.messenger.redis' => [
                'class'        => \MauticPlugin\MessengerRedisBundle\Messenger\RedisTransport::class,
                'arguments'    => [],
                'tagArguments' => [
                    \Mautic\MessengerBundle\Model\MessengerTransportType::TRANSPORT_ALIAS   => 'mautic.messenger.config.transport.redis',
                    \Mautic\MessengerBundle\Model\MessengerTransportType::FIELD_HOST        => true,
                    \Mautic\MessengerBundle\Model\MessengerTransportType::FIELD_PORT        => true,
                    \Mautic\MessengerBundle\Model\MessengerTransportType::FIELD_USER        => false,
                    \Mautic\MessengerBundle\Model\MessengerTransportType::FIELD_PASSWORD    => false,
                    \Mautic\MessengerBundle\Model\MessengerTransportType::TRANSPORT_OPTIONS => 'MauticPlugin\MessengerRedisBundle\Form\Type\ConfigType',
                    \Mautic\MessengerBundle\Model\MessengerTransportType::DSN_CONVERTOR     => 'MauticPlugin\MessengerRedisBundle\Helper\DsnRedisConvertor',
                ],
                'tag'          => 'mautic.messenger_transport',
            ],
        ],
        'integrations' => [
            'mautic.integration.messengerredis' => [
                'class'     => \MauticPlugin\MessengerRedisBundle\Integration\MessengerRedisIntegration::class,
                'tags'      => [
                    'mautic.integration',
                    'mautic.basic_integration',
                ],
            ],
            // Provides the form types to use for the configuration UI
            'messengerredis.integration.configuration' => [
                'class'     => \MauticPlugin\MessengerRedisBundle\Integration\Support\ConfigSupport::class,
                'tags'      => [
                    'mautic.config_integration',
                ],
            ],
        ],
    ],
];
