<?php

declare(strict_types=1);

return [
    'routes' => [
        'main' => [
            'mautic_sms_index' => [
                'path'       => '/sms/{page}',
                'controller' => 'Mautic\SmsBundle\Controller\SmsController::indexAction',
            ],
            'mautic_sms_action' => [
                'path'       => '/sms/{objectAction}/{objectId}',
                'controller' => 'Mautic\SmsBundle\Controller\SmsController::executeAction',
            ],
            'mautic_sms_contacts' => [
                'path'       => '/sms/view/{objectId}/contact/{page}',
                'controller' => 'Mautic\SmsBundle\Controller\SmsController::contactsAction',
            ],
        ],
        'public' => [
            'mautic_sms_callback' => [
                'path'       => '/sms/{transport}/callback',
                'controller' => 'Mautic\SmsBundle\Controller\ReplyController::callbackAction',
            ],
            /* @deprecated as this was Twilio specific */
            'mautic_receive_sms' => [
                'path'       => '/sms/receive',
                'controller' => 'Mautic\SmsBundle\Controller\ReplyController::callbackAction',
                'defaults'   => [
                    'transport' => 'twilio',
                ],
            ],
        ],
        'api' => [
            'mautic_api_smsesstandard' => [
                'standard_entity' => true,
                'name'            => 'smses',
                'path'            => '/smses',
                'controller'      => Mautic\SmsBundle\Controller\Api\SmsApiController::class,
            ],
            'mautic_api_smses_send' => [
                'path'       => '/smses/{id}/contact/{contactId}/send',
                'controller' => 'Mautic\SmsBundle\Controller\Api\SmsApiController::sendAction',
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
