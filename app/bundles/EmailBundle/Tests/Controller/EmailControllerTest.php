<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\EmailBundle\Controller\EmailController;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\FormBundle\Helper\FormFieldHelper;
use Mautic\LeadBundle\Helper\FakeContactHelper;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Router;
use Twig\Environment;

class EmailControllerTest extends TestCase
{
    /**
     * @var string
     */
    public const NEW_CATEGORY_TITLE = 'New category';
    private MockObject $translatorMock;

    /**
     * @var MockObject|Session
     */
    private MockObject $sessionMock;

    /**
     * @var MockObject|ModelFactory<EmailModel>
     */
    private MockObject $modelFactoryMock;

    /**
     * @var MockObject|Container
     */
    private MockObject $containerMock;

    /**
     * @var MockObject|Router
     */
    private MockObject $routerMock;

    /**
     * @var MockObject|EmailModel
     */
    private MockObject $modelMock;

    /**
     * @var MockObject|Email
     */
    private MockObject $emailMock;

    /**
     * @var MockObject|FlashBag
     */
    private MockObject $flashBagMock;

    private EmailController $controller;

    /**
     * @var MockObject|CorePermissions
     */
    private MockObject $corePermissionsMock;

    /**
     * @var MockObject|FormFactory
     */
    private MockObject $formFactoryMock;

    /**
     * @var MockObject|Form
     */
    private MockObject $formMock;

    /**
     * @var MockObject|Environment
     */
    private MockObject $twigMock;

    private RequestStack $requestStack;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sessionMock   = $this->createMock(Session::class);
        $this->containerMock = $this->createMock(Container::class);
        $this->routerMock    = $this->createMock(Router::class);
        $this->modelMock     = $this->createMock(EmailModel::class);
        $this->emailMock     = $this->createMock(Email::class);
        $this->formMock      = $this->createMock(Form::class);
        $this->twigMock      = $this->createMock(Environment::class);

        $this->formFactoryMock      = $this->createMock(FormFactory::class);
        $formFieldHelper            = $this->createMock(FormFieldHelper::class);
        $doctrine                   = $this->createMock(ManagerRegistry::class);
        $this->modelFactoryMock     = $this->createMock(ModelFactory::class);
        $helperUserMock             = $this->createMock(UserHelper::class);
        $coreParametersHelper       = $this->createMock(CoreParametersHelper::class);
        $dispatcher                 = $this->createMock(EventDispatcherInterface::class);
        $this->translatorMock       = $this->createMock(Translator::class);
        $this->flashBagMock         = $this->createMock(FlashBag::class);
        $this->requestStack         = new RequestStack();
        $this->corePermissionsMock  = $this->createMock(CorePermissions::class);

        $helperUserMock->method('getUser')
            ->willReturn(new User(false));

        $this->controller = new EmailController(
            $this->formFactoryMock,
            $formFieldHelper,
            $doctrine,
            $this->modelFactoryMock,
            $helperUserMock,
            $coreParametersHelper,
            $dispatcher,
            $this->translatorMock,
            $this->flashBagMock,
            $this->requestStack,
            $this->corePermissionsMock
        );
        $this->controller->setContainer($this->containerMock);
        $this->sessionMock->method('getFlashBag')->willReturn($this->createMock(FlashBagInterface::class));
    }

    public function testSendActionWhenNoEntityFound(): void
    {
        $this->containerMock->expects($this->once())
            ->method('get')
            ->withConsecutive(['router'])
            ->willReturnOnConsecutiveCalls($this->routerMock);

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

        $request = $this->createMock(Request::class);
        $request->expects(self::once())
            ->method('getSession')
            ->willReturn($this->sessionMock);
        $this->requestStack->push($request);
        $response = $this->controller->sendAction($request, 5);
        $this->assertEquals(302, $response->getStatusCode());
    }

    public function testSendActionWhenEntityFoundButNotPublished(): void
    {
        $this->containerMock->expects($this->once())
            ->method('get')
            ->withConsecutive(['router'])
            ->willReturnOnConsecutiveCalls($this->routerMock);

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

        $request = $this->createMock(Request::class);
        $request->expects(self::once())
            ->method('getSession')
            ->willReturn($this->sessionMock);
        $this->requestStack->push($request);
        $response = $this->controller->sendAction($request, 5);
        $this->assertEquals(302, $response->getStatusCode());
    }

    public function testThatExampleEmailsHaveTestStringInTheirSubject(): void
    {
        $this->emailMock->expects($this->once())
            ->method('setSubject')
            ->with($this->stringStartsWith(EmailController::EXAMPLE_EMAIL_SUBJECT_PREFIX));

        $services = [
            ['router', Container::EXCEPTION_ON_INVALID_REFERENCE, $this->routerMock],
            ['form.factory', Container::EXCEPTION_ON_INVALID_REFERENCE, $this->formFactoryMock],
            ['twig', Container::EXCEPTION_ON_INVALID_REFERENCE, $this->twigMock],
        ];

        $serviceExists = fn ($key) => count(array_filter($services, fn ($service) => $service[0] === $key)) > 0;

        $this->containerMock->method('has')->willReturnCallback($serviceExists);
        $this->containerMock->method('get')->willReturnMap($services);

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

        $this->formFactoryMock->expects($this->once())
            ->method('create')
            ->with(\Mautic\EmailBundle\Form\Type\ExampleSendType::class,
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

        $this->twigMock->expects($this->once())
            ->method('render')
            ->willReturn('');

        $request = new Request();
        $this->requestStack->push($request);
        $this->controller->sendExampleAction($request, 1, $this->corePermissionsMock, $this->modelMock, $this->createMock(LeadModel::class), $this->createMock(FakeContactHelper::class));
    }
}
