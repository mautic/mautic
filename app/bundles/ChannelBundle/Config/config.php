<?php

return [
    'routes' => [
        'main' => [
            'mautic_message_index' => [
                'path'       => '/messages/{page}',
                'controller' => 'Mautic\ChannelBundle\Controller\MessageController::indexAction',
            ],
            'mautic_message_contacts' => [
                'path'       => '/messages/contacts/{objectId}/{channel}/{page}',
                'controller' => 'Mautic\ChannelBundle\Controller\MessageController::contactsAction',
            ],
            'mautic_message_action' => [
                'path'       => '/messages/{objectAction}/{objectId}',
                'controller' => 'Mautic\ChannelBundle\Controller\MessageController::executeAction',
            ],
            'mautic_channel_batch_contact_set' => [
                'path'       => '/channels/batch/contact/set',
                'controller' => 'Mautic\ChannelBundle\Controller\BatchContactController::setAction',
            ],
            'mautic_channel_batch_contact_view' => [
                'path'       => '/channels/batch/contact/view',
                'controller' => 'Mautic\ChannelBundle\Controller\BatchContactController::indexAction',
            ],
        ],
        'api' => [
            'mautic_api_messagetandard' => [
                'standard_entity' => true,
                'name'            => 'messages',
                'path'            => '/messages',
                'controller'      => 'Mautic\ChannelBundle\Controller\Api\MessageApiController',
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
        'messages' => null,
    ],

    'services' => [
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
            'mautic.channel.model.channel.action' => [
                'class'     => \Mautic\ChannelBundle\Model\ChannelActionModel::class,
                'arguments' => [
                    'mautic.lead.model.lead',
                    'mautic.lead.model.dnc',
                    'translator',
                ],
            ],
            'mautic.channel.model.frequency.action' => [
                'class'     => \Mautic\ChannelBundle\Model\FrequencyActionModel::class,
                'arguments' => [
                    'mautic.lead.model.lead',
                    'mautic.lead.repository.frequency_rule',
                ],
            ],
        ],
        'repositories' => [
            'mautic.channel.repository.message_queue' => [
                'class'     => Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => \Mautic\ChannelBundle\Entity\MessageQueue::class,
            ],
        ],
    ],

    'parameters' => [
    ],
];
