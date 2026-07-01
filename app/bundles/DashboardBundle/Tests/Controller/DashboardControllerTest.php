<?php

declare(strict_types=1);

namespace Mautic\DashboardBundle\Tests\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\DashboardBundle\Controller\DashboardController;
use Mautic\DashboardBundle\Dashboard\Widget;
use Mautic\DashboardBundle\Model\DashboardModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

class DashboardControllerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject&Request
     */
    private MockObject $requestMock;

    /**
     * @var MockObject&Translator
     */
    private MockObject $translatorMock;

    /**
     * @var MockObject&ModelFactory
     */
    private MockObject $modelFactoryMock;

    /**
     * @var MockObject&DashboardModel
     */
    private MockObject $dashboardModelMock;

    /**
     * @var MockObject&RouterInterface
     */
    private MockObject $routerMock;

    /**
     * @var MockObject&Container
     */
    private MockObject $containerMock;

    private DashboardController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->requestMock        = $this->createMock(Request::class);
        $this->dashboardModelMock = $this->createMock(DashboardModel::class);
        $this->routerMock         = $this->createMock(RouterInterface::class);
        $this->containerMock      = $this->createMock(Container::class);

        $doctrine                 = $this->createMock(ManagerRegistry::class);
        $this->modelFactoryMock   = $this->createMock(ModelFactory::class);
        $userHelper               = $this->createMock(UserHelper::class);
        $coreParametersHelper     = $this->createMock(CoreParametersHelper::class);
        $dispatcher               = $this->createMock(EventDispatcherInterface::class);
        $this->translatorMock     = $this->createMock(Translator::class);
        $flashBagMock             = $this->createMock(FlashBag::class);
        $requestStack             = new RequestStack();
        $securityMock             = $this->createMock(CorePermissions::class);

        $requestStack->push($this->requestMock);
        $this->controller = new DashboardController(
            $doctrine,
            $this->modelFactoryMock,
            $userHelper,
            $coreParametersHelper,
            $dispatcher,
            $this->translatorMock,
            $flashBagMock,
            $requestStack,
            $securityMock
        );
        $this->controller->setContainer($this->containerMock);
    }

    public function testSaveWithGetWillCallAccessDenied(): void
    {
        $this->requestMock->expects($this->once())
            ->method('isMethod')
            ->willReturn(true);

        $this->requestMock->expects($this->once())
            ->method('isXmlHttpRequest')
            ->willReturn(false);

        $this->expectException(AccessDeniedHttpException::class);
        $this->controller->saveAction($this->requestMock);
    }

    public function testSaveWithPostNotAjaxWillCallAccessDenied(): void
    {
        $this->requestMock->expects($this->once())
            ->method('isMethod')
            ->willReturn(true);

        $this->requestMock->method('isXmlHttpRequest')
            ->willReturn(false);

        $this->translatorMock->expects($this->once())
            ->method('trans')
            ->with('mautic.core.url.error.401');

        $this->expectException(AccessDeniedHttpException::class);
        $this->controller->saveAction($this->requestMock);
    }

    public function testSaveWithPostAjaxWillSave(): void
    {
        $this->requestMock->expects($this->once())
            ->method('isMethod')
            ->willReturn(true);

        $this->requestMock->method('isXmlHttpRequest')->willReturn(true);
        $this->requestMock->method('get')->willReturn('mockName');

        $this->containerMock->expects($this->exactly(2))
            ->method('get')->willReturnCallback(function (...$parameters): MockObject {
                $this->assertSame('router', $parameters[0]);

                return $this->routerMock;
            });

        $this->routerMock->expects($this->any())
            ->method('generate')
            ->willReturn('https://some.url');

        $this->modelFactoryMock->expects($this->once())
            ->method('getModel')
            ->with('dashboard')
            ->willReturn($this->dashboardModelMock);

        $this->dashboardModelMock->expects($this->once())
            ->method('saveSnapshot')
            ->with('mockName');

        $this->translatorMock->expects($this->once())
            ->method('trans')
            ->with('mautic.dashboard.notice.save');

        $this->controller->saveAction($this->requestMock);
    }

    public function testSaveWithPostAjaxWillNotBeAbleToSave(): void
    {
        $this->requestMock->expects($this->once())
            ->method('isMethod')
            ->willReturn(true);

        $this->requestMock->method('isXmlHttpRequest')
            ->willReturn(true);

        $this->routerMock->expects($this->any())
            ->method('generate')
            ->willReturn('https://some.url');

        $this->requestMock->method('get')->willReturn('mockName');

        $this->containerMock->expects($this->once())
            ->method('get')
            ->with('router')
            ->willReturn($this->routerMock);

        $this->modelFactoryMock->expects($this->once())
            ->method('getModel')
            ->with('dashboard')
            ->willReturn($this->dashboardModelMock);

        $this->dashboardModelMock->expects($this->once())
            ->method('saveSnapshot')
            ->will($this->throwException(new IOException('some error message')));

        $this->translatorMock->expects($this->once())
            ->method('trans')
            ->with('mautic.dashboard.error.save');

        $this->controller->saveAction($this->requestMock);
    }

    public function testWidgetDirectRequest(): void
    {
        $this->requestMock->method('isXmlHttpRequest')
            ->willReturn(false);

        $this->expectException(NotFoundHttpException::class);
        $this->controller->widgetAction($this->requestMock, $this->createStub(Widget::class), $this->createStub(Environment::class), 1);
    }

    public function testWidgetNotFound(): void
    {
        $widgetId = '1';
        $twig     = $this->createStub(Environment::class);

        $this->requestMock->method('isXmlHttpRequest')
            ->willReturn(true);

        $widgetService = $this->createMock(Widget::class);
        $widgetService->expects($this->once())
            ->method('setFilter')
            ->with($this->requestMock);
        $widgetService->expects($this->once())
            ->method('get')
            ->with((int) $widgetId)
            ->willReturn(null);

        $this->containerMock->expects(self::never())
            ->method('get');

        $this->expectException(NotFoundHttpException::class);
        $this->controller->widgetAction($this->requestMock, $widgetService, $twig, $widgetId);
    }

    public function testWidget(): void
    {
        $widgetId        = '1';
        $widget          = new \Mautic\DashboardBundle\Entity\Widget();
        $renderedContent = 'lfsadkdhfůasfjds';
        $twig            = $this->createMock(Environment::class);

        $twig->expects($this->once())
            ->method('render')
            ->willReturn($renderedContent);

        $this->requestMock->method('isXmlHttpRequest')
            ->willReturn(true);

        $widgetService = $this->createMock(Widget::class);
        $widgetService->expects($this->once())
            ->method('setFilter')
            ->with($this->requestMock);
        $widgetService->expects($this->once())
            ->method('get')
            ->with((int) $widgetId)
            ->willReturn($widget);

        $response = $this->controller->widgetAction($this->requestMock, $widgetService, $twig, $widgetId);

        self::assertSame('{"success":1,"widgetId":"1","widgetHtml":"lfsadkdhf\u016fasfjds","widgetWidth":null,"widgetHeight":null}', $response->getContent());
    }
}
