<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Controller\Package;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\CoreBundle\Helper\ComposerHelper;
use Mautic\MarketplaceBundle\Exception\ApiException;
use Mautic\MarketplaceBundle\Model\PackageModel;
use Mautic\MarketplaceBundle\Security\Permissions\MarketplacePermissions;
use Mautic\MarketplaceBundle\Service\Config;
use Mautic\MarketplaceBundle\Service\ResourceInstallerInterface;
use Mautic\MarketplaceBundle\Service\RouteProvider;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Service\Attribute\Required;

final class DetailController extends CommonController
{
    private PackageModel $packageModel;

    private RouteProvider $routeProvider;

    private Config $config;

    private ComposerHelper $composer;

    private ResourceInstallerInterface $resourceInstaller;

    #[Required]
    public function autowireDetailController(
        PackageModel $packageModel,
        RouteProvider $routeProvider,
        Config $config,
        ComposerHelper $composer,
        ResourceInstallerInterface $resourceInstaller,
    ): void {
        $this->packageModel      = $packageModel;
        $this->routeProvider     = $routeProvider;
        $this->config            = $config;
        $this->composer          = $composer;
        $this->resourceInstaller = $resourceInstaller;
    }

    public function viewAction(string $vendor, string $package): Response
    {
        if (!$this->config->marketplaceIsEnabled()) {
            return $this->notFound();
        }

        if (!$this->security->isGranted(MarketplacePermissions::CAN_VIEW_PACKAGES)) {
            $this->throwAccessDenied();
        }

        try {
            $packageDetail = $this->packageModel->getPackageDetail("{$vendor}/{$package}");
        } catch (ApiException $e) {
            if (Response::HTTP_NOT_FOUND === $e->getCode()) {
                return $this->notFound();
            }

            throw $e;
        }

        $packageFullName = "{$vendor}/{$package}";
        $isResource      = 'mautic-resource' === ($packageDetail->packageBase->type ?? '');
        $isInstalled     = $isResource
            ? $this->resourceInstaller->isInstalled($packageFullName)
            : $this->composer->isInstalled($packageFullName);

        $security = $this->security;

        return $this->delegateView(
            [
                'returnUrl'      => $this->routeProvider->buildListRoute(),
                'viewParameters' => [
                    'packageDetail'         => $packageDetail,
                    'isInstalled'           => $isInstalled,
                    'isComposerEnabled'     => $this->config->isComposerEnabled(),
                    'marketplaceWebsiteUrl' => $this->config->getMarketplaceWebsiteUrl(),
                    'security'              => $security,
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
