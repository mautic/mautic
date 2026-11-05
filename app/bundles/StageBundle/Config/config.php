<?php

declare(strict_types=1);

use Mautic\StageBundle\Controller\Api\StageApiController;
use Mautic\StageBundle\Entity\Stage;

return [
    'routes' => [
        'api' => [
            'mautic_api_stagesstandard' => [
                'standard_entity' => true,
                'name'            => 'stages',
                'path'            => '/stages',
                'controller'      => StageApiController::class,
            ],
        ],
    ],

    'menu' => [
        'main' => [
            'mautic.stages.menu.index' => [
                'route'     => 'mautic_stage_index',
                'iconClass' => 'ri-barricade-fill flip-vertically',
                'access'    => ['stage:stages:view'],
                'priority'  => 25,
            ],
        ],
    ],

    'categories' => [
        'stage' => [
            'class' => Stage::class,
        ],
    ],
];
