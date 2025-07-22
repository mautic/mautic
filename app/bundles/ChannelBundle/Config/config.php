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
                'controller'      => Mautic\ChannelBundle\Controller\Api\MessageApiController::class,
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
        'messages' => [
            'class' => Mautic\ChannelBundle\Entity\Message::class,
        ],
    ],

    'services' => [
        'helpers' => [
            'mautic.channel.helper.channel_list' => [
                'class'     => Mautic\ChannelBundle\Helper\ChannelListHelper::class,
                'arguments' => [
                    'event_dispatcher',
                    'translator',
                ],
                'alias' => 'channel',
            ],
        ],
    ],

    'parameters' => [
    ],
];
