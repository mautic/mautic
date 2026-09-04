<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Controller\Package;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\MarketplaceBundle\Model\PackageModel;
use Mautic\MarketplaceBundle\Security\Permissions\MarketplacePermissions;
use Mautic\MarketplaceBundle\Service\Config;
use Mautic\MarketplaceBundle\Service\RouteProvider;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Service\Attribute\Required;

final class InstallController extends CommonController
{
    private PackageModel $packageModel;

    private RouteProvider $routeProvider;

    private Config $config;

    #[Required]
    public function autowireInstallController(
        PackageModel $packageModel,
        RouteProvider $routeProvider,
        Config $config,
    ): void {
        $this->packageModel  = $packageModel;
        $this->routeProvider = $routeProvider;
        $this->config        = $config;
    }

    #[Route(
        '/s/marketplace/install/{vendor}/{package}',
        name: 'mautic_marketplace_install',
        methods: ['GET', 'POST'],
    )]
    public function viewAction(string $vendor, string $package): Response
    {
        if (!$this->config->marketplaceIsEnabled()) {
            return $this->notFound();
        }

        if (!$this->security->isGranted(MarketplacePermissions::CAN_INSTALL_PACKAGES)) {
            $this->throwAccessDenied();
        }

        $packageDetail = $this->packageModel->getPackageDetail("{$vendor}/{$package}");
        $isResource    = 'mautic-resource' === ($packageDetail->packageBase->type ?? '');

        if (!$isResource && !$this->config->isComposerEnabled()) {
            $this->throwAccessDenied();
        }

        return $this->delegateView(
            [
                'returnUrl'      => $this->routeProvider->buildListRoute(),
                'viewParameters' => [
                    'packageDetail'  => $packageDetail,
                ],
                'contentTemplate' => '@Marketplace/Package/install.html.twig',
                'passthroughVars' => [
                    'mauticContent' => 'package',
                    'activeLink'    => '#mautic_marketplace',
                    'route'         => $this->routeProvider->buildInstallRoute($vendor, $package),
                ],
            ]
        );
    }
}
