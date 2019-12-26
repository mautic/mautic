<?php

/*
 * @copyright   2019 Mautic. All rights reserved
 * @author      Mautic.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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
            CustomObjectRouteProvider::ROUTE_LIST => [
                'path'       => '/custom/object/{page}',
                'controller' => 'CustomObjectsBundle:CustomObject\List:list',
                'method'     => 'GET|POST',
                'defaults'   => [
                    'page' => 1,
                ],
            ],
            CustomObjectRouteProvider::ROUTE_INSTALL => [
                'path'       => '/custom/object/new',
                'controller' => 'CustomObjectsBundle:CustomObject\Form:new',
                'method'     => 'GET',
            ],
            CustomObjectRouteProvider::ROUTE_CONFIGURE => [
                'path'       => '/custom/object/edit/{objectId}',
                'controller' => 'CustomObjectsBundle:CustomObject\Form:edit',
                'method'     => 'GET',
            ],
            CustomObjectRouteProvider::ROUTE_CANCEL => [
                'path'       => '/custom/object/cancel/{objectId}',
                'controller' => 'CustomObjectsBundle:CustomObject\Cancel:cancel',
                'method'     => 'GET',
                'defaults'   => [
                    'objectId' => null,
                ],
            ],
            CustomObjectRouteProvider::ROUTE_SAVE => [
                'path'       => '/custom/object/save/{objectId}',
                'controller' => 'CustomObjectsBundle:CustomObject\Save:save',
                'method'     => 'POST',
                'defaults'   => [
                    'objectId' => null,
                ],
            ],
            CustomObjectRouteProvider::ROUTE_REMOVE => [
                'path'       => '/custom/object/delete/{objectId}',
                'controller' => 'CustomObjectsBundle:CustomObject\Delete:delete',
                'method'     => 'GET',
            ],
        ],
    ],

    'services' => [
        'controllers' => [
            'custom_object.list_controller' => [
                'class'     => \MauticPlugin\CustomObjectsBundle\Controller\CustomObject\ListController::class,
                'arguments' => [
                    'request_stack',
                    'custom_object.session.provider',
                    'mautic.custom.model.object',
                    'custom_object.permission.provider',
                    'custom_object.route.provider',
                ],
                'methodCalls' => [
                    'setContainer' => [
                        '@service_container',
                    ],
                ],
            ],
            'custom_object.view_controller' => [
                'class'     => \MauticPlugin\CustomObjectsBundle\Controller\CustomObject\ViewController::class,
                'arguments' => [
                    'request_stack',
                    'form.factory',
                    'mautic.custom.model.object',
                    'mautic.core.model.auditlog',
                    'custom_object.permission.provider',
                    'custom_object.route.provider',
                ],
                'methodCalls' => [
                    'setContainer' => [
                        '@service_container',
                    ],
                ],
            ],
            'custom_object.form_controller' => [
                'class'     => \MauticPlugin\CustomObjectsBundle\Controller\CustomObject\FormController::class,
                'arguments' => [
                    'form.factory',
                    'mautic.custom.model.object',
                    'mautic.custom.model.field',
                    'custom_object.permission.provider',
                    'custom_object.route.provider',
                    'custom_field.type.provider',
                    'custom_object.lock_flash_message.helper',
                ],
                'methodCalls' => [
                    'setContainer' => [
                        '@service_container',
                    ],
                ],
            ],
            'custom_object.save_controller' => [
                'class'     => \MauticPlugin\CustomObjectsBundle\Controller\CustomObject\SaveController::class,
                'arguments' => [
                    'request_stack',
                    'mautic.core.service.flashbag',
                    'form.factory',
                    'mautic.custom.model.object',
                    'mautic.custom.model.field',
                    'custom_object.permission.provider',
                    'custom_object.route.provider',
                    'custom_field.type.provider',
                    'custom_field.field.params.to.string.transformer',
                    'custom_field.field.options.to.string.transformer',
                    'custom_object.lock_flash_message.helper',
                ],
                'methodCalls' => [
                    'setContainer' => [
                        '@service_container',
                    ],
                ],
            ],
            'custom_object.delete_controller' => [
                'class'     => \MauticPlugin\CustomObjectsBundle\Controller\CustomObject\DeleteController::class,
                'arguments' => [
                    'mautic.custom.model.object',
                    'custom_object.session.provider',
                    'mautic.core.service.flashbag',
                    'custom_object.permission.provider',
                ],
                'methodCalls' => [
                    'setContainer' => [
                        '@service_container',
                    ],
                ],
            ],
            'custom_object.cancel_controller' => [
                'class'     => \MauticPlugin\CustomObjectsBundle\Controller\CustomObject\CancelController::class,
                'arguments' => [
                    'custom_object.session.provider',
                    'custom_object.route.provider',
                    'mautic.custom.model.object',
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
