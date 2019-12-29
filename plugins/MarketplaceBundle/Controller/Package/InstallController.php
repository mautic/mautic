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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class InstallController extends CommonController
{
    private $packageModel;
    private $requestStack;
    private $routeProvider;

    public function __construct(
        PackageModel $packageModel,
        RequestStack $requestStack,
        RouteProvider $routeProvider
    ) {
        $this->packageModel  = $packageModel;
        $this->requestStack  = $requestStack;
        $this->routeProvider = $routeProvider;
    }

    public function ViewAction(string $vendor, string $package): Response
    {
        $route = $this->routeProvider->buildListRoute();

        return $this->delegateView(
            [
                'returnUrl'      => $route,
                'viewParameters' => [
                    'packageDetail'  => $this->packageModel->getPackageDetail("{$vendor}/{$package}"),
                ],
                'contentTemplate' => 'MarketplaceBundle:Package:install.html.php',
                'passthroughVars' => [
                    'mauticContent' => 'package',
                    'activeLink'    => '#mautic_marketplace',
                    'route'         => $route,
                ],
            ]
        );
    }

    public function StepComposerAction(string $vendor, string $package): Response
    {
        return new JsonResponse('lalala');
    }
}
