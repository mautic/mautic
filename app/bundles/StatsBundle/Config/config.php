<?php

return [
    'services' => [
        'other' => [
            'mautic.stats.aggregate.collector' => [
                'class'     => Mautic\StatsBundle\Aggregate\Collector::class,
                'arguments' => [
                    'event_dispatcher',
                ],
            ],
        ],
    ],
];
