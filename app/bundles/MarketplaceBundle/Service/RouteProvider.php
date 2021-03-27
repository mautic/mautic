<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Service;

use Symfony\Component\Routing\RouterInterface;

class RouteProvider
{
    public const ROUTE_LIST = 'mautic_marketplace_list';

    public const ROUTE_DETAIL = 'mautic_marketplace_detail';

    private RouterInterface $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function buildListRoute(int $page = 1): string
    {
        return $this->router->generate(static::ROUTE_LIST, ['page' => $page]);
    }

    public function buildDetailRoute(string $vendor, string $package): string
    {
        return $this->router->generate(
            static::ROUTE_DETAIL,
            ['vendor' => $vendor, 'package' => $package]
        );
    }
}
