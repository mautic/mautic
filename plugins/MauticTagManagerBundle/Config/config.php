<?php

declare(strict_types=1);

use MauticPlugin\MauticTagManagerBundle\Controller\BatchTagController;
use MauticPlugin\MauticTagManagerBundle\Controller\TagController;

return [
    'name'        => 'Mautic tag manager bundle',
    'description' => 'Provides an interface for tags management.',
    'version'     => '1.0',
    'author'      => 'Leuchtfeuer',
    'routes'      => [
        'main' => [
            'mautic_tagmanager_batch_index_action' => [
                'path'       => '/tags/batch/view',
                'controller' => BatchTagController::class.'::indexAction',
            ],
            'mautic_tagmanager_batch_set_action' => [
                'path'       => '/tags/batch/set',
                'controller' => BatchTagController::class.'::execAction',
            ],
            'mautic_tagmanager_index' => [
                'path'       => '/tags/{page}',
                'controller' => TagController::class.'::indexAction',
            ],
            'mautic_tagmanager_action' => [
                'path'       => '/tags/{objectAction}/{objectId}',
                'controller' => TagController::class.'::executeAction',
            ],
        ],
    ],
    'menu' => [
        'main' => [
            'tagmanager.menu.index' => [
                'id'        => 'mautic_tagmanager_index',
                'route'     => 'mautic_tagmanager_index',
                'access'    => 'tagManager:tagManager:view',
                'iconClass' => 'ri-hashtag',
                'priority'  => 1,
            ],
        ],
    ],
];
