<?php

namespace Mautic\InstallBundle\Tests\Controller;

use AppKernel;
use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Configurator\Configurator;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\InstallBundle\Controller\InstallController;
use Mautic\InstallBundle\Install\InstallService;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Router;

class InstallControllerTest extends \PHPUnit\Framework\TestCase
{
    private $translatorMock;
    private $sessionMock;
    private $containerMock;
    private $routerMock;
    private $flashBagMock;
    private $controller;
    private $pathsHelper;

    private $configurator;
    private $installer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translatorMock       = $this->createMock(Translator::class);
        $this->sessionMock          = $this->createMock(Session::class);
        $this->containerMock        = $this->createMock(Container::class);
        $this->routerMock           = $this->createMock(Router::class);
        $this->flashBagMock         = $this->createMock(FlashBagInterface::class);
        $this->pathsHelper          = $this->createMock(PathsHelper::class);

        $this->configurator         = $this->createMock(Configurator::class);
        $this->installer            = $this->createMock(InstallService::class);

        $this->controller           = new InstallController($this->configurator, $this->installer);
        $this->controller->setContainer($this->containerMock);
        $this->controller->setTranslator($this->translatorMock);
        $this->sessionMock->method('getFlashBag')->willReturn($this->flashBagMock);

        $this->containerMock->method('get')
            ->with('router')
            ->willReturn($this->routerMock);

        $kernel  = new AppKernel(MAUTIC_ENV, false);
        $request = $this->createMock(Request::class);

        $event = new ControllerEvent($kernel, fn () => $this->controller, $request, HttpKernelInterface::MAIN_REQUEST);

        $this->controller->initialize($event);
    }

    public function testStepActionWhenInstalled(): void
    {
        $this->installer->expects($this->once())
            ->method('checkIfInstalled')
            ->willReturn(
                true
            );

        $this->routerMock->expects($this->once())
            ->method('generate')
            ->with('mautic_dashboard_index', [], UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn('http://localhost/');

        $response = $this->controller->stepAction(
            new Request(),
            $this->createMock(EntityManagerInterface::class),
            $this->pathsHelper,
            InstallService::CHECK_STEP
        );
        $this->assertEquals(302, $response->getStatusCode());
    }
}
