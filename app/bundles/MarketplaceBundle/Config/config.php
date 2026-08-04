<?php

declare(strict_types=1);

use Mautic\MarketplaceBundle\Service\Config;
use Mautic\MarketplaceBundle\Service\RouteProvider;

return [
    'routes' => [
        'main' => [
            RouteProvider::ROUTE_LIST => [
                'path'       => '/marketplace/{page}',
                'controller' => 'Mautic\MarketplaceBundle\Controller\Package\ListController::listAction',
                'method'     => 'GET|POST',
                'defaults'   => ['page' => 1],
            ],
            RouteProvider::ROUTE_DETAIL => [
                'path'       => '/marketplace/detail/{vendor}/{package}',
                'controller' => 'Mautic\MarketplaceBundle\Controller\Package\DetailController::viewAction',
                'method'     => 'GET',
            ],
            RouteProvider::ROUTE_INSTALL => [
                'path'       => '/marketplace/install/{vendor}/{package}',
                'controller' => 'Mautic\MarketplaceBundle\Controller\Package\InstallController::viewAction',
                'method'     => 'GET|POST',
            ],
            RouteProvider::ROUTE_REMOVE => [
                'path'       => '/marketplace/remove/{vendor}/{package}',
                'controller' => 'Mautic\MarketplaceBundle\Controller\Package\RemoveController::viewAction',
                'method'     => 'GET|POST',
            ],
            RouteProvider::ROUTE_CLEAR_CACHE => [
                'path'       => '/marketplace/clear/cache',
                'controller' => 'Mautic\MarketplaceBundle\Controller\CacheController::clearAction',
                'method'     => 'GET',
            ],
        ],
    ],
    // NOTE: when adding new parameters here, please add them to the developer documentation as well:
    'parameters' => [
        Config::MARKETPLACE_ENABLED                => true,
        Config::MARKETPLACE_WEBSITE_URL            => 'https://marketplace.mautic.org',
        Config::MARKETPLACE_API_BASE               => 'https://marketplace-api.mautic.org',
        Config::MARKETPLACE_API_KEY                => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InBkbW5jYnBpbG54dHJod2FpdHRkIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTQ2NTM2MzcsImV4cCI6MjA3MDIyOTYzN30.zVl0eqXdkluLAMRGqf381peTk2TWyYkd01KRPseLlss',
    ],
];
