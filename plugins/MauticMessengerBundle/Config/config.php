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
    'services' => [
        'forms' => [
            'mautic.form.type.messenger.facebook' => [
                'class' => 'MauticPlugin\MauticMessengerBundle\Form\Type\MessengerType',
                'alias' => 'messenger_facebook',
            ],
        ],
    ],
    'parameters' => array(),

];