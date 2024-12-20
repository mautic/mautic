<?php

return [
    'services' => [
        'helpers' => [
            'mautic.helper.sms' => [
                'class'     => Mautic\SmsBundle\Helper\SmsHelper::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                    'mautic.lead.model.lead',
                    'mautic.helper.phone_number',
                    'mautic.sms.model.sms',
                    'mautic.helper.integration',
                    'mautic.lead.model.dnc',
                    'mautic.helper.core_parameters',
                ],
                'alias' => 'sms_helper',
            ],
        ],
        'other' => [
            'mautic.sms.transport_chain' => [
                'class'     => Mautic\SmsBundle\Sms\TransportChain::class,
                'arguments' => [
                    '%mautic.sms_transport%',
                    'mautic.helper.integration',
                    'monolog.logger.mautic',
                ],
            ],
            'mautic.sms.callback_handler_container' => [
                'class' => Mautic\SmsBundle\Callback\HandlerContainer::class,
            ],
            'mautic.sms.helper.contact' => [
                'class'     => Mautic\SmsBundle\Helper\ContactHelper::class,
                'arguments' => [
                    'mautic.lead.repository.lead',
                    'doctrine.dbal.default_connection',
                    'mautic.helper.phone_number',
                ],
            ],
            'mautic.sms.helper.reply' => [
                'class'     => Mautic\SmsBundle\Helper\ReplyHelper::class,
                'arguments' => [
                    'event_dispatcher',
                    'monolog.logger.mautic',
                    'mautic.tracker.contact',
                ],
            ],
            'mautic.sms.twilio.configuration' => [
                'class'        => Mautic\SmsBundle\Integration\Twilio\Configuration::class,
                'arguments'    => [
                    'mautic.helper.integration',
                ],
            ],
            'mautic.sms.twilio.transport' => [
                'class'        => Mautic\SmsBundle\Integration\Twilio\TwilioTransport::class,
                'arguments'    => [
                    'mautic.sms.twilio.configuration',
                    'monolog.logger.mautic',
                ],
                'tag'          => 'mautic.sms_transport',
                'tagArguments' => [
                    'integrationAlias' => 'Twilio',
                ],
                'serviceAliases' => [
                    'sms_api',
                    'mautic.sms.api',
                ],
            ],
            'mautic.sms.twilio.callback' => [
                'class'     => Mautic\SmsBundle\Integration\Twilio\TwilioCallback::class,
                'arguments' => [
                    'mautic.sms.helper.contact',
                    'mautic.sms.twilio.configuration',
                ],
                'tag'   => 'mautic.sms_callback_handler',
            ],

            // @deprecated - this should not be used; use `mautic.sms.twilio.transport` instead.
            // Only kept as BC in case someone is passing the service by name in 3rd party
            'mautic.sms.transport.twilio' => [
                'class'        => Mautic\SmsBundle\Api\TwilioApi::class,
                'arguments'    => [
                    'mautic.sms.twilio.configuration',
                    'monolog.logger.mautic',
                ],
            ],
            'mautic.sms.broadcast.executioner' => [
                'class'        => Mautic\SmsBundle\Broadcast\BroadcastExecutioner::class,
                'arguments'    => [
                    'mautic.sms.model.sms',
                    'mautic.sms.broadcast.query',
                    'translator',
                    'mautic.lead.repository.lead',
                ],
            ],
            'mautic.sms.broadcast.query' => [
                'class'        => Mautic\SmsBundle\Broadcast\BroadcastQuery::class,
                'arguments'    => [
                    'doctrine.orm.entity_manager',
                    'mautic.sms.model.sms',
                ],
            ],
        ],
        'integrations' => [
            'mautic.integration.twilio' => [
                'class'     => Mautic\SmsBundle\Integration\TwilioIntegration::class,
                'arguments' => [
                    'event_dispatcher',
                    'mautic.helper.cache_storage',
                    'doctrine.orm.entity_manager',
                    'request_stack',
                    'router',
                    'translator',
                    'monolog.logger.mautic',
                    'mautic.helper.encryption',
                    'mautic.lead.model.lead',
                    'mautic.lead.model.company',
                    'mautic.helper.paths',
                    'mautic.core.model.notification',
                    'mautic.lead.model.field',
                    'mautic.plugin.model.integration_entity',
                    'mautic.lead.model.dnc',
                    'mautic.lead.field.fields_with_unique_identifier',
                ],
            ],
        ],
    ],
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
