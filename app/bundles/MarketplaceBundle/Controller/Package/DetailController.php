<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Controller\Package;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\CoreBundle\Helper\ComposerHelper;
use Mautic\MarketplaceBundle\Exception\RecordNotFoundException;
use Mautic\MarketplaceBundle\Model\PackageModel;
use Mautic\MarketplaceBundle\Security\Permissions\MarketplacePermissions;
use Mautic\MarketplaceBundle\Service\Config;
use Mautic\MarketplaceBundle\Service\RouteProvider;
use Symfony\Component\HttpFoundation\Response;

class DetailController extends CommonController
{
    private PackageModel $packageModel;
    private RouteProvider $routeProvider;
    private Config $config;
    private ComposerHelper $composer;

    public function __construct(
        PackageModel $packageModel,
        RouteProvider $routeProvider,
        Config $config,
        ComposerHelper $composer
    ) {
        $this->packageModel    = $packageModel;
        $this->routeProvider   = $routeProvider;
        $this->config          = $config;
        $this->composer        = $composer;
    }

    public function ViewAction(string $vendor, string $package): Response
    {
        if (!$this->config->marketplaceIsEnabled()) {
            return $this->notFound();
        }

        if (!$this->security->isGranted(MarketplacePermissions::CAN_VIEW_PACKAGES)) {
            return $this->accessDenied();
        }

        $isInstalled = $this->composer->isInstalled("{$vendor}/{$package}");

        try {
            $packageDetail = $this->packageModel->getPackageDetail("{$vendor}/{$package}");
        } catch (RecordNotFoundException $e) {
            return $this->notFound($e->getMessage());
        }

        $security = $this->security;

        return $this->delegateView(
            [
                'returnUrl'      => $this->routeProvider->buildListRoute(),
                'viewParameters' => [
                    'packageDetail'     => $packageDetail,
                    'isInstalled'       => $isInstalled,
                    'isComposerEnabled' => $this->config->isComposerEnabled(),
                    'security'          => $security,
                ],
                'contentTemplate' => '@Marketplace/Package/detail.html.twig',
                'passthroughVars' => [
                    'mauticContent' => 'package',
                    'activeLink'    => '#mautic_marketplace',
                    'route'         => $this->routeProvider->buildDetailRoute($vendor, $package),
                ],
            ]
        );
    }
}
