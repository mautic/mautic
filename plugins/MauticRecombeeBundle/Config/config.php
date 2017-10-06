<?php

return [
    'name'        => 'Presonalization',
    'description' => '',
    'author'      => 'kuzmany.biz',
    'version'     => '1.0.0',
    'services'    => [
        'events' => [
            'plugin.skeleton.whyMe.subscriber' => [
                'class' => 'MauticPlugin\MauticPersonalizationBundle\EventListener\WhyMeSubscriber',
            ],
        ],
        'other' => [
            'mautic.personalization.helper.recombee' => [
                'class'     => 'MauticPlugin\MauticPersonalizationBundle\Helper\RecombeeHelper',
                'arguments' => [
                    'mautic.helper.integration',
                ],
            ],
        ],
    ],
    'routes' => [
        'public' => [
            'mautic_personalization_webhook' => [
                'path'       => '/personalization/hook',
                'controller' => 'MauticRecombeeBundle:Webhook:process',
            ],
        ],
        'api' => [
            'mautic_recombee_api' => [
                'path'       => '/personalization/{component}/{user}/{action}/{item}',
                'controller' => 'MauticRecombeeBundle:Api\RecombeeApi:process',
                'method'     => 'POST',
            ],
        ],
    ],
];
