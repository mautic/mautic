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
    public const ROUTE_LIST   = 'mautic_marketplace_list';

    public const ROUTE_INSTALL = 'mautic_marketplace_install';

    public const ROUTE_INSTALL_STEP_COMPOSER = 'mautic_marketplace_install_step_composer';

    public const ROUTE_CONFIGURE = 'mautic_marketplace_configure';

    public const ROUTE_DELETE = 'mautic_marketplace_delete';

    public const ROUTE_REMOVE = 'mautic_marketplace_remove';

    public const ROUTE_CONFIGURE_SAVE = 'mautic_marketplace_save';

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

    public function buildSaveRoute(?int $id = null): string
    {
        return $this->router->generate(static::ROUTE_SAVE, ['objectId' => $id]);
    }

    public function buildViewRoute(int $id): string
    {
        return $this->router->generate(static::ROUTE_VIEW, ['objectId' => $id]);
    }

    public function buildNewRoute(): string
    {
        return $this->router->generate(static::ROUTE_NEW);
    }

    /**
     * @param int $id
     */
    public function buildEditRoute(?int $id = null): string
    {
        return $this->router->generate(static::ROUTE_EDIT, ['objectId' => $id]);
    }

    public function buildCloneRoute(int $id): string
    {
        return $this->router->generate(static::ROUTE_CLONE, ['objectId' => $id]);
    }

    public function buildDeleteRoute(int $id): string
    {
        return $this->router->generate(static::ROUTE_DELETE, ['objectId' => $id]);
    }
}
