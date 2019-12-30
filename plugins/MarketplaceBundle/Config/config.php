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
                'defaults'   => ['page' => 1],
            ],
            RouteProvider::ROUTE_INSTALL => [
                'path'       => '/marketplace/install/{vendor}/{package}',
                'controller' => 'MarketplaceBundle:Package\Install:view',
                'method'     => 'GET',
            ],
            RouteProvider::ROUTE_INSTALL_STEP_COMPOSER => [
                'path'       => '/marketplace/install/{vendor}/{package}/step/composer',
                'controller' => 'MarketplaceBundle:Package\Install:stepComposer',
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
                ],
                'methodCalls' => [
                    'setContainer' => [
                        '@service_container',
                    ],
                ],
            ],
            'marketplace.controller.package.install' => [
                'class'     => \MauticPlugin\MarketplaceBundle\Controller\Package\InstallController::class,
                'arguments' => [
                    'marketplace.model.package',
                    'marketplace.service.package_installer',
                    'request_stack',
                    'marketplace.service.route_provider',
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
                    'marketplace.service.package_installer',
                    'mautic.plugin.facade.reload',
                    'mautic.helper.core_parameters',
                    'symfony.filesystem',
                ],
            ],
            'marketplace.command.remove' => [
                'class'     => \MauticPlugin\MarketplaceBundle\Command\RemoveCommand::class,
                'tag'       => 'console.command',
                'arguments' => ['marketplace.service.package_remover'],
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
        'models' => [
            'marketplace.model.package' => [
                'class'     => \MauticPlugin\MarketplaceBundle\Model\PackageModel::class,
                'arguments' => ['marketplace.api.connection'],
            ],
        ],
        'other' => [
            'marketplace.service.package_installer' => [
                'class'     => \MauticPlugin\MarketplaceBundle\Service\PackageInstaller::class,
                'arguments' => ['marketplace.service.composer_combiner'],
            ],
            'marketplace.service.package_remover' => [
                'class'     => \MauticPlugin\MarketplaceBundle\Service\PackageRemover::class,
                'arguments' => ['marketplace.service.composer_combiner'],
            ],
            'marketplace.service.plugin_collector' => [
                'class'     => \MauticPlugin\MarketplaceBundle\Service\PluginCollector::class,
                'arguments' => ['marketplace.api.connection'],
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
