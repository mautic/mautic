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
use Mautic\EmailBundle\Controller\BatchEmailController;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Model\EmailActionModel;
use Mautic\EmailBundle\Model\EmailModel;
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
     * @var MockObject|EmailModel
     */
    private MockObject $modelMock;

    /**
     * @var MockObject|FlashBag
     */
    private MockObject $flashBagMock;

    private BatchEmailController $controller;

    /**
     * @var MockObject|CorePermissions
     */
    private MockObject $corePermissionsMock;

    /**
     * @var MockObject|FormFactory
     */
    private MockObject $formFactoryMock;

    private RequestStack $requestStack;
    private EmailActionModel $actionModel;
    private CategoryModel $categoryModel;

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

        $this->actionModel            = $this->createMock(EmailActionModel::class);
        $this->categoryModel          = $this->createMock(CategoryModel::class);

        $helperUserMock->method('getUser')
            ->willReturn(new User(false));

        $this->controller = new BatchEmailController(
            $this->actionModel,
            $this->categoryModel,
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
    public function testExecBatchChangeCategory(): void
    {
        $this->markTestSkipped('Not implemented yet.');
    }

    protected function configureCategoryMock(Category $newCategory): void
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

        $categoryModelMock
            ->expects($this->once())
            ->method('getEntity')
            ->with($newCategory->getId())
            ->willReturn($newCategory);
    }

    protected function configureEmailMock(array $emails): void
    {
        $this
            ->modelMock
            ->expects($this->exactly(count($emails)))
            ->method('getEntity')
            ->withConsecutive(...array_chunk(array_keys($emails), 1))
            ->willReturnOnConsecutiveCalls(...$emails);

        $this
            ->modelMock
            ->expects($this->exactly(count($emails)))
            ->method('saveEntity')
            ->withConsecutive(...array_chunk($emails, 1));
    }

    protected function buildRequest(array $emails, Category $newCategory): Request
    {
        $request = new Request();
        $request->setMethod(Request::METHOD_POST);
        $request->query->set('emailIds', array_keys($emails));
        $request->query->set('newCategoryId', $newCategory->getId());

        return $request;
    }
}
