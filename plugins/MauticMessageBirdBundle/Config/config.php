<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return [
    'services' => [
        'events'  => [],
        'forms'   => [],
        'helpers' => [],
        'other'   => [
            'mautic.sms.transport.messagebird' => [
                'class'     => 'MauticPlugin\MauticMessageBirdBundle\Services\MessageBirdApi',
                'arguments' => [
                    'mautic.page.model.trackable',
                    'mautic.helper.phone_number',
                    'mautic.helper.integration',
                    'monolog.logger.mautic',
                ],
                'alias' => 'mautic.sms.config.transport.messagebird',
                'tags'  => [
                    'name' => 'mautic.sms_transport', 'MessageBird',
                ],
            ],
        ],
        'models'       => [],
        'integrations' => [
            'mautic.integration.messagebird' => [
                'class' => \MauticPlugin\MauticMessageBirdBundle\Integration\MessageBirdIntegration::class,
            ],
        ],
    ],
    'routes'     => [],
    'menu'       => [],
    'parameters' => [
        'messagebird_api_endpoint'              => 'https://rest.messagebird.com/',
    ],
];
