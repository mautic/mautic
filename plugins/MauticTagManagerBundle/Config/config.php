<?php

return [
    'name'        => 'Mautic tag manager bundle',
    'description' => 'Provides an interface for tags management.',
    'version'     => '1.0',
    'author'      => 'Leuchtfeuer',
    'routes'      => [
        'main' => [
            'mautic_tagmanager_index' => [
                'path'       => '/tags/{page}',
                'controller' => 'MauticTagManagerBundle:Tag:index',
            ],
            'mautic_tagmanager_action' => [
                'path'       => '/tags/{objectAction}/{objectId}',
                'controller' => 'MauticTagManagerBundle:Tag:execute',
            ],
        ],
    ],
    'services'    => [
        'integrations' => [
            'mautic.integration.tagmanager' => [
                'class'     => \MauticPlugin\MauticTagManagerBundle\Integration\TagManagerIntegration::class,
                'arguments' => [
                    'event_dispatcher',
                    'mautic.helper.cache_storage',
                    'doctrine.orm.entity_manager',
                    'session',
                    'request_stack',
                    'router',
                    'translator',
                    'logger',
                    'mautic.helper.encryption',
                    'mautic.lead.model.lead',
                    'mautic.lead.model.company',
                    'mautic.helper.paths',
                    'mautic.core.model.notification',
                    'mautic.lead.model.field',
                    'mautic.plugin.model.integration_entity',
                    'mautic.lead.model.dnc',
                ],
            ],
        ],
        'repositories' => [
            'mautic.tagmanager.repository.tag' => [
                'class'     => Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [
                    \MauticPlugin\MauticTagManagerBundle\Entity\Tag::class,
                ],
            ],
        ],
        'models' => [
            'mautic.tagmanager.model.tag' => [
                'class'     => \MauticPlugin\MauticTagManagerBundle\Model\TagModel::class,
                'arguments' => [
                    'service_container',
                ],
            ],
        ],
    ],
    'menu' => [
        'main' => [
            'tagmanager.menu.index' => [
                'id'        => 'mautic_tagmanager_index',
                'route'     => 'mautic_tagmanager_index',
                'access'    => 'tagManager:tagManager:view',
                'iconClass' => 'fa-tag',
                'priority'  => 1,
            ],
        ],
    ],
  ];
