<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\EventListener;

use AppKernel;
use Mautic\CoreBundle\Controller\CommonController;
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\EventListener\CoreSubscriber;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\BundleHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Menu\MenuHelper;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\CoreBundle\Twig\Helper\AssetsHelper;
use Mautic\FormBundle\Entity\FormRepository;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Model\UserModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Asset\Packages;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Http\SecurityEvents;

class CoreSubscriberTest extends TestCase
{
    /**
     * @var BundleHelper|MockObject
     */
    private $bundleHelper;

    /**
     * @var MenuHelper|MockObject
     */
    private $menuHelper;

    /**
     * @var UserHelper|MockObject
     */
    private $userHelper;

    /**
     * @var AssetsHelper|MockObject
     */
    private $assetsHelper;

    /**
     * @var CoreParametersHelper|MockObject
     */
    private $coreParametersHelper;

    /**
     * @var MockObject|AuthorizationChecker
     */
    private $securityContext;

    /**
     * @var UserModel|MockObject
     */
    private $userModel;

    /**
     * @var MockObject|EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var MockObject|Translator
     */
    private $translator;

    /**
     * @var MockObject|RequestStack
     */
    private $requestStack;

    /**
     * @var FormRepository|MockObject
     */
    private $formRepository;

    /**
     * @var MauticFactory|MockObject
     */
    private $factory;

    /**
     * @var ModelFactory<object>&MockObject
     */
    private $modelFactory;

    /**
     * @var FlashBag|MockObject
     */
    private $flashBag;

    /**
     * @var CoreSubscriber
     */
    private $subscriber;

    protected function setUp(): void
    {
        $this->bundleHelper         = $this->createMock(BundleHelper::class);
        $this->menuHelper           = $this->createMock(MenuHelper::class);
        $this->userHelper           = $this->createMock(UserHelper::class);
        $packagesMock               = $this->getMockBuilder(Packages::class)
        ->disableOriginalConstructor()
        ->getMock();
        $this->assetsHelper         = new AssetsHelper($packagesMock);
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $this->securityContext      = $this->createMock(AuthorizationChecker::class);
        $this->userModel            = $this->createMock(UserModel::class);
        $this->dispatcher           = $this->createMock(EventDispatcherInterface::class);
        $this->translator           = $this->createMock(Translator::class);
        $this->requestStack         = $this->createMock(RequestStack::class);

        $this->formRepository = $this->createMock(FormRepository::class);
        $this->factory        = $this->createMock(MauticFactory::class);
        $this->modelFactory   = $this->createMock(ModelFactory::class);
        $this->flashBag       = $this->createMock(FlashBag::class);

        $this->subscriber = new CoreSubscriber(
            $this->bundleHelper,
            $this->menuHelper,
            $this->userHelper,
            $this->assetsHelper,
            $this->coreParametersHelper,
            $this->securityContext,
            $this->userModel,
            $this->dispatcher,
            $this->translator,
            $this->requestStack,
            $this->formRepository,
            $this->factory,
            $this->modelFactory,
            $this->flashBag
        );

        parent::setUp();
    }

    public function testGetSubscribedEvents(): void
    {
        self::assertSame(
            [
                KernelEvents::CONTROLLER => [
                    ['onKernelController', 0],
                    ['onKernelRequestAddGlobalJS', 0],
                ],
                CoreEvents::BUILD_MENU            => ['onBuildMenu', 9999],
                CoreEvents::BUILD_ROUTE           => ['onBuildRoute', 0],
                CoreEvents::FETCH_ICONS           => ['onFetchIcons', 9999],
                SecurityEvents::INTERACTIVE_LOGIN => ['onSecurityInteractiveLogin', 0],
            ],
            CoreSubscriber::getSubscribedEvents()
        );
    }

    public function testOnKernelController(): void
    {
        $user = $this->createMock(User::class);

        $this->userHelper->expects(self::exactly(2))
            ->method('getUser')
            ->willReturn($user);

        $controller = $this->getMockBuilder(CommonController::class)
            ->onlyMethods(['initialize', 'setFactory', 'setUser', 'setCoreParametersHelper', 'setDispatcher', 'setTranslator', 'setFlashBag', 'setModelFactory'])
            ->getMock();

        $kernel  = new AppKernel(MAUTIC_ENV, false);
        $request = $this->createMock(Request::class);

        $event = new ControllerEvent($kernel, fn () => $controller, $request, HttpKernelInterface::MAIN_REQUEST);

        $controller->expects(self::once())
            ->method('setFactory')
            ->with($this->factory);
        $controller->expects(self::once())
            ->method('setModelFactory')
            ->with($this->modelFactory);
        $controller->expects(self::once())
            ->method('setUser')
            ->with($user);
        $controller->expects(self::once())
            ->method('setCoreParametersHelper')
            ->with($this->coreParametersHelper);
        $controller->expects(self::once())
            ->method('setCoreParametersHelper')
            ->with($this->coreParametersHelper);
        $controller->expects(self::once())
            ->method('setDispatcher')
            ->with($this->dispatcher);
        $controller->expects(self::once())
            ->method('setTranslator')
            ->with($this->translator);
        $controller->expects(self::once())
            ->method('setFlashBag')
            ->with($this->flashBag);
        $controller->expects(self::once())
            ->method('initialize')
            ->with($event);

        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addSubscriber($this->subscriber);
        $eventDispatcher->dispatch($event, KernelEvents::CONTROLLER);
    }
}
