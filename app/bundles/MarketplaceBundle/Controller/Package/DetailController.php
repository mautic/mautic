<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Controller\Package;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\MarketplaceBundle\Model\PackageModel;
use Mautic\MarketplaceBundle\Security\Permissions\MarketplacePermissions;
use Mautic\MarketplaceBundle\Service\RouteProvider;
use Symfony\Component\HttpFoundation\Response;

class DetailController extends CommonController
{
    /**
     * @var PackageModel
     */
    private $packageModel;

    /**
     * @var RouteProvider
     */
    private $routeProvider;

    /**
     * @var CorePermissions
     */
    private $corePermissions;

    public function __construct(
        PackageModel $packageModel,
        RouteProvider $routeProvider,
        CorePermissions $corePermissions
    ) {
        $this->packageModel    = $packageModel;
        $this->routeProvider   = $routeProvider;
        $this->corePermissions = $corePermissions;
    }

    public function ViewAction(string $vendor, string $package): Response
    {
        if (!$this->corePermissions->isGranted(MarketplacePermissions::CAN_VIEW_PACKAGES)) {
            return $this->accessDenied();
        }

        return $this->delegateView(
            [
                'returnUrl'      => $this->routeProvider->buildListRoute(),
                'viewParameters' => [
                    'packageDetail'  => $this->packageModel->getPackageDetail("{$vendor}/{$package}"),
                ],
                'contentTemplate' => 'MarketplaceBundle:Package:detail.html.php',
                'passthroughVars' => [
                    'mauticContent' => 'package',
                    'activeLink'    => '#mautic_marketplace',
                    'route'         => $this->routeProvider->buildDetailRoute($vendor, $package),
                ],
            ]
        );
    }
}
