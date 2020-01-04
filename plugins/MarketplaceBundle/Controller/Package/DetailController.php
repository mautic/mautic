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

namespace MauticPlugin\MarketplaceBundle\Controller\Package;

use Mautic\CoreBundle\Controller\CommonController;
use MauticPlugin\MarketplaceBundle\Model\PackageModel;
use MauticPlugin\MarketplaceBundle\Service\RouteProvider;
use Symfony\Component\HttpFoundation\Response;

class DetailController extends CommonController
{
    private $packageModel;
    private $routeProvider;

    public function __construct(
        PackageModel $packageModel,
        RouteProvider $routeProvider
    ) {
        $this->packageModel  = $packageModel;
        $this->routeProvider = $routeProvider;
    }

    public function ViewAction(string $vendor, string $package): Response
    {
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
