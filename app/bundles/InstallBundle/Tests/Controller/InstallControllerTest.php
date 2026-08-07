<?php

declare(strict_types=1);

namespace Mautic\InstallBundle\Tests\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Mautic\CoreBundle\Configurator\Configurator;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\InstallBundle\Controller\InstallController;
use Mautic\InstallBundle\Install\InstallService;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Router;

final class InstallControllerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&Router
     */
    private \PHPUnit\Framework\MockObject\MockObject $routerMock;

    private InstallController $controller;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&InstallService
     */
    private \PHPUnit\Framework\MockObject\MockObject $installer;

    protected function setUp(): void
    {
        parent::setUp();

        $sessionMock                = $this->createMock(Session::class);
        $containerMock              = $this->createMock(Container::class);
        $this->routerMock           = $this->createMock(Router::class);
        $this->installer      = $this->createMock(InstallService::class);
        $requestStack         = new RequestStack();

        $this->controller = new InstallController(
            $this->createStub(ManagerRegistry::class),
            $this->createStub(ModelFactory::class),
            $this->createStub(UserHelper::class),
            $this->createStub(CoreParametersHelper::class),
            $this->createStub(EventDispatcherInterface::class),
            $this->createStub(Translator::class),
            $this->createStub(FlashBag::class),
            $requestStack,
            $this->createStub(CorePermissions::class)
        );
        $this->controller->autowireInstallController(
            $this->createStub(Configurator::class),
            $this->installer
        );
        $this->controller->setContainer($containerMock);
        $sessionMock->method('getFlashBag')->willReturn($this->createStub(FlashBagInterface::class));

        $containerMock->method('get')
            ->with('router')
            ->willReturn($this->routerMock);
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
            $this->createStub(EntityManagerInterface::class),
            $this->createStub(PathsHelper::class),
            InstallService::CHECK_STEP
        );
        $this->assertSame(\Symfony\Component\HttpFoundation\Response::HTTP_FOUND, $response->getStatusCode());
    }
}
