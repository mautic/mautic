<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Tests\Controller;

use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\EmailBundle\Controller\EmailController;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Model\EmailModel;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Router;
use Symfony\Component\Translation\TranslatorInterface;

class EmailControllerTest extends \PHPUnit_Framework_TestCase
{
    private $translatorMock;
    private $sessionMock;
    private $modelFactoryMock;
    private $containerMock;
    private $modelMock;
    private $emailMock;
    private $controller;

    protected function setUp()
    {
        parent::setUp();

        $this->translatorMock   = $this->createMock(TranslatorInterface::class);
        $this->sessionMock      = $this->createMock(Session::class);
        $this->modelFactoryMock = $this->createMock(ModelFactory::class);
        $this->containerMock    = $this->createMock(Container::class);
        $this->routerMock       = $this->createMock(Router::class);
        $this->modelMock        = $this->createMock(EmailModel::class);
        $this->emailMock        = $this->createMock(Email::class);
        $this->flashBagMock     = $this->createMock(FlashBagInterface::class);
        $this->controller       = new EmailController();
        $this->controller->setContainer($this->containerMock);
        $this->controller->setTranslator($this->translatorMock);
        $this->sessionMock->method('getFlashBag')->willReturn($this->flashBagMock);
        $this->controller->setRequest(new Request());
    }

    public function testSendActionWhenNoEntityFound()
    {
        $this->containerMock->expects($this->at(0))
            ->method('get')
            ->with('mautic.model.factory')
            ->willReturn($this->modelFactoryMock);

        $this->modelFactoryMock->expects($this->at(0))
            ->method('getModel')
            ->with('email')
            ->willReturn($this->modelMock);

        $this->modelMock->expects($this->at(0))
            ->method('getEntity')
            ->with(5)
            ->willReturn(null);

        $this->containerMock->expects($this->at(1))
            ->method('get')
            ->with('session')
            ->willReturn($this->sessionMock);

        $this->containerMock->expects($this->at(2))
            ->method('get')
            ->with('router')
            ->willReturn($this->routerMock);

        $this->containerMock->expects($this->at(3))
            ->method('get')
            ->with('translator')
            ->willReturn($this->translatorMock);

        $this->containerMock->expects($this->at(4))
            ->method('get')
            ->with('session')
            ->willReturn($this->sessionMock);

        $this->routerMock->expects($this->any())
            ->method('generate')
            ->willReturn('https://some.url');

        $this->emailMock->expects($this->never())
            ->method('isPublished');

        $response = $this->controller->sendAction(5);
        $this->assertEquals(302, $response->getStatusCode());
    }

    public function testSendActionWhenEnityFoundButNotPublished()
    {
        $this->containerMock->expects($this->at(0))
            ->method('get')
            ->with('mautic.model.factory')
            ->willReturn($this->modelFactoryMock);

        $this->modelFactoryMock->expects($this->at(0))
            ->method('getModel')
            ->with('email')
            ->willReturn($this->modelMock);

        $this->modelMock->expects($this->at(0))
            ->method('getEntity')
            ->with(5)
            ->willReturn($this->emailMock);

        $this->containerMock->expects($this->at(1))
            ->method('get')
            ->with('session')
            ->willReturn($this->sessionMock);

        $this->containerMock->expects($this->at(2))
            ->method('get')
            ->with('router')
            ->willReturn($this->routerMock);

        $this->containerMock->expects($this->at(3))
            ->method('get')
            ->with('translator')
            ->willReturn($this->translatorMock);

        $this->containerMock->expects($this->at(4))
            ->method('get')
            ->with('session')
            ->willReturn($this->sessionMock);

        $this->routerMock->expects($this->any())
            ->method('generate')
            ->willReturn('https://some.url');

        $this->emailMock->expects($this->once())
            ->method('isPublished')
            ->willReturn(false);

        $this->emailMock->expects($this->never())
            ->method('getEmailType');

        $response = $this->controller->sendAction(5);
        $this->assertEquals(302, $response->getStatusCode());
    }
}
