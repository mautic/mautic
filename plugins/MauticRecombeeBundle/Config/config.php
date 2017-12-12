<?php

return [
    'name'        => 'Recombee',
    'description' => 'Enable integration with Recombee  - personalize content using Recommender as a Service',
    'author'      => 'kuzmany.biz',
    'version'     => '1.0.0',
    'services'    => [
        'other' => [
            'mautic.recombee.helper' => [
                'class'     => 'MauticPlugin\MauticRecombeeBundle\Helper\RecombeeHelper',
                'arguments' => [
                    'mautic.helper.integration',
                ],
            ],
        ],
    ],
    'routes' => [
        'public' => [
            'mautic_recombee_webhook' => [
                'path'       => '/recombee/hook',
                'controller' => 'MauticRecombeeBundle:Webhook:process',
            ],
        ],
        'api' => [
            'mautic_recombee_api' => [
                'path'       => '/recombee/{component}/{user}/{action}/{item}',
                'controller' => 'MauticRecombeeBundle:Api\RecombeeApi:process',
                'method'     => 'POST',
            ],
        ],
    ],
];
