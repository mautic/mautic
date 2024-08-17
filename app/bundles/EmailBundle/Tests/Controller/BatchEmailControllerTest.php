<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\CategoryBundle\Entity\Category;
use Mautic\CategoryBundle\Model\CategoryModel;
use Mautic\CoreBundle\Factory\MauticFactory;
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
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Router;
use Twig\Environment;

class BatchEmailControllerTest extends TestCase
{
    public const NEW_CATEGORY_TITLE = 'New category';
    private MockObject|Translator $translatorMock;

    /**
     * @var MockObject|Session
     */
    private MockObject $sessionMock;

    /**
     * @var MockObject|ModelFactory<EmailModel>
     */
    private MockObject|ModelFactory $modelFactoryMock;

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
        $factory                    = $this->createMock(MauticFactory::class);
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
            $factory,
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
        $this->sessionMock->method('getFlashBag')->willReturn($this->flashBagMock);
    }

    // @todo Change this to use the action
    public function testBatchRecategorizePost(): void
    {
        $categoryModelMock = $this->createMock(CategoryModel::class);

        $this
            ->modelFactoryMock
            ->expects($this->exactly(2))
            ->method('getModel')
            ->withConsecutive(
                ['email'],
                ['category']
            )
            ->willReturnOnConsecutiveCalls(
                $this->modelMock,
                $categoryModelMock
            );

        $newCategory = new Category();
        $newCategory->setTitle(self::NEW_CATEGORY_TITLE);

        $categoryModelMock
            ->expects($this->once())
            ->method('getEntity')
            ->with($newCategory->getId())
            ->willReturn($newCategory);
        $emails = [
            1 => new Email(),
            2 => new Email(),
            3 => new Email(),
        ];

        $oldCategory = new Category();
        $oldCategory->setTitle('Old category');

        foreach ($emails as $email) {
            $email->setCategory($oldCategory);
        }

        $emailIds = array_keys($emails);
        $this
            ->modelMock
            ->expects($this->exactly(count($emails)))
            ->method('getEntity')
            ->withConsecutive(...array_chunk($emailIds, 1))
            ->willReturnOnConsecutiveCalls(...$emails);

        $this
            ->modelMock
            ->expects($this->exactly(count($emails)))
            ->method('saveEntity')
            ->withConsecutive(...array_chunk($emails, 1));

        $request = new Request();
        $request->setMethod(Request::METHOD_POST);
        $request->query->set('emailIds', $emailIds);
        $request->query->set('newCategoryId', $newCategory->getId());
        $this->requestStack->push($request);
        $this->controller->batchRecategorizeAction($request);

        foreach ($emails as $email) {
            $this->assertEquals(self::NEW_CATEGORY_TITLE, $email->getCategory()->getTitle());
        }
    }
}
