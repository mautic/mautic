<?php

return [
    'services' => [
        'events' => [
            'mautic.sms.lead.subscriber' => [
                'class'     => \Mautic\SmsBundle\EventListener\LeadSubscriber::class,
                'arguments' => [
                    'translator',
                    'router',
                    'doctrine.orm.entity_manager',
                ],
            ],
            'mautic.sms.broadcast.subscriber' => [
                'class'     => \Mautic\SmsBundle\EventListener\BroadcastSubscriber::class,
                'arguments' => [
                    'mautic.sms.broadcast.executioner',
                ],
            ],
            'mautic.sms.campaignbundle.subscriber.send' => [
                'class'     => \Mautic\SmsBundle\EventListener\CampaignSendSubscriber::class,
                'arguments' => [
                    'mautic.sms.model.sms',
                    'mautic.sms.transport_chain',
                ],
                'alias' => 'mautic.sms.campaignbundle.subscriber',
            ],
            'mautic.sms.campaignbundle.subscriber.reply' => [
                'class'     => \Mautic\SmsBundle\EventListener\CampaignReplySubscriber::class,
                'arguments' => [
                    'mautic.sms.transport_chain',
                    'mautic.campaign.executioner.realtime',
                ],
            ],
            'mautic.sms.smsbundle.subscriber' => [
                'class'     => \Mautic\SmsBundle\EventListener\SmsSubscriber::class,
                'arguments' => [
                    'mautic.core.model.auditlog',
                    'mautic.page.model.trackable',
                    'mautic.page.helper.token',
                    'mautic.asset.helper.token',
                    'mautic.helper.sms',
                ],
            ],
            'mautic.sms.channel.subscriber' => [
                'class'     => \Mautic\SmsBundle\EventListener\ChannelSubscriber::class,
                'arguments' => [
                    'mautic.sms.transport_chain',
                ],
            ],
            'mautic.sms.message_queue.subscriber' => [
                'class'     => \Mautic\SmsBundle\EventListener\MessageQueueSubscriber::class,
                'arguments' => [
                    'mautic.sms.model.sms',
                ],
            ],
            'mautic.sms.stats.subscriber' => [
                'class'     => \Mautic\SmsBundle\EventListener\StatsSubscriber::class,
                'arguments' => [
                    'mautic.security',
                    'doctrine.orm.entity_manager',
                ],
            ],
            'mautic.sms.configbundle.subscriber' => [
                'class' => Mautic\SmsBundle\EventListener\ConfigSubscriber::class,
            ],
            'mautic.sms.subscriber.contact_tracker' => [
                'class'     => \Mautic\SmsBundle\EventListener\TrackingSubscriber::class,
                'arguments' => [
                    'mautic.sms.repository.stat',
                ],
            ],
            'mautic.sms.subscriber.stop' => [
                'class'     => \Mautic\SmsBundle\EventListener\StopSubscriber::class,
                'arguments' => [
                    'mautic.lead.model.dnc',
                ],
            ],
            'mautic.sms.subscriber.reply' => [
                'class'     => \Mautic\SmsBundle\EventListener\ReplySubscriber::class,
                'arguments' => [
                    'translator',
                    'mautic.lead.repository.lead_event_log',
                ],
            ],
            'mautic.sms.webhook.subscriber' => [
                'class'     => \Mautic\SmsBundle\EventListener\WebhookSubscriber::class,
                'arguments' => [
                    'mautic.webhook.model.webhook',
                ],
            ],
        ],
        'forms' => [
            'mautic.form.type.sms' => [
                'class'     => \Mautic\SmsBundle\Form\Type\SmsType::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                ],
            ],
            'mautic.form.type.smsconfig' => [
                'class' => \Mautic\SmsBundle\Form\Type\ConfigType::class,
            ],
            'mautic.form.type.smssend_list' => [
                'class'     => \Mautic\SmsBundle\Form\Type\SmsSendType::class,
                'arguments' => 'router',
            ],
            'mautic.form.type.sms_list' => [
                'class' => \Mautic\SmsBundle\Form\Type\SmsListType::class,
            ],
            'mautic.form.type.sms.config.form' => [
                'class'     => \Mautic\SmsBundle\Form\Type\ConfigType::class,
                'arguments' => ['mautic.sms.transport_chain', 'translator'],
            ],
            'mautic.form.type.sms.campaign_reply_type' => [
                'class' => \Mautic\SmsBundle\Form\Type\CampaignReplyType::class,
            ],
        ],
        'helpers' => [
            'mautic.helper.sms' => [
                'class'     => \Mautic\SmsBundle\Helper\SmsHelper::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                    'mautic.lead.model.lead',
                    'mautic.helper.phone_number',
                    'mautic.sms.model.sms',
                    'mautic.helper.integration',
                    'mautic.lead.model.dnc',
                ],
                'alias' => 'sms_helper',
            ],
        ],
        'other' => [
            'mautic.sms.transport_chain' => [
                'class'     => \Mautic\SmsBundle\Sms\TransportChain::class,
                'arguments' => [
                    '%mautic.sms_transport%',
                    'mautic.helper.integration',
                    'monolog.logger.mautic',
                ],
            ],
            'mautic.sms.callback_handler_container' => [
                'class' => \Mautic\SmsBundle\Callback\HandlerContainer::class,
            ],
            'mautic.sms.helper.contact' => [
                'class'     => \Mautic\SmsBundle\Helper\ContactHelper::class,
                'arguments' => [
                    'mautic.lead.repository.lead',
                    'doctrine.dbal.default_connection',
                    'mautic.helper.phone_number',
                ],
            ],
            'mautic.sms.helper.reply' => [
                'class'     => \Mautic\SmsBundle\Helper\ReplyHelper::class,
                'arguments' => [
                    'event_dispatcher',
                    'monolog.logger.mautic',
                    'mautic.tracker.contact',
                ],
            ],
            'mautic.sms.twilio.configuration' => [
                'class'        => \Mautic\SmsBundle\Integration\Twilio\Configuration::class,
                'arguments'    => [
                    'mautic.helper.integration',
                ],
            ],
            'mautic.sms.twilio.transport' => [
                'class'        => \Mautic\SmsBundle\Integration\Twilio\TwilioTransport::class,
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
                'class'     => \Mautic\SmsBundle\Integration\Twilio\TwilioCallback::class,
                'arguments' => [
                    'mautic.sms.helper.contact',
                    'mautic.sms.twilio.configuration',
                ],
                'tag'   => 'mautic.sms_callback_handler',
            ],

            // @deprecated - this should not be used; use `mautic.sms.twilio.transport` instead.
            // Only kept as BC in case someone is passing the service by name in 3rd party
            'mautic.sms.transport.twilio' => [
                'class'        => \Mautic\SmsBundle\Api\TwilioApi::class,
                'arguments'    => [
                    'mautic.sms.twilio.configuration',
                    'monolog.logger.mautic',
                ],
            ],
            'mautic.sms.broadcast.executioner' => [
                'class'        => \Mautic\SmsBundle\Broadcast\BroadcastExecutioner::class,
                'arguments'    => [
                    'mautic.sms.model.sms',
                    'mautic.sms.broadcast.query',
                    'translator',
                ],
            ],
            'mautic.sms.broadcast.query' => [
                'class'        => \Mautic\SmsBundle\Broadcast\BroadcastQuery::class,
                'arguments'    => [
                    'doctrine.orm.entity_manager',
                    'mautic.sms.model.sms',
                ],
            ],
        ],
        'models' => [
            'mautic.sms.model.sms' => [
                'class'     => 'Mautic\SmsBundle\Model\SmsModel',
                'arguments' => [
                    'mautic.page.model.trackable',
                    'mautic.lead.model.lead',
                    'mautic.channel.model.queue',
                    'mautic.sms.transport_chain',
                    'mautic.helper.cache_storage',
                ],
            ],
        ],
        'integrations' => [
            'mautic.integration.twilio' => [
                'class'     => \Mautic\SmsBundle\Integration\TwilioIntegration::class,
                'arguments' => [
                    'event_dispatcher',
                    'mautic.helper.cache_storage',
                    'doctrine.orm.entity_manager',
                    'session',
                    'request_stack',
                    'router',
                    'translator',
                    'logger',
                    'mautic.helper.encryption',
                    'mautic.lead.model.lead',
                    'mautic.lead.model.company',
                    'mautic.helper.paths',
                    'mautic.core.model.notification',
                    'mautic.lead.model.field',
                    'mautic.plugin.model.integration_entity',
                    'mautic.lead.model.dnc',
                ],
            ],
        ],
        'repositories' => [
            'mautic.sms.repository.stat' => [
                'class'     => Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [
                    \Mautic\SmsBundle\Entity\Stat::class,
                ],
            ],
        ],
        'controllers' => [
            'mautic.sms.controller.reply' => [
                'class'     => \Mautic\SmsBundle\Controller\ReplyController::class,
                'arguments' => [
                    'mautic.sms.callback_handler_container',
                    'mautic.sms.helper.reply',
                ],
                'methodCalls' => [
                    'setContainer' => [
                        '@service_container',
                    ],
                ],
            ],
        ],
    ],
    'routes' => [
        'main' => [
            'mautic_sms_index' => [
                'path'       => '/sms/{page}',
                'controller' => 'MauticSmsBundle:Sms:index',
            ],
            'mautic_sms_action' => [
                'path'       => '/sms/{objectAction}/{objectId}',
                'controller' => 'MauticSmsBundle:Sms:execute',
            ],
            'mautic_sms_contacts' => [
                'path'       => '/sms/view/{objectId}/contact/{page}',
                'controller' => 'MauticSmsBundle:Sms:contacts',
            ],
        ],
        'public' => [
            'mautic_sms_callback' => [
                'path'       => '/sms/{transport}/callback',
                'controller' => 'MauticSmsBundle:Reply:callback',
            ],
            /* @deprecated as this was Twilio specific */
            'mautic_receive_sms' => [
                'path'       => '/sms/receive',
                'controller' => 'MauticSmsBundle:Reply:callback',
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
                'controller'      => 'MauticSmsBundle:Api\SmsApi',
            ],
            'mautic_api_smses_send' => [
                'path'       => '/smses/{id}/contact/{contactId}/send',
                'controller' => 'MauticSmsBundle:Api\SmsApi:send',
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
    'parameters' => [
        'sms_enabled'              => false,
        'sms_username'             => null,
        'sms_password'             => null,
        'sms_sending_phone_number' => null,
        'sms_frequency_number'     => 0,
        'sms_frequency_time'       => 'DAY',
        'sms_transport'            => 'mautic.sms.twilio.transport',
    ],
];
