<?php
/*
 * @copyright   2016 Mautic.org. All rights reserved
 * @author      Jan Linhart
 * @link        https://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return [
    'name'        => 'Marketplace',
    'description' => 'Allows to list, install and update Mautic plugins from Packagist.org',
    'version'     => '0.0',
    'author'      => 'John Linhart',

    'routes' => [
        'main' => [
            'marketplace' => [
                'path'       => '/marketplace',
                'controller' => 'MarketplaceBundle:Marketplace:index',
            ],
        ],
    ],

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

    'services' => [
        'commands' => [
            'marketplace.command.list' => [
                'class'     => \MauticPlugin\MarketplaceBundle\Command\ListCommand::class,
                'tag'       => 'console.command',
                'arguments' => ['marketplace.api.connection'],
            ],
            'marketplace.command.install' => [
                'class'     => \MauticPlugin\MarketplaceBundle\Command\InstallCommand::class,
                'tag'       => 'console.command',
                'arguments' => [
                    'marketplace.service.plugin_collector',
                    'marketplace.service.plugin_downloader',
                    'mautic.plugin.facade.reload',
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
        ],
    ],
];
