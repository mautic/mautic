<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\MarketplaceBundle\Security\Permissions\MarketplacePermissions;
use Mautic\MarketplaceBundle\Service\Config;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Service\Attribute\Required;

final class CacheController extends CommonController
{
    private Config $config;

    #[Required]
    public function autowireCacheController(Config $config): void
    {
        $this->config = $config;
    }

    #[Route(
        '/s/marketplace/clear/cache',
        name: 'mautic_marketplace_clear_cache',
        methods: ['GET'],
    )]
    public function clearAction(): Response
    {
        if (!$this->config->marketplaceIsEnabled()) {
            return $this->notFound();
        }

        if (!$this->security->isGranted(MarketplacePermissions::CAN_VIEW_PACKAGES)) {
            $this->throwAccessDenied();
        }

        return $this->forward(
            'Mautic\MarketplaceBundle\Controller\Package\ListController::listAction'
        );
    }
}
