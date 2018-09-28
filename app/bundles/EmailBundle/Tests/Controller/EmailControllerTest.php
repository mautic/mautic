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
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\EmailBundle\Controller\EmailController;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\UserBundle\Entity\User;
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
    private $translatorMock;
    private $sessionMock;
    private $modelFactoryMock;
    private $containerMock;
    private $routerMock;
    private $modelMock;
    private $emailMock;
    private $flashBagMock;
    private $controller;
    private $corePermissionsMock;
    private $helperUserMock;
    private $formFactoryMock;
    private $formMock;
    private $templatingMock;

    protected function setUp()
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
        $this->controller->setTranslator($this->translatorMock);
        $this->controller->setFlashBag($this->flashBagMock);
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

    public function testThatExampleEmailsHaveTestStringInTheirSubject()
    {
        $this->emailMock->expects($this->once())
            ->method('setSubject')
            ->with($this->stringStartsWith(EmailController::EXAMPLE_EMAIL_SUBJECT_PREFIX));

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
            ->with(1)
            ->willReturn($this->emailMock);

        $this->containerMock->expects($this->at(1))
            ->method('get')
            ->with('mautic.security')
            ->willReturn($this->corePermissionsMock);

        $this->corePermissionsMock->expects($this->at(0))
            ->method('hasEntityAccess')
            ->with('email:emails:viewown', 'email:emails:viewother', null)
            ->willReturn(true);

        $this->containerMock->expects($this->at(2))
            ->method('get')
            ->with('router')
            ->willReturn($this->routerMock);

        $this->routerMock->expects($this->at(0))
            ->method('generate')
            ->with('mautic_email_action', [
                'objectAction' => 'sendExample',
                'objectId'     => 1,
            ], 1)
            ->willReturn('someUrl');

        $this->containerMock->expects($this->at(3))
            ->method('get')
            ->with('mautic.helper.user')
            ->willReturn($this->helperUserMock);

        $this->helperUserMock->expects($this->at(0))
            ->method('getUser')
            ->willReturn(new User(false));

        $this->containerMock->expects($this->at(4))
            ->method('get')
            ->with('form.factory')
            ->willReturn($this->formFactoryMock);

        $this->formFactoryMock->expects($this->at(0))
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

        $this->containerMock->expects($this->at(5))
            ->method('has')
            ->with('templating')
            ->willReturn(true);

        $this->containerMock->expects($this->at(6))
            ->method('get')
            ->with('templating')
            ->willReturn($this->templatingMock);

        $this->templatingMock->expects($this->once())
            ->method('render')
            ->willReturn('');

        $this->controller->sendExampleAction(1);
    }
}
