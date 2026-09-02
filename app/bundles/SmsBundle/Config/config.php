<?php

declare(strict_types=1);

return [
    'routes' => [
        'api' => [
            'mautic_api_smsesstandard' => [
                'standard_entity' => true,
                'name'            => 'smses',
                'path'            => '/smses',
                'controller'      => Mautic\SmsBundle\Controller\Api\SmsApiController::class,
            ],
        ],
    ],
    'menu' => [
        'main' => [
            'items' => [
                'mautic.sms.smses' => [
                    'route'  => 'mautic_sms_index',
                    'access' => ['sms:smses:viewown', 'sms:smses:viewother'],
                    'parent' => 'mautic.core.channels',
                    'checks' => [
                        'integration' => [
                            'Twilio' => [
                                'enabled' => true,
                            ],
                        ],
                    ],
                    'priority' => 70,
                ],
            ],
        ],
    ],
    'categories' => [
        'sms' => null,
    ],
    'parameters' => [
        'sms_enabled'                                                      => false,
        'sms_username'                                                     => null,
        'sms_password'                                                     => null,
        'sms_messaging_service_sid'                                        => null,
        'sms_frequency_number'                                             => 0,
        'sms_frequency_time'                                               => 'DAY',
        'sms_transport'                                                    => 'mautic.sms.twilio.transport',
        Mautic\SmsBundle\Form\Type\ConfigType::SMS_DISABLE_TRACKABLE_URLS  => false,
    ],
];
