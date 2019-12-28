<?php

/*
* @copyright   2019 Mautic. All rights reserved
* @author      Mautic.
*
* @link        https://mautic.org
*
* @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
*/

use MauticPlugin\MarketplaceBundle\Service\RouteProvider;

return [
    'name'        => 'Marketplace',
    'description' => 'Allows to list, install and update Mautic plugins from Packagist.org',
    'version'     => '0.0',
    'author'      => 'John Linhart',

    'menu' => [
        'admin' => [
            'items' => [
                'marketplace.title' => [
                    'id'        => 'marketplace',
                    'route'     => 'marketplace',
                    'iconClass' => 'fa-clock-o',
                    // 'access'    => 'plugin:marketplace:marketplace:view',
                ],
            ],
        ],
    ],

    'routes' => [
        'main' => [
            RouteProvider::ROUTE_LIST => [
                'path'       => '/marketplace/{page}',
                'controller' => 'MarketplaceBundle:Package\List:list',
                'method'     => 'GET|POST',
                'defaults'   => [
                    'page' => 1,
                ],
            ],
            RouteProvider::ROUTE_INSTALL => [
                'path'       => '/marketplace/install/{vendor}/{package}',
                'controller' => 'MarketplaceBundle:Package\Form:new',
                'method'     => 'GET',
            ],
            RouteProvider::ROUTE_CONFIGURE => [
                'path'       => '/marketplace/configure/{vendor}/{package}',
                'controller' => 'MarketplaceBundle:Package\Form:edit',
                'method'     => 'GET',
            ],
            // RouteProvider::ROUTE_CANCEL => [
            //     'path'       => '/marketplace/cancel/{vendor}/{package}',
            //     'controller' => 'MarketplaceBundle:Package\Cancel:cancel',
            //     'method'     => 'GET',
            //     'defaults'   => [
            //         'objectId' => null,
            //     ],
            // ],
            RouteProvider::ROUTE_CONFIGURE_SAVE => [
                'path'       => '/marketplace/configure/{vendor}/{package}',
                'controller' => 'MarketplaceBundle:Package\Save:save',
                'method'     => 'POST',
                'defaults'   => [
                    'objectId' => null,
                ],
            ],
            RouteProvider::ROUTE_REMOVE => [
                'path'       => '/marketplace/remove/{vendor}/{package}',
                'controller' => 'MarketplaceBundle:Package\Delete:delete',
                'method'     => 'GET',
            ],
        ],
    ],

    'services' => [
        'controllers' => [
            'marketplace.controller.package.list' => [
                'class'     => \MauticPlugin\MarketplaceBundle\Controller\Package\ListController::class,
                'arguments' => [
                    'marketplace.service.plugin_collector',
                    'request_stack',
                    'marketplace.service.route_provider',
                    // 'custom_object.session.provider',
                    // 'mautic.custom.model.object',
                    // 'custom_object.permission.provider',
                    // 'custom_object.route.provider',
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
                'class'     => \MauticPlugin\MarketplaceBundle\Command\ListCommand::class,
                'tag'       => 'console.command',
                'arguments' => ['marketplace.service.plugin_collector'],
            ],
            'marketplace.command.install' => [
                'class'     => \MauticPlugin\MarketplaceBundle\Command\InstallCommand::class,
                'tag'       => 'console.command',
                'arguments' => [
                    'marketplace.service.plugin_collector',
                    'marketplace.service.plugin_downloader',
                    'mautic.plugin.facade.reload',
                    'mautic.helper.core_parameters',
                    'symfony.filesystem',
                    'marketplace.service.composer_combiner',
                ],
            ],
            'marketplace.command.remove' => [
                'class' => \MauticPlugin\MarketplaceBundle\Command\RemoveCommand::class,
                'tag'   => 'console.command',
            ],
        ],
        'api' => [
            'marketplace.api.connection' => [
                'class'     => \MauticPlugin\MarketplaceBundle\Api\Connection::class,
                'arguments' => [
                    'mautic.http.client',
                    'monolog.logger.mautic',
                ],
            ],
        ],
        'factories' => [
            'marketplace.factory.package' => [
                'class' => \MauticPlugin\MarketplaceBundle\Factory\PackageFactory::class,
            ],
        ],
        'other' => [
            'marketplace.service.plugin_downloader' => [
                'class'     => \MauticPlugin\MarketplaceBundle\Service\PluginDownloader::class,
                'arguments' => ['marketplace.api.connection'],
            ],
            'marketplace.service.plugin_collector' => [
                'class'     => \MauticPlugin\MarketplaceBundle\Service\PluginCollector::class,
                'arguments' => ['marketplace.api.connection', 'marketplace.factory.package'],
            ],
            'marketplace.service.composer_combiner' => [
                'class'     => \MauticPlugin\MarketplaceBundle\Service\ComposerCombiner::class,
                'arguments' => ['symfony.filesystem'],
            ],
            'marketplace.service.route_provider' => [
                'class'     => \MauticPlugin\MarketplaceBundle\Service\RouteProvider::class,
                'arguments' => ['router'],
            ],
        ],
    ],
];
