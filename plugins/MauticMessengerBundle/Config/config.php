<?php
return [
    'name' => 'Messenger Integration',
    'description' => '',
    'author' => 'kuzmany.biz',
    'version' => '1.0.0',
    'routes' => [
        'public' => [
            'mautic_messenger' => [
                'path' => '/messenger/callback',
                'controller' => 'MauticMessengerBundle:Messenger:callback',
            ],
        ],
    ],
    'parameters' => array(),

];