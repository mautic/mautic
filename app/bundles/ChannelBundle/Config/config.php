<?php

declare(strict_types=1);

return [
    'routes' => [
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

    'parameters' => [
    ],
];
