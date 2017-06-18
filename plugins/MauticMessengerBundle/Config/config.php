<?php
return [
    'name' => 'Messenger Integration',
    'description' => '',
    'author' => 'kuzmany.biz',
    'version' => '1.0.0',
    'routes' => [
        'public' => [
            'messenger_callback' => [
                'path' => '/messenger/callback',
                'controller' => 'MauticMessengerBundle:Messenger:callback',
            ],
            'messenger_checkbox_plugin' => [
                'path' => '/messenger/checkbox',
                'controller' => 'MauticMessengerBundle:Messenger:checkbox',
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
    'other' => [
        'mautic.plugin.helper.messenger' => [
            'class' => 'MauticPlugin\MauticMessengerBundle\Helper\MessengerHelper',
            'arguments' => [
                'mautic.http.connector',
                'request_stack',
                'mautic.helper.core_parameters',
            ],
        ],
    ],
    'parameters' => array(),

];