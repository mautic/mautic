<?php

return [
    'name'        => 'MauticBitlyBundle',
    'description' => 'Bitly bundle for Mautic.',
    'version'     => '1.0',
    'author'      => 'Webmecanik',
    'services'    => [
        'others' => [
            'mautic.shortener.bitly' => [
                'class'     => \MauticPlugin\MauticBitlyBundle\Shortener\BitlyService::class,
                'arguments' => [
                    'mautic.http.client',
                    'mautic.bitly.config',
                    'monolog.logger.mautic',
                ],
                'tags'      => [
                    'mautic.shortener.service',
                ],
            ],
            'mautic.bitly.config'            => [
                'class'     => \MauticPlugin\MauticBitlyBundle\Integration\Config::class,
                'arguments' => [
                    'mautic.integrations.helper',
                ],
            ],
        ],
        'integrations' => [
            // Basic definitions with name, display name and icon
            'mautic.integration.bitlybundle' => [
                'class' => \MauticPlugin\MauticBitlyBundle\Integration\BitlyBundleIntegration::class,
                'tags'  => [
                    'mautic.integration',
                    'mautic.basic_integration',
                ],
            ],
            // Provides the form types to use for the configuration UI
            'bitlybundle.integration.configuration' => [
                'class'     => \MauticPlugin\MauticBitlyBundle\Integration\Support\ConfigSupport::class,
                'tags'      => [
                    'mautic.config_integration',
                ],
            ],
        ],
    ],
];
