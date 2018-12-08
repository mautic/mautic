<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return [
    'services' => [
        'other' => [
            'mautic.stats.aggregate.collector' => [
                'class'     => \Mautic\StatsBundle\Aggregate\Collector::class,
                'arguments' => [
                    'event_dispatcher',
                ],
            ],
        ],
    ],
];
