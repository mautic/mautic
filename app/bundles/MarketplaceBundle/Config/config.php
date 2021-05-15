<?php

declare(strict_types=1);

use Mautic\MarketplaceBundle\Service\Config;
use Mautic\MarketplaceBundle\Service\RouteProvider;

return [
    'routes' => [
        'main' => [
            RouteProvider::ROUTE_LIST => [
                'path'       => '/marketplace/{page}',
                'controller' => 'MarketplaceBundle:Package\List:list',
                'method'     => 'GET|POST',
                'defaults'   => ['page' => 1],
            ],
            RouteProvider::ROUTE_DETAIL => [
                'path'       => '/marketplace/detail/{vendor}/{package}',
                'controller' => 'MarketplaceBundle:Package\Detail:view',
                'method'     => 'GET',
            ],
        ],
    ],
    'services' => [
        'controllers' => [
            'marketplace.controller.package.list' => [
                'class'     => \Mautic\MarketplaceBundle\Controller\Package\ListController::class,
                'arguments' => [
                    'marketplace.service.plugin_collector',
                    'request_stack',
                    'marketplace.service.route_provider',
                    'mautic.security',
                    'marketplace.service.config',
                ],
                'methodCalls' => [
                    'setContainer' => [
                        '@service_container',
                    ],
                ],
            ],
            'marketplace.controller.package.detail' => [
                'class'     => \Mautic\MarketplaceBundle\Controller\Package\DetailController::class,
                'arguments' => [
                    'marketplace.model.package',
                    'marketplace.service.route_provider',
                    'mautic.security',
                    'marketplace.service.config',
                ],
                'methodCalls' => [
                    'setContainer' => [
                        '@service_container',
                    ],
                ],
            ],
        ],
        'commands' => [
            'marketplace.command.list' => [
                'class'     => \Mautic\MarketplaceBundle\Command\ListCommand::class,
                'tag'       => 'console.command',
                'arguments' => ['marketplace.service.plugin_collector'],
            ],
        ],
        'events' => [
            'marketplace.menu.subscriber' => [
                'class'     => \Mautic\MarketplaceBundle\EventListener\MenuSubscriber::class,
                'arguments' => [
                    'marketplace.service.config',
                ],
            ],
        ],
        'permissions' => [
            'marketplace.permissions' => [
                'class'     => \Mautic\MarketplaceBundle\Security\Permissions\MarketplacePermissions::class,
                'arguments' => [
                    'mautic.helper.core_parameters',
                    'marketplace.service.config',
                ],
            ],
        ],
        'api' => [
            'marketplace.api.connection' => [
                'class'     => \Mautic\MarketplaceBundle\Api\Connection::class,
                'arguments' => [
                    'mautic.http.client',
                    'monolog.logger.mautic',
                ],
            ],
        ],
        'models' => [
            'marketplace.model.package' => [
                'class'     => \Mautic\MarketplaceBundle\Model\PackageModel::class,
                'arguments' => ['marketplace.api.connection'],
            ],
        ],
        'other' => [
            'marketplace.service.plugin_collector' => [
                'class'     => \Mautic\MarketplaceBundle\Service\PluginCollector::class,
                'arguments' => ['marketplace.api.connection'],
            ],
            'marketplace.service.route_provider' => [
                'class'     => \Mautic\MarketplaceBundle\Service\RouteProvider::class,
                'arguments' => ['router'],
            ],
            'marketplace.service.config' => [
                'class'     => \Mautic\MarketplaceBundle\Service\Config::class,
                'arguments' => ['mautic.helper.core_parameters'],
            ],
        ],
    ],
    'parameters' => [
        Config::MARKETPLACE_ENABLED => true,
    ],
];
