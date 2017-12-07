<?php

return [
    'name'        => 'Recombee',
    'description' => 'Enables integrations with Recombee for products personalization',
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
