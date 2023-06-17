<?php

namespace Mautic\PageBundle\Tests\EventListener;

use Monolog\Logger;
use Mautic\CoreBundle\Event\BuildJsEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Mautic\PageBundle\EventListener\BuildJsSubscriber;
use Mautic\CoreBundle\Twig\Helper\AssetsHelper;
use Symfony\Component\Asset\Packages;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Symfony\Component\Routing\RouterInterface;
use Mautic\PageBundle\Helper\TrackingHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\CoreEvents;
use PHPUnit\Framework\TestCase;

class BuildJsSubscriberTest extends TestCase
{
    public function testBuildMauticJsReturnsGtagAnalytics()
    {
        $dispatcher = new EventDispatcher();
        $subscriber = $this->getBuildJsSubscriber();

        $dispatcher->addSubscriber($subscriber);

        $event = new BuildJsEvent($this->getJsHeader(), false);

        $dispatcher->dispatch($event, CoreEvents::BUILD_MAUTIC_JS);

        $result = strpos($event->getJs(), 'https://www.googletagmanager.com/gtag/js?id=G-F3825DS9CD');
        $this->assertNotEquals($result, false);
    }

    /**
     * Get buildjs subscriber with mocked dependencies.
     */
    protected function getBuildJsSubscriber(): BuildJsSubscriber
    {
        /** @var Packages&MockObject $packagesMock */
        $packagesMock = $this->createMock(Packages::class);

        /** @var CoreParametersHelper&MockObject $coreParametersHelper */
        $coreParametersHelper = $this->createMock(CoreParametersHelper::class);

        $assetsHelperMock   = new AssetsHelper($packagesMock, $coreParametersHelper);
        $pathsHelperMock  = $this->createMock(PathsHelper::class);
        $pathsHelperMock->method('getSystemPath')->willReturn('http://localhost');
        $assetsHelperMock->setPathsHelper($pathsHelperMock);

        $trackingHelperMock   = $this->createMock(TrackingHelper::class);
        $trackingHelperMock->method('displayInitCode')->willReturnCallback(function ($key) {
            switch ($key) {
                case 'google_analytics':
                    return 'G-F3825DS9CD';
                    break;
            };
            return false;
        });

        $routerHelperMock   = $this->createMock(RouterInterface::class);
        $routerHelperMock->method('generate')->willReturn('http://localhost');

        return new BuildJsSubscriber(
            $assetsHelperMock,
            $trackingHelperMock,
            $routerHelperMock
        );
    }

     /**
     * Build a JS header for the Mautic embedded JS.
     *
     * @return string
     */
    protected function getJsHeader()
    {
        $year = date('Y');

        return <<<JS
/**
 * @package     MauticJS
 * @copyright   {$year} Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
JS;
    }
}