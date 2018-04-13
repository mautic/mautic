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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Mautic\DashboardBundle\Model\DashboardModel;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Mautic\DashboardBundle\Controller\DashboardController;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class DashboardControllerTest extends \PHPUnit_Framework_TestCase
{
    private $requestMock;

    protected function setUp()
    {
        parent::setUp();

        $this->requestMock = $this->createMock(Request::class);
        $this->securityMock = $this->createMock(CorePermissions::class);
        $this->translatorMock = $this->createMock(TranslatorInterface::class);
        $this->modelFactoryMock = $this->createMock(ModelFactory::class);
        $this->dashboardModelMock = $this->createMock(DashboardModel::class);
        $this->routerMock = $this->createMock(RouterInterface::class);
        $this->sessionMock = $this->createMock(Session::class);
        $this->flashBagMock = $this->createMock(FlashBagInterface::class);
        $this->containerMock = $this->createMock(Container::class);
        $this->controller = new DashboardController;
        $this->controller->setRequest($this->requestMock);
        $this->controller->setContainer($this->containerMock);
        $this->controller->setTranslator($this->translatorMock);
        $this->sessionMock->method('getFlashBag')->willReturn($this->flashBagMock);
    }

    public function testSaveWithGetWillCallAccessDenied()
    {
        $this->requestMock->expects($this->once())->method('getMethod')->willReturn('GET');
        $this->containerMock->expects($this->at(0))->method('get')->with('mautic.security')->willReturn($this->securityMock);
        $this->translatorMock->expects($this->at(0))->method('trans')->with('mautic.core.url.error.401');
        $this->expectException(AccessDeniedHttpException::class);
        $this->controller->saveAction();
    }

    public function testSaveWithPostNotAjaxWillCallAccessDenied()
    {
        $this->requestMock->expects($this->once())->method('getMethod')->willReturn('POST');
        $this->requestMock->method('isXmlHttpRequest')->willReturn(false);
        $this->containerMock->expects($this->at(0))->method('get')->with('mautic.security')->willReturn($this->securityMock);
        $this->translatorMock->expects($this->at(0))->method('trans')->with('mautic.core.url.error.401');
        $this->expectException(AccessDeniedHttpException::class);
        $this->controller->saveAction();
    }

    public function testSaveWithPostAjaxWillSave()
    {
        $this->requestMock->expects($this->once())->method('getMethod')->willReturn('POST');
        $this->requestMock->method('isXmlHttpRequest')->willReturn(true);
        $this->requestMock->expects($this->at(2))->method('get')->with('name')->willReturn('mockName');
        $this->containerMock->expects($this->at(0))->method('get')->with('mautic.model.factory')->willReturn($this->modelFactoryMock);
        $this->containerMock->expects($this->at(1))->method('get')->with('router')->willReturn($this->routerMock);
        $this->containerMock->expects($this->at(2))->method('get')->with('router')->willReturn($this->routerMock);
        $this->containerMock->expects($this->at(3))->method('get')->with('translator')->willReturn($this->translatorMock);
        $this->containerMock->expects($this->at(4))->method('get')->with('session')->willReturn($this->sessionMock);
        $this->routerMock->expects($this->any(0))->method('generate')->willReturn('https://some.url');
        $this->modelFactoryMock->expects($this->at(0))->method('getModel')->with('dashboard')->willReturn($this->dashboardModelMock);
        $this->dashboardModelMock->expects($this->at(0))->method('saveSnapshot')->with('mockName');
        $this->translatorMock->expects($this->at(0))->method('trans')->with('mautic.dashboard.notice.save');
        // This exception is thrown if templating is not set. Let's take it as success to avoid further mocking.
        $this->expectException(\LogicException::class);
        $this->controller->saveAction();
    }

    public function testSaveWithPostAjaxWillNotBeAbleToSave()
    {
        $this->requestMock->expects($this->once())->method('getMethod')->willReturn('POST');
        $this->requestMock->method('isXmlHttpRequest')->willReturn(true);
        $this->requestMock->expects($this->at(2))->method('get')->with('name')->willReturn('mockName');
        $this->containerMock->expects($this->at(0))->method('get')->with('mautic.model.factory')->willReturn($this->modelFactoryMock);
        $this->containerMock->expects($this->at(1))->method('get')->with('router')->willReturn($this->routerMock);
        $this->containerMock->expects($this->at(2))->method('get')->with('translator')->willReturn($this->translatorMock);
        $this->containerMock->expects($this->at(3))->method('get')->with('session')->willReturn($this->sessionMock);
        $this->modelFactoryMock->expects($this->at(0))->method('getModel')->with('dashboard')->willReturn($this->dashboardModelMock);
        $this->dashboardModelMock->expects($this->at(0))->method('saveSnapshot')->will($this->throwException(new IOException('some error message')));
        $this->translatorMock->expects($this->at(0))->method('trans')->with('mautic.dashboard.error.save');
        // This exception is thrown if templating is not set. Let's take it as success to avoid further mocking.
        $this->expectException(\LogicException::class);
        $this->controller->saveAction();
    }
}
