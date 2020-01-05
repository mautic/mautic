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
use MauticPlugin\MarketplaceBundle\DTO\Version;
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
        $packageDetail = $this->packageModel->getPackageDetail("{$vendor}/{$package}");
        try {
            // @todo set the stability the user really wants.
            $version = $packageDetail->getVersions()->findLatestVersionPackage(Version::STABILITY_BETA);
        } catch (\Throwable $e) {
            $version = null;
        }

        return $this->delegateView(
            [
                'returnUrl'      => $this->routeProvider->buildDetailRoute($vendor, $package),
                'viewParameters' => [
                    'packageDetail'    => $packageDetail,
                    'version'          => $version,
                    'maxExecutionTime' => $this->getMaxExecutionTime(),
                ],
                'contentTemplate' => 'MarketplaceBundle:Package:install.html.php',
                'passthroughVars' => [
                    'mauticContent' => 'package',
                    'activeLink'    => '#mautic_marketplace',
                    'route'         => $this->routeProvider->buildIntallRoute($vendor, $package),
                ],
            ]
        );
    }

    public function StepComposerAction(string $vendor, string $package): Response
    {
        $packageName = "{$vendor}/{$package}";

        $this->setComposerTimeout();

        return new StreamedResponse(function () use ($packageName) {
            $this->packageInstaller->install(
                $packageName,
                new StreamOutput(fopen('php://stdout', 'w')),
                ['--optimize-autoloader' => false]
            );
        });
    }

    public function StepDatabaseAction(): Response
    {
        $output = new StreamOutput(fopen('php://stdout', 'w'));

        return new StreamedResponse(function () use ($output) {
            $output->writeln('Starting to refresh Mautic plugins');
            $output->writeln($this->reloadFacade->reloadPlugins());
            $output->writeln('Plugin successfully installed');
        });
    }

    private function setComposerTimeout(): void
    {
        $timeSpentAlready = microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];
        $maxExecutionTime = $this->getMaxExecutionTime();
        $precaution       = 3;
        $timeout          = $maxExecutionTime - $timeSpentAlready - $precaution;
        putenv("COMPOSER_PROCESS_TIMEOUT={$timeout}");
    }

    /**
     * Returns max_execution_time from php.ini. In seconds.
     */
    private function getMaxExecutionTime(): int
    {
        return (int) ini_get('max_execution_time');
    }
}
