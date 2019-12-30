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
use MauticPlugin\MarketplaceBundle\Service\PackageInstaller;
use MauticPlugin\MarketplaceBundle\Service\RouteProvider;
use MauticPlugin\MarketplaceBundle\Service\StreamOutput;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InstallController extends CommonController
{
    private $packageModel;
    private $packageInstaller;
    private $requestStack;
    private $routeProvider;

    public function __construct(
        PackageModel $packageModel,
        PackageInstaller $packageInstaller,
        RequestStack $requestStack,
        RouteProvider $routeProvider
    ) {
        $this->packageModel     = $packageModel;
        $this->packageInstaller = $packageInstaller;
        $this->requestStack     = $requestStack;
        $this->routeProvider    = $routeProvider;
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
        $packageName = "{$vendor}/{$package}";

        $this->setComposerTimeout();

        $response = new StreamedResponse(function () use ($packageName) {
            $this->packageInstaller->install(
                $packageName,
                new StreamOutput(fopen('php://stdout', 'w')),
                ['--optimize-autoloader' => false]
            );
        });

        return $response;
    }

    private function setComposerTimeout(): void
    {
        $timeSpentAlready = microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];
        $maxExecutionTime = (int) ini_get('max_execution_time');
        $precaution       = 3;
        $timeout          = $maxExecutionTime - $timeSpentAlready - $precaution;
        putenv("COMPOSER_PROCESS_TIMEOUT={$timeout}");
    }
}
