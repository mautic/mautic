<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Tests\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Model\AbstractCommonModel;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\EmailBundle\Controller\PublicController;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\LeadBundle\Model\LeadModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PublicControllerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var EmailModel|MockObject
     */
    private $emailModelMock;

    /**
     * @var Translator|MockObject
     */
    private $translatorMock;

    /**
     * @var MockObject|Session
     */
    private $sessionMock;

    /**
     * @var LeadModel|MockObject
     */
    private $leadModelMock;

    /**
     * @var MockObject|Container
     */
    private $containerMock;

    /**
     * @var PublicController
     */
    private $controller;

    /**
     * @var ModelFactory|MockObject
     */
    private $modelFactoryMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->emailModelMock     = $this->createMock(EmailModel::class);
        $this->translatorMock     = $this->createMock(Translator::class);
        $this->sessionMock        = $this->createMock(Session::class);
        $this->leadModelMock      = $this->createMock(LeadModel::class);
        $this->containerMock      = $this->createMock(Container::class);
        $this->modelFactoryMock   = $this->createMock(ModelFactory::class);

        $this->controller = new PublicController();
        $this->controller->setContainer($this->containerMock);
        $this->controller->setTranslator($this->translatorMock);
        $this->controller->setFactory(new MauticFactory(new Container()));
        $this->controller->setRequest(new Request());
    }

    public function testUnsubscribeAction(): void
    {
//        $abstractModelMock = new \ReflectionClass(ModelFactory::class);
//        $method            = $abstractModelMock->getMethod('getModel');
//        $method->setAccessible(true);

        $this->containerMock->expects($this->any())
            ->method('get')
            ->withAnyParameters('mautic.model.factory', 'translator')
            ->willReturn($this->modelFactoryMock, $this->translatorMock);

        $this->modelFactoryMock->expects($this->any())
            ->method('getModel')
            ->withAnyParameters('email', 'lead')
            ->willReturnOnConsecutiveCalls($this->emailModelMock, $this->leadModelMock);

        $this->emailModelMock->expects($this->once())
            ->method('getEmailStatus')
            ->willReturn(1);

        $response = $this->controller->unsubscribeAction(new Request());
        $this->assertEquals($this->modelFactoryMock, $response->getContent());
    }
}
