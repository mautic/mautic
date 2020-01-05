<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic. All rights reserved
 * @author      Mautic.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MarketplaceBundle\Service;

use Symfony\Component\Routing\RouterInterface;

class RouteProvider
{
    public const ROUTE_LIST = 'mautic_marketplace_list';

    public const ROUTE_DETAIL = 'mautic_marketplace_detail';

    public const ROUTE_INSTALL = 'mautic_marketplace_install';

    public const ROUTE_INSTALL_STEP_COMPOSER = 'mautic_marketplace_install_step_composer';

    public const ROUTE_INSTALL_STEP_DATABASE = 'mautic_marketplace_install_step_database';

    /**
     * @var RouterInterface
     */
    private $router;

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

    public function buildIntallRoute(string $vendor, string $package): string
    {
        return $this->router->generate(
            static::ROUTE_INSTALL,
            ['vendor' => $vendor, 'package' => $package]
        );
    }
}
