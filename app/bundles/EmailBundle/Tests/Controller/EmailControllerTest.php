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

use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\EmailBundle\Controller\EmailController;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Templating\DelegatingEngine;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Router;
use Symfony\Component\Translation\TranslatorInterface;

class EmailControllerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|TranslatorInterface
     */
    private $translatorMock;

    /**
     * @var MockObject|Session
     */
    private $sessionMock;

    /**
     * @var MockObject|ModelFactory
     */
    private $modelFactoryMock;

    /**
     * @var MockObject|Container
     */
    private $containerMock;

    /**
     * @var MockObject|Router
     */
    private $routerMock;

    /**
     * @var MockObject|EmailModel
     */
    private $modelMock;

    /**
     * @var MockObject|Email
     */
    private $emailMock;

    /**
     * @var MockObject|FlashBag
     */
    private $flashBagMock;

    /**
     * @var EmailController
     */
    private $controller;

    /**
     * @var MockObject|CorePermissions
     */
    private $corePermissionsMock;

    /**
     * @var MockObject|UserHelper
     */
    private $helperUserMock;

    /**
     * @var MockObject|FormFactory
     */
    private $formFactoryMock;

    /**
     * @var MockObject|Form
     */
    private $formMock;

    /**
     * @var MockObject|DelegatingEngine
     */
    private $templatingMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translatorMock       = $this->createMock(TranslatorInterface::class);
        $this->sessionMock          = $this->createMock(Session::class);
        $this->modelFactoryMock     = $this->createMock(ModelFactory::class);
        $this->containerMock        = $this->createMock(Container::class);
        $this->routerMock           = $this->createMock(Router::class);
        $this->modelMock            = $this->createMock(EmailModel::class);
        $this->emailMock            = $this->createMock(Email::class);
        $this->flashBagMock         = $this->createMock(FlashBag::class);
        $this->corePermissionsMock  = $this->createMock(CorePermissions::class);
        $this->helperUserMock       = $this->createMock(UserHelper::class);
        $this->formFactoryMock      = $this->createMock(FormFactory::class);
        $this->formMock             = $this->createMock(Form::class);
        $this->templatingMock       = $this->createMock(DelegatingEngine::class);
        $this->controller           = new EmailController();
        $this->controller->setContainer($this->containerMock);
        $this->controller->setTranslator($this->translatorMock);
        $this->controller->setFlashBag($this->flashBagMock);
        $this->sessionMock->method('getFlashBag')->willReturn($this->flashBagMock);
        $this->controller->setRequest(new Request());
    }

    public function testSendActionWhenNoEntityFound(): void
    {
        $this->containerMock->expects($this->exactly(3))
            ->method('get')
            ->withConsecutive(['mautic.model.factory'], ['session'], ['router'])
            ->willReturnOnConsecutiveCalls($this->modelFactoryMock, $this->sessionMock, $this->routerMock);

        $this->modelFactoryMock->expects($this->once())
            ->method('getModel')
            ->with('email')
            ->willReturn($this->modelMock);

        $this->modelMock->expects($this->once())
            ->method('getEntity')
            ->with(5)
            ->willReturn(null);

        $this->routerMock->expects($this->any())
            ->method('generate')
            ->willReturn('https://some.url');

        $this->emailMock->expects($this->never())
            ->method('isPublished');

        $response = $this->controller->sendAction(5);
        $this->assertEquals(302, $response->getStatusCode());
    }

    public function testSendActionWhenEnityFoundButNotPublished(): void
    {
        $this->containerMock->expects($this->exactly(3))
            ->method('get')
            ->withConsecutive(['mautic.model.factory'], ['session'], ['router'])
            ->willReturnOnConsecutiveCalls($this->modelFactoryMock, $this->sessionMock, $this->routerMock);

        $this->modelFactoryMock->expects($this->once())
            ->method('getModel')
            ->with('email')
            ->willReturn($this->modelMock);

        $this->modelMock->expects($this->once())
            ->method('getEntity')
            ->with(5)
            ->willReturn($this->emailMock);

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

    public function testThatExampleEmailsHaveTestStringInTheirSubject(): void
    {
        $this->emailMock->expects($this->once())
            ->method('setSubject')
            ->with($this->stringStartsWith(EmailController::EXAMPLE_EMAIL_SUBJECT_PREFIX));

        $this->containerMock->expects($this->exactly(7))
            ->method('get')
            ->withConsecutive(
                ['mautic.model.factory'],
                ['mautic.security'],
                ['router'],
                ['mautic.helper.user'],
                ['form.factory'],
                ['templating'],
                ['templating']
            )
            ->willReturnOnConsecutiveCalls(
                $this->modelFactoryMock,
                $this->corePermissionsMock,
                $this->routerMock,
                $this->helperUserMock,
                $this->formFactoryMock,
                $this->templatingMock,
                $this->templatingMock
            );

        $this->templatingMock->method('supports')
            ->willReturn(true);

        $this->modelFactoryMock->expects($this->once())
            ->method('getModel')
            ->with('email')
            ->willReturn($this->modelMock);

        $this->modelMock->expects($this->once())
            ->method('getEntity')
            ->with(1)
            ->willReturn($this->emailMock);

        $this->corePermissionsMock->expects($this->once())
            ->method('hasEntityAccess')
            ->with('email:emails:viewown', 'email:emails:viewother', null)
            ->willReturn(true);

        $this->routerMock->expects($this->once())
            ->method('generate')
            ->with('mautic_email_action', [
                'objectAction' => 'sendExample',
                'objectId'     => 1,
            ], 1)
            ->willReturn('someUrl');

        $this->helperUserMock->expects($this->once())
            ->method('getUser')
            ->willReturn(new User(false));

        $this->formFactoryMock->expects($this->once())
            ->method('create')
            ->with('Mautic\EmailBundle\Form\Type\ExampleSendType',
                [
                    'emails' => [
                        'list' => [
                            0 => null,
                        ],
                    ],
                ],
                [
                    'action' => 'someUrl',
                ]
            )
            ->willReturn($this->formMock);

        $this->containerMock->expects($this->once())
            ->method('has')
            ->with('templating')
            ->willReturn(true);

        $this->templatingMock->expects($this->once())
            ->method('render')
            ->willReturn('');

        $this->controller->sendExampleAction(1);
    }
}
