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
use Mautic\EmailBundle\Controller\AjaxController;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Model\EmailModel;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Translation\TranslatorInterface;

class AjaxControllerTest extends \PHPUnit_Framework_TestCase
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
        $this->modelMock        = $this->createMock(EmailModel::class);
        $this->emailMock        = $this->createMock(Email::class);
        $this->controller       = new AjaxController();
        $this->controller->setContainer($this->containerMock);
        $this->controller->setTranslator($this->translatorMock);
    }

    public function testSendBatchActionWhenNoIdProvided()
    {
        $this->containerMock
            ->expects($this->at(0))
            ->method('get')
            ->with('mautic.model.factory')
            ->willReturn($this->modelFactoryMock);

        $this->modelFactoryMock
            ->expects($this->at(0))
            ->method('getModel')
            ->with('email')
            ->willReturn($this->modelMock);

        $response = $this->controller->sendBatchAction(new Request([], []));

        $this->assertEquals('{"success":0}', $response->getContent());
    }

    public function testSendBatchActionWhenIdProvidedButEmailNotPublished()
    {
        $this->containerMock->expects($this->at(0))
            ->method('get')
            ->with('mautic.model.factory')
            ->willReturn($this->modelFactoryMock);

        $this->containerMock->expects($this->at(1))
            ->method('get')
            ->with('session')
            ->willReturn($this->sessionMock);

        $this->modelFactoryMock->expects($this->at(0))
            ->method('getModel')
            ->with('email')
            ->willReturn($this->modelMock);

        $this->modelMock->expects($this->once())
            ->method('getEntity')
            ->with(5)
            ->willReturn($this->emailMock);

        $this->modelMock->expects($this->never())
            ->method('sendEmailToLists');

        $this->sessionMock->expects($this->at(0))
            ->method('get')
            ->with('mautic.email.send.progress')
            ->willReturn([0, 100]);

        $this->sessionMock->expects($this->at(1))
            ->method('get')
            ->with('mautic.email.send.stats')
            ->willReturn(['sent' => 0, 'failed' => 0, 'failedRecipients' => []]);

        $this->sessionMock->expects($this->at(2))
            ->method('get')
            ->with('mautic.email.send.active')
            ->willReturn(false);

        $this->emailMock->expects($this->once())
            ->method('isPublished')
            ->willReturn(false);

        $response = $this->controller->sendBatchAction(new Request([], ['id' => 5, 'pending' => 100]));
        $expected = '{"success":1,"percent":0,"progress":[0,100],"stats":{"sent":0,"failed":0,"failedRecipients":[]}}';
        $this->assertEquals($expected, $response->getContent());
    }

    public function testSendBatchActionWhenIdProvidedAndEmailIsPublished()
    {
        $this->containerMock->expects($this->at(0))
            ->method('get')
            ->with('mautic.model.factory')
            ->willReturn($this->modelFactoryMock);

        $this->containerMock->expects($this->at(1))
            ->method('get')
            ->with('session')
            ->willReturn($this->sessionMock);

        $this->modelFactoryMock->expects($this->at(0))
            ->method('getModel')
            ->with('email')
            ->willReturn($this->modelMock);

        $this->modelMock->expects($this->once())
            ->method('getEntity')
            ->with(5)
            ->willReturn($this->emailMock);

        $this->modelMock->expects($this->once())
            ->method('sendEmailToLists')
            ->with($this->emailMock, null, 50)
            ->willReturn([50, 0, []]);

        $this->sessionMock->expects($this->at(0))
            ->method('get')
            ->with('mautic.email.send.progress')
            ->willReturn([0, 100]);

        $this->sessionMock->expects($this->at(1))
            ->method('get')
            ->with('mautic.email.send.stats')
            ->willReturn(['sent' => 0, 'failed' => 0, 'failedRecipients' => []]);

        $this->sessionMock->expects($this->at(2))
            ->method('get')
            ->with('mautic.email.send.active')
            ->willReturn(false);

        $this->emailMock->expects($this->once())
            ->method('isPublished')
            ->willReturn(true);

        $response = $this->controller->sendBatchAction(new Request([], ['id' => 5, 'pending' => 100, 'batchlimit' => 50]));
        $expected = '{"success":1,"percent":50,"progress":[50,100],"stats":{"sent":50,"failed":0,"failedRecipients":[]}}';
        $this->assertEquals($expected, $response->getContent());
    }
}
