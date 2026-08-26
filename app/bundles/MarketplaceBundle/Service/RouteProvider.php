<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Service;

use Symfony\Component\Routing\RouterInterface;

final readonly class RouteProvider
{
    public const string ROUTE_LIST = 'mautic_marketplace_list';

    public const string ROUTE_DETAIL = 'mautic_marketplace_detail';

    public const string ROUTE_INSTALL = 'mautic_marketplace_install';

    public const string ROUTE_REMOVE = 'mautic_marketplace_remove';

    public const string ROUTE_CLEAR_CACHE = 'mautic_marketplace_clear_cache';

    public function __construct(
        private RouterInterface $router,
    ) {
    }

    public function buildListRoute(int $page = 1): string
    {
        return $this->router->generate(self::ROUTE_LIST, ['page' => $page]);
    }

    public function buildDetailRoute(string $vendor, string $package): string
    {
        return $this->router->generate(
            self::ROUTE_DETAIL,
            ['vendor' => $vendor, 'package' => $package]
        );
    }

    public function buildInstallRoute(string $vendor, string $package): string
    {
        return $this->router->generate(
            self::ROUTE_DETAIL,
            ['vendor' => $vendor, 'package' => $package]
        );
    }

    public function buildRemoveRoute(string $vendor, string $package): string
    {
        return $this->router->generate(
            self::ROUTE_REMOVE,
            ['vendor' => $vendor, 'package' => $package]
        );
    }

    public function buildClearCacheRoute(): string
    {
        return $this->router->generate(
            self::ROUTE_CLEAR_CACHE
        );
    }
}
