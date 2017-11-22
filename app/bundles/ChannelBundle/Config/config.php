<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return [
    'routes' => [
        'main' => [
            'mautic_message_index' => [
                'path'       => '/messages/{page}',
                'controller' => 'MauticChannelBundle:Message:index',
            ],
            'mautic_message_contacts' => [
                'path'       => '/messages/contacts/{objectId}/{channel}/{page}',
                'controller' => 'MauticChannelBundle:Message:contacts',
            ],
            'mautic_message_action' => [
                'path'       => '/messages/{objectAction}/{objectId}',
                'controller' => 'MauticChannelBundle:Message:execute',
            ],
        ],
        'api' => [
            'mautic_api_messagetandard' => [
                'standard_entity' => true,
                'name'            => 'messages',
                'path'            => '/messages',
                'controller'      => 'MauticChannelBundle:Api\MessageApi',
            ],
        ],
        'public' => [

        ],
    ],

    'menu' => [
        'main' => [
            'mautic.channel.messages' => [
                'route'    => 'mautic_message_index',
                'access'   => ['channel:messages:viewown', 'channel:messages:viewother'],
                'parent'   => 'mautic.core.channels',
                'priority' => 110,
            ],
        ],
        'admin' => [

        ],
        'profile' => [

        ],
        'extra' => [

        ],
    ],

    'categories' => [
        'messages',
    ],

    'services' => [
        'events' => [
            'mautic.channel.campaignbundle.subscriber' => [
                'class'     => 'Mautic\ChannelBundle\EventListener\CampaignSubscriber',
                'arguments' => [
                    'mautic.channel.model.message',
                    'mautic.campaign.model.campaign',
                    'mautic.campaign.model.event',
                ],
            ],
            'mautic.channel.channelbundle.subscriber' => [
                'class'     => 'Mautic\ChannelBundle\EventListener\MessageSubscriber',
                'arguments' => [
                    'mautic.core.model.auditlog',
                ],
            ],
            'mautic.channel.channelbundle.lead.subscriber' => [
                'class' => Mautic\ChannelBundle\EventListener\LeadSubscriber::class,
            ],
            'mautic.channel.reportbundle.subscriber' => [
                'class' => Mautic\ChannelBundle\EventListener\ReportSubscriber::class,
            ],

        ],
        'forms' => [
            \Mautic\ChannelBundle\Form\Type\MessageType::class => [
                'class'       => \Mautic\ChannelBundle\Form\Type\MessageType::class,
                'methodCalls' => [
                    'setSecurity' => ['mautic.security'],
                ],
                'arguments' => [
                    'mautic.channel.model.message',
                ],
            ],
            'mautic.form.type.message_list' => [
                'class' => 'Mautic\ChannelBundle\Form\Type\MessageListType',
                'alias' => 'message_list',
            ],
            'mautic.form.type.message_send' => [
                'class'     => 'Mautic\ChannelBundle\Form\Type\MessageSendType',
                'arguments' => ['router', 'mautic.channel.model.message'],
                'alias'     => 'message_send',
            ],
        ],
        'helpers' => [
            'mautic.channel.helper.channel_list' => [
                'class'     => \Mautic\ChannelBundle\Helper\ChannelListHelper::class,
                'arguments' => [
                    'event_dispatcher',
                    'translator',
                ],
                'alias' => 'channel',
            ],
        ],
        'models' => [
            'mautic.channel.model.message' => [
                'class'     => \Mautic\ChannelBundle\Model\MessageModel::class,
                'arguments' => [
                    'mautic.channel.helper.channel_list',
                    'mautic.campaign.model.campaign',
                ],
            ],
            'mautic.channel.model.queue' => [
                'class'     => 'Mautic\ChannelBundle\Model\MessageQueueModel',
                'arguments' => [
                    'mautic.lead.model.lead',
                    'mautic.lead.model.company',
                    'mautic.helper.core_parameters',
                ],
            ],
        ],
    ],

    'parameters' => [

    ],
];
