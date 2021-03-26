<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\DashboardBundle\Tests\Controller;

use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\CoreBundle\Templating\Engine\PhpEngine;
use Mautic\DashboardBundle\Controller\DashboardController;
use Mautic\DashboardBundle\Dashboard\Widget;
use Mautic\DashboardBundle\Model\DashboardModel;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;

class DashboardControllerTest extends \PHPUnit\Framework\TestCase
{
    private $requestMock;
    private $securityMock;
    private $translatorMock;
    private $modelFactoryMock;
    private $dashboardModelMock;
    private $routerMock;
    private $sessionMock;
    private $flashBagMock;
    private $containerMock;
    private $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->requestMock        = $this->createMock(Request::class);
        $this->securityMock       = $this->createMock(CorePermissions::class);
        $this->translatorMock     = $this->createMock(TranslatorInterface::class);
        $this->modelFactoryMock   = $this->createMock(ModelFactory::class);
        $this->dashboardModelMock = $this->createMock(DashboardModel::class);
        $this->routerMock         = $this->createMock(RouterInterface::class);
        $this->sessionMock        = $this->createMock(Session::class);
        $this->flashBagMock       = $this->createMock(FlashBag::class);
        $this->containerMock      = $this->createMock(Container::class);
        $this->controller         = new DashboardController();
        $this->controller->setRequest($this->requestMock);
        $this->controller->setContainer($this->containerMock);
        $this->controller->setTranslator($this->translatorMock);
        $this->controller->setFlashBag($this->flashBagMock);
        $this->sessionMock->method('getFlashBag')->willReturn($this->flashBagMock);
    }

    public function testSaveWithGetWillCallAccessDenied()
    {
        $this->requestMock->expects($this->once())
            ->method('isMethod')
            ->willReturn(Request::METHOD_POST)
            ->willReturn(true);

        $this->requestMock->expects(self::once())
            ->method('isXmlHttpRequest')
            ->willReturn(false);

        $this->containerMock->expects($this->at(0))
            ->method('get')
            ->with('mautic.security')
            ->willReturn($this->securityMock);

        $this->expectException(AccessDeniedHttpException::class);
        $this->controller->saveAction();
    }

    public function testSaveWithPostNotAjaxWillCallAccessDenied()
    {
        $this->requestMock->expects($this->once())
            ->method('isMethod')
            ->willReturn('POST')
            ->willReturn(true);

        $this->requestMock->method('isXmlHttpRequest')
            ->willReturn(false);

        $this->containerMock->expects($this->at(0))
            ->method('get')
            ->with('mautic.security')
            ->willReturn($this->securityMock);

        $this->translatorMock->expects($this->at(0))
            ->method('trans')
            ->with('mautic.core.url.error.401');

        $this->expectException(AccessDeniedHttpException::class);
        $this->controller->saveAction();
    }

    public function testSaveWithPostAjaxWillSave()
    {
        $this->requestMock->expects($this->once())
            ->method('isMethod')
            ->willReturn('POST')
            ->willReturn(true);

        $this->requestMock->method('isXmlHttpRequest')
            ->willReturn(true);

        $this->requestMock->expects($this->at(2))
            ->method('get')
            ->with('name')
            ->willReturn('mockName');

        $this->containerMock->expects($this->at(0))
            ->method('get')
            ->with('mautic.model.factory')
            ->willReturn($this->modelFactoryMock);

        $this->containerMock->expects($this->at(1))
            ->method('get')
            ->with('router')
            ->willReturn($this->routerMock);

        $this->containerMock->expects($this->at(2))
            ->method('get')
            ->with('router')
            ->willReturn($this->routerMock);

        $this->routerMock->expects($this->any())
            ->method('generate')
            ->willReturn('https://some.url');

        $this->modelFactoryMock->expects($this->at(0))
            ->method('getModel')
            ->with('dashboard')
            ->willReturn($this->dashboardModelMock);

        $this->dashboardModelMock->expects($this->at(0))
            ->method('saveSnapshot')
            ->with('mockName');

        $this->translatorMock->expects($this->at(0))
            ->method('trans')
            ->with('mautic.dashboard.notice.save');

        // This exception is thrown if templating is not set. Let's take it as success to avoid further mocking.
        $this->expectException(\LogicException::class);
        $this->controller->saveAction();
    }

    public function testSaveWithPostAjaxWillNotBeAbleToSave()
    {
        $this->requestMock->expects($this->once())
            ->method('isMethod')
            ->willReturn('POST')
            ->willReturn(true);

        $this->requestMock->method('isXmlHttpRequest')
            ->willReturn(true);

        $this->routerMock->expects($this->any())
            ->method('generate')
            ->willReturn('https://some.url');

        $this->requestMock->expects($this->at(2))
            ->method('get')
            ->with('name')
            ->willReturn('mockName');

        $this->containerMock->expects($this->at(0))
            ->method('get')
            ->with('mautic.model.factory')
            ->willReturn($this->modelFactoryMock);

        $this->containerMock->expects($this->at(1))
            ->method('get')
            ->with('router')
            ->willReturn($this->routerMock);

        $this->modelFactoryMock->expects($this->at(0))
            ->method('getModel')
            ->with('dashboard')
            ->willReturn($this->dashboardModelMock);

        $this->dashboardModelMock->expects($this->at(0))
            ->method('saveSnapshot')
            ->will($this->throwException(new IOException('some error message')));

        $this->translatorMock->expects($this->at(0))
            ->method('trans')
            ->with('mautic.dashboard.error.save');

        // This exception is thrown if templating is not set. Let's take it as success to avoid further mocking.
        $this->expectException(\LogicException::class);
        $this->controller->saveAction();
    }

    public function testWidgetDirectRequest(): void
    {
        $this->requestMock->method('isXmlHttpRequest')
            ->willReturn(false);

        $this->expectException(NotFoundHttpException::class);
        $this->controller->widgetAction(1);
    }

    public function testWidgetNotFound(): void
    {
        $widgetId = '1';

        $this->requestMock->method('isXmlHttpRequest')
            ->willReturn(true);

        $widgetService = $this->createMock(Widget::class);
        $widgetService->expects(self::once())
            ->method('setFilter')
            ->with($this->requestMock);
        $widgetService->expects(self::once())
            ->method('get')
            ->with((int) $widgetId)
            ->willReturn(null);

        $this->containerMock->expects(self::once())
            ->method('get')
            ->with('mautic.dashboard.widget')
            ->willReturn($widgetService);

        $this->expectException(NotFoundHttpException::class);
        $this->controller->widgetAction($widgetId);
    }

    public function testWidget(): void
    {
        $widgetId        = '1';
        $widget          = new \Mautic\DashboardBundle\Entity\Widget();
        $renderedContent = 'lfsadkdhfůasfjds';

        $this->requestMock->method('isXmlHttpRequest')
            ->willReturn(true);

        $widgetService = $this->createMock(Widget::class);
        $widgetService->expects(self::once())
            ->method('setFilter')
            ->with($this->requestMock);
        $widgetService->expects(self::once())
            ->method('get')
            ->with((int) $widgetId)
            ->willReturn($widget);

        $this->containerMock->expects(self::at(0))
            ->method('get')
            ->with('mautic.dashboard.widget')
            ->willReturn($widgetService);

        $this->containerMock->expects(self::once())
            ->method('has')
            ->with('templating')
            ->willReturn(true);

        $engine = $this->createMock(PhpEngine::class);
        $engine->expects(self::once())
            ->method('render')
            ->willReturn($renderedContent);

        $this->containerMock->expects(self::at(2))
            ->method('get')
            ->with('templating')
            ->willReturn($engine);

        $response = $this->controller->widgetAction($widgetId);

        self::assertSame('{"success":1,"widgetId":"1","widgetHtml":"lfsadkdhf\u016fasfjds","widgetWidth":null,"widgetHeight":null}', $response->getContent());
    }
}
