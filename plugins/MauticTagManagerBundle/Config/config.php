<?php

return [
    'name'        => 'Mautic tag manager bundle',
    'description' => 'Provides an interface for tags management.',
    'version'     => '1.0',
    'author'      => 'Leuchtfeuer',
    'routes'      => [
        'main' => [
            'mautic_tagmanager_batch_index_action' => [
                'path'       => '/tags/batch/view',
                'controller' => 'MauticPlugin\MauticTagManagerBundle\Controller\BatchTagController::indexAction',
            ],
            'mautic_tagmanager_batch_set_action' => [
                'path'       => '/tags/batch/set',
                'controller' => 'MauticPlugin\MauticTagManagerBundle\Controller\BatchTagController::execAction',
            ],
            'mautic_tagmanager_index' => [
                'path'       => '/tags/{page}',
                'controller' => 'MauticPlugin\MauticTagManagerBundle\Controller\TagController::indexAction',
            ],
            'mautic_tagmanager_action' => [
                'path'       => '/tags/{objectAction}/{objectId}',
                'controller' => 'MauticPlugin\MauticTagManagerBundle\Controller\TagController::executeAction',
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
