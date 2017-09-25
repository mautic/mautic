<?php
return [
    'name' => 'Presonalization',
    'description' => '',
    'author' => 'kuzmany.biz',
    'version' => '1.0.0',
    'services' => [
        'events' => [
            'plugin.skeleton.whyMe.subscriber' => [
                'class' => 'MauticPlugin\MauticPersonalizationBundle\EventListener\WhyMeSubscriber',
            ],
        ],
    ],
    'routes' => [
        'public' => [
            'mautic_personalization_webhook' => [
                'path' => '/personalization/hook',
                'controller' => 'MauticPersonalizationBundle:Webhook:process',
            ],
        ],
    ],
];