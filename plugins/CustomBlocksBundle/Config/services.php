<?php

declare(strict_types=1);

return [
    'services' => [
        'events' => [
            'customblocks.grapesjs.inject.subscriber' => [
                'class'     => \MauticPlugin\CustomBlocksBundle\EventListener\GrapesJsInjectSubscriber::class,
                'arguments' => [
                    'mautic.helper.assets',
                    'request_stack',
                ],
            ],
        ],
    ],
];
