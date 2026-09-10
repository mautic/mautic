<?php

declare(strict_types=1);

namespace Mautic\DashboardBundle\Tests\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\PageBundle\Model\PageModel;
use Mautic\DashboardBundle\Controller\DashboardController;
use Mautic\DashboardBundle\Dashboard\Widget;
use Mautic\DashboardBundle\Model\DashboardModel;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

#[AllowMockObjectsWithoutExpectations]
final class DashboardControllerTest extends \PHPUnit\Framework\TestCase
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
        $this->translatorMock     = $this->createMock(Translator::class);
        $requestStack             = new RequestStack([$this->requestMock]);

        // The mock does not run the Request constructor, so initialize the bags manually.
        $this->requestMock->attributes = new InputBag();
        $this->requestMock->query      = new InputBag();
        $this->requestMock->request    = new InputBag();

        $this->controller = new DashboardController(
            $this->createStub(ManagerRegistry::class),
            $this->createStub(ModelFactory::class),
            $this->createStub(UserHelper::class),
            $this->createStub(CoreParametersHelper::class),
            $this->createStub(EventDispatcherInterface::class),
            $this->translatorMock,
            $this->createStub(FlashBag::class),
            $requestStack,
            $this->createStub(CorePermissions::class)
        );

        $notificationModel = $this->createStub(NotificationModel::class);
        $notificationModel->method('getNotificationContent')->willReturn([[], false, null]);

        $this->controller->setContainer($this->containerMock);
        $this->controller->autowireDashboardController($this->dashboardModelMock);
        $this->controller->autowireCommonController(
            $this->createStub(PageModel::class),
            $notificationModel,
            $this->routerMock,
            $this->createStub(HttpKernelInterface::class),
            $this->createStub(Environment::class)
        );
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
        $this->requestMock->query = new InputBag(['name' => 'mockName']);

        $twig = $this->createStub(Environment::class);
        $twig->method('render')->willReturn('');

        $this->containerMock->method('has')->willReturnCallback(fn (string $id): bool => 'twig' === $id);
        $this->containerMock
            ->method('get')
            ->willReturnCallback(fn (string $id): object => match ($id) {
                'router' => $this->routerMock,
                'twig'   => $twig,
                default  => throw new \LogicException("Unexpected service {$id}"),
            });

        $this->routerMock
            ->method('generate')
            ->willReturn('https://some.url');

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

        $this->routerMock
            ->method('generate')
            ->willReturn('https://some.url');

        $this->requestMock->query = new InputBag(['name' => 'mockName']);

        $twig = $this->createStub(Environment::class);
        $twig->method('render')->willReturn('');

        $this->containerMock->method('has')->willReturnCallback(fn (string $id): bool => 'twig' === $id);
        $this->containerMock
            ->method('get')
            ->willReturnCallback(fn (string $id): object => match ($id) {
                'router' => $this->routerMock,
                'twig'   => $twig,
                default  => throw new \LogicException("Unexpected service {$id}"),
            });

        $this->dashboardModelMock->expects($this->once())
            ->method('saveSnapshot')
            ->willThrowException(new IOException('some error message'));

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

        $this->containerMock->expects($this->never())
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

        $this->assertSame('{"success":1,"widgetId":"1","widgetHtml":"lfsadkdhf\u016fasfjds","widgetWidth":null,"widgetHeight":null}', $response->getContent());
    }
}
