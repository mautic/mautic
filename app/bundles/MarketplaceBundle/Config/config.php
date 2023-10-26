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
            RouteProvider::ROUTE_INSTALL => [
                'path'       => '/marketplace/install/{vendor}/{package}',
                'controller' => 'MarketplaceBundle:Package\Install:view',
                'method'     => 'GET|POST',
            ],
            RouteProvider::ROUTE_REMOVE => [
                'path'       => '/marketplace/remove/{vendor}/{package}',
                'controller' => 'MarketplaceBundle:Package\Remove:view',
                'method'     => 'GET|POST',
            ],
            RouteProvider::ROUTE_CLEAR_CACHE => [
                'path'       => '/marketplace/clear/cache',
                'controller' => 'MarketplaceBundle:Cache:clear',
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
                    'mautic.helper.composer',
                ],
                'methodCalls' => [
                    'setContainer' => [
                        '@service_container',
                    ],
                ],
            ],
            'marketplace.controller.package.install' => [
                'class'     => \Mautic\MarketplaceBundle\Controller\Package\InstallController::class,
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
            'marketplace.controller.package.remove' => [
                'class'     => \Mautic\MarketplaceBundle\Controller\Package\RemoveController::class,
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
            'marketplace.controller.cache' => [
                'class'     => \Mautic\MarketplaceBundle\Controller\CacheController::class,
                'arguments' => [
                    'mautic.security',
                    'marketplace.service.config',
                    'marketplace.service.allowlist',
                ],
                'methodCalls' => [
                    'setContainer' => [
                        '@service_container',
                    ],
                ],
            ],
            'marketplace.controller.ajax' => [
                'class'     => \Mautic\MarketplaceBundle\Controller\AjaxController::class,
                'arguments' => [
                    'mautic.helper.composer',
                    'mautic.helper.cache',
                    'monolog.logger.mautic',
                ],
            ],
        ],
        'commands' => [
            'marketplace.command.list' => [
                'class'     => \Mautic\MarketplaceBundle\Command\ListCommand::class,
                'tag'       => 'console.command',
                'arguments' => ['marketplace.service.plugin_collector'],
            ],
            'marketplace.command.install' => [
                'class'     => \Mautic\MarketplaceBundle\Command\InstallCommand::class,
                'tag'       => 'console.command',
                'arguments' => ['mautic.helper.composer', 'marketplace.model.package'],
            ],
            'marketplace.command.remove' => [
                'class'     => \Mautic\MarketplaceBundle\Command\RemoveCommand::class,
                'tag'       => 'console.command',
                'arguments' => ['mautic.helper.composer', 'monolog.logger.mautic'],
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
                'arguments' => ['marketplace.api.connection', 'marketplace.service.allowlist'],
            ],
        ],
        'other' => [
            'marketplace.service.plugin_collector' => [
                'class'     => \Mautic\MarketplaceBundle\Service\PluginCollector::class,
                'arguments' => [
                    'marketplace.api.connection',
                    'marketplace.service.allowlist',
                ],
            ],
            'marketplace.service.route_provider' => [
                'class'     => \Mautic\MarketplaceBundle\Service\RouteProvider::class,
                'arguments' => ['router'],
            ],
            'marketplace.service.config' => [
                'class'     => \Mautic\MarketplaceBundle\Service\Config::class,
                'arguments' => [
                    'mautic.helper.core_parameters',
                ],
            ],
            'marketplace.service.allowlist' => [
                'class'     => \Mautic\MarketplaceBundle\Service\Allowlist::class,
                'arguments' => [
                    'marketplace.service.config',
                    'mautic.cache.provider',
                    'mautic.http.client',
                ],
            ],
        ],
    ],
    // NOTE: when adding new parameters here, please add them to the developer documentation as well:
    'parameters' => [
        Config::MARKETPLACE_ENABLED                     => true,
        Config::MARKETPLACE_ALLOWLIST_URL               => 'https://raw.githubusercontent.com/mautic/marketplace-allowlist/main/allowlist.json',
        Config::MARKETPLACE_ALLOWLIST_CACHE_TTL_SECONDS => 3600,
    ],
];
