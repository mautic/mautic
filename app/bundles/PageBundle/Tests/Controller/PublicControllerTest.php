<?php

namespace Mautic\PageBundle\Tests\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Exception\InvalidDecodedStringException;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\CookieHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Helper\ThemeHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\CoreBundle\Twig\Helper\AnalyticsHelper;
use Mautic\CoreBundle\Twig\Helper\AssetsHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Helper\ContactRequestHelper;
use Mautic\LeadBundle\Helper\PrimaryCompanyHelper;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Mautic\LeadBundle\Tracker\Service\DeviceTrackingService\DeviceTrackingServiceInterface;
use Mautic\PageBundle\Controller\PublicController;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Entity\Redirect;
use Mautic\PageBundle\Event\TrackingEvent;
use Mautic\PageBundle\Helper\TrackingHelper;
use Mautic\PageBundle\Model\PageModel;
use Mautic\PageBundle\Model\RedirectModel;
use Mautic\PageBundle\Model\Tracking404Model;
use Mautic\PageBundle\PageEvents;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class PublicControllerTest extends TestCase
{
    private MockObject&Container $internalContainer;

    private MockObject&LoggerInterface $logger;

    /**
     * @var ModelFactory<object>&MockObject
     */
    private MockObject&ModelFactory $modelFactory;

    private MockObject&RedirectModel $redirectModel;

    private MockObject&Redirect $redirect;

    private Request $request;

    private MockObject&IpLookupHelper $ipLookupHelper;

    private MockObject&IpAddress $ipAddress;

    private MockObject&PageModel $pageModel;

    private MockObject&PrimaryCompanyHelper $primaryCompanyHelper;

    private MockObject&ContactRequestHelper $contactRequestHelper;

    private MockObject&RouterInterface $router;

    protected function setUp(): void
    {
        $this->request              = new Request();
        $this->internalContainer    = $this->createMock(Container::class);
        $this->logger               = $this->createMock(LoggerInterface::class);
        $this->modelFactory         = $this->createMock(ModelFactory::class);
        $this->redirectModel        = $this->createMock(RedirectModel::class);
        $this->redirect             = $this->createMock(Redirect::class);
        $this->ipLookupHelper       = $this->createMock(IpLookupHelper::class);
        $this->ipAddress            = $this->createMock(IpAddress::class);
        $this->pageModel            = $this->createMock(PageModel::class);
        $this->primaryCompanyHelper = $this->createMock(PrimaryCompanyHelper::class);
        $this->contactRequestHelper = $this->createMock(ContactRequestHelper::class);
        $this->router               = $this->createMock(RouterInterface::class);

        parent::setUp();
    }

    /**
     * Test that the appropriate variant is displayed based on hit counts and variant weights.
     */
    public function testVariantPageWeightsAreAppropriate(): void
    {
        // Each of these should return the one with the greatest weight deficit based on
        // A = 50%
        // B = 25%
        // C = 25%

        // A = 0/50; B = 0/25; C = 0/25
        $this->assertEquals('pageA', $this->getVariantContent(0, 0, 0));

        // A = 100/50; B = 0/25; C = 0/25
        $this->assertEquals('pageB', $this->getVariantContent(1, 0, 0));

        // A = 50/50; B = 50/25; C = 0/25;
        $this->assertEquals('pageC', $this->getVariantContent(1, 1, 0));

        // A = 33/50; B = 33/25; C = 33/25;
        $this->assertEquals('pageA', $this->getVariantContent(1, 1, 1));

        // A = 66/50; B = 33/25; C = 0/25
        $this->assertEquals('pageC', $this->getVariantContent(2, 1, 0));

        // A = 50/50; B = 25/25; C = 25/25
        $this->assertEquals('pageA', $this->getVariantContent(2, 1, 1));

        // A = 33/50; B = 66/50; C = 0/25
        $this->assertEquals('pageC', $this->getVariantContent(1, 2, 0));

        // A = 25/50; B = 50/50; C = 25/25
        $this->assertEquals('pageA', $this->getVariantContent(1, 2, 1));

        // A = 55/50; B = 18/25; C = 27/25
        $this->assertEquals('pageB', $this->getVariantContent(6, 2, 3));

        // A = 50/50; B = 25/25; C = 25/25
        $this->assertEquals('pageA', $this->getVariantContent(6, 3, 3));
    }

    /**
     * @return string
     */
    private function getVariantContent($aCount, $bCount, $cCount)
    {
        $pageEntityB = $this->createMock(Page::class);
        $pageEntityB->method('getId')
            ->willReturn(2);
        $pageEntityB->method('isPublished')
            ->willReturn(true);
        $pageEntityB->method('getVariantHits')
            ->willReturn($bCount);
        $pageEntityB->method('getTranslations')
            ->willReturn([]);
        $pageEntityB->method('isTranslation')
            ->willReturn(false);
        $pageEntityB->method('getContent')
            ->willReturn(null);
        $pageEntityB->method('getCustomHtml')
            ->willReturn('pageB');
        $pageEntityB->method('getVariantSettings')
            ->willReturn(['weight' => '25']);

        $pageEntityC = $this->createMock(Page::class);
        $pageEntityC->method('getId')
            ->willReturn(3);
        $pageEntityC->method('isPublished')
            ->willReturn(true);
        $pageEntityC->method('getVariantHits')
            ->willReturn($cCount);
        $pageEntityC->method('getTranslations')
            ->willReturn([]);
        $pageEntityC->method('isTranslation')
            ->willReturn(false);
        $pageEntityC->method('getContent')
            ->willReturn(null);
        $pageEntityC->method('getCustomHtml')
            ->willReturn('pageC');
        $pageEntityC->method('getVariantSettings')
            ->willReturn(['weight' => '25']);

        $pageEntityA = $this->createMock(Page::class);
        $pageEntityA->method('getId')
            ->willReturn(1);
        $pageEntityA->method('isPublished')
            ->willReturn(true);
        $pageEntityA->method('getVariants')
            ->willReturn([$pageEntityA, [2 => $pageEntityB, 3 => $pageEntityC]]);
        $pageEntityA->method('getVariantHits')
            ->willReturn($aCount);
        $pageEntityA->method('getTranslations')
            ->willReturn([]);
        $pageEntityA->method('isTranslation')
            ->willReturn(false);
        $pageEntityA->method('getContent')
            ->willReturn(null);
        $pageEntityA->method('getCustomHtml')
            ->willReturn('pageA');
        $pageEntityA->method('getVariantSettings')
            ->willReturn(['weight' => '50']);

        $cookieHelper = $this->createMock(CookieHelper::class);

        /** @var Packages&MockObject $packagesMock */
        $packagesMock = $this->createMock(Packages::class);

        /** @var CoreParametersHelper&MockObject $coreParametersHelper */
        $coreParametersHelper = $this->createMock(CoreParametersHelper::class);

        $assetHelper = new AssetsHelper($packagesMock);

        $mauticSecurity = $this->createMock(CorePermissions::class);
        $mauticSecurity->method('hasEntityAccess')
            ->willReturn(false);

        $analyticsHelper = new AnalyticsHelper($coreParametersHelper);

        $pageModel = $this->createMock(PageModel::class);
        $pageModel->method('getHitQuery')
            ->willReturn([]);
        $pageModel->method('getEntityBySlugs')
            ->willReturn($pageEntityA);
        $pageModel->method('hitPage')
            ->willReturn(true);

        $this->contactRequestHelper->method('getContactFromQuery')
            ->willReturn(new Lead());

        $this->request->attributes->set('ignore_mismatch', true);
        $themeHelper = $this->createMock(ThemeHelper::class);
        $themeHelper->expects(self::never())
            ->method('checkForTwigTemplate');

        $controller = new PublicController(
            $this->createMock(ManagerRegistry::class),
            $this->modelFactory,
            $this->createMock(UserHelper::class),
            $this->createMock(CoreParametersHelper::class),
            new EventDispatcher(),
            $this->createMock(Translator::class),
            $this->createMock(FlashBag::class),
            new RequestStack([$this->request]),
            $mauticSecurity
        );
        $controller->setContainer($this->internalContainer);

        $response = $controller->indexAction(
            $this->request,
            $this->contactRequestHelper,
            $cookieHelper,
            $analyticsHelper,
            $assetHelper,
            $themeHelper,
            $this->createMock(Tracking404Model::class),
            $this->router,
            $this->createMock(DeviceTrackingServiceInterface::class),
            $pageModel,
            '/page/a',
        );

        return $response->getContent();
    }

    public function testThatInvalidClickTroughGetsProcessed(): void
    {
        $redirectId  = 'someRedirectId';
        $clickTrough = 'someClickTroughValue';
        $redirectUrl = 'https://someurl.test/';

        $this->redirectModel->expects(self::once())
            ->method('getRedirectById')
            ->with($redirectId)
            ->willReturn($this->redirect);

        $this->redirect->expects(self::once())
            ->method('isPublished')
            ->with(false)
            ->willReturn(true);

        $this->redirect->expects(self::once())
            ->method('getUrl')
            ->willReturn($redirectUrl);

        $this->ipLookupHelper->expects(self::once())
            ->method('getIpAddress')
            ->willReturn($this->ipAddress);

        $this->ipAddress->expects(self::once())
            ->method('isTrackable')
            ->willReturn(true);

        $getContactFromRequestCallback = function ($queryFields) use ($clickTrough) {
            if (empty($queryFields)) {
                return null;
            }

            throw new InvalidDecodedStringException($clickTrough);
        };

        $this->contactRequestHelper->expects(self::exactly(2))
            ->method('getContactFromQuery')
            ->willReturnCallback($getContactFromRequestCallback);

        $this->router->expects(self::once())
            ->method('generate')
            ->willReturn('/asset/');

        $this->internalContainer
            ->expects(self::once())
            ->method('get')
            ->willReturnMap([
                ['router', Container::EXCEPTION_ON_INVALID_REFERENCE, $this->router],
            ]);

        $this->request->query->set('ct', $clickTrough);

        $controller = new PublicController(
            $this->createMock(ManagerRegistry::class),
            $this->modelFactory,
            $this->createMock(UserHelper::class),
            $this->createMock(CoreParametersHelper::class),
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(Translator::class),
            $this->createMock(FlashBag::class),
            new RequestStack(),
            $this->createMock(CorePermissions::class)
        );
        $controller->setContainer($this->internalContainer);

        $response = $controller->redirectAction(
            $this->request,
            $this->contactRequestHelper,
            $this->primaryCompanyHelper,
            $this->ipLookupHelper,
            $this->logger,
            $this->redirectModel,
            $this->pageModel,
            $redirectId
        );

        self::assertSame('https://someurl.test/', $response->getTargetUrl());
    }

    #[DataProvider('provideRedirectUrls')]
    public function testAssetRedirectUrlWithClickThrough(string $redirectUrl, string $targetUrl): void
    {
        $redirectId   = 'dummy_redirect_id';
        $clickThrough = 'dummy_click_through';

        $this->redirectModel->expects(self::once())
            ->method('getRedirectById')
            ->with($redirectId)
            ->willReturn($this->redirect);

        $this->redirect->expects(self::once())
            ->method('isPublished')
            ->with(false)
            ->willReturn(true);

        $this->redirect->expects(self::once())
            ->method('getUrl')
            ->willReturn($redirectUrl);

        $this->ipLookupHelper->expects(self::once())
            ->method('getIpAddress')
            ->willReturn($this->ipAddress);

        $this->ipAddress->expects(self::once())
            ->method('isTrackable')
            ->willReturn(true);

        $getContactFromRequestCallback = function ($queryFields) use ($clickThrough) {
            if (empty($queryFields)) {
                return null;
            }

            throw new InvalidDecodedStringException($clickThrough);
        };

        $this->contactRequestHelper->expects(self::exactly(2))
            ->method('getContactFromQuery')
            ->willReturnCallback($getContactFromRequestCallback);

        $this->router->expects(self::once())
            ->method('generate')
            ->with('mautic_asset_download')
            ->willReturn('/asset');

        $this->internalContainer
            ->expects(self::once())
            ->method('get')
            ->willReturnMap([
                ['router', Container::EXCEPTION_ON_INVALID_REFERENCE, $this->router],
            ]);

        $this->request->query->set('ct', $clickThrough);

        $controller = new PublicController(
            $this->createMock(ManagerRegistry::class),
            $this->modelFactory,
            $this->createMock(UserHelper::class),
            $this->createMock(CoreParametersHelper::class),
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(Translator::class),
            $this->createMock(FlashBag::class),
            new RequestStack(),
            $this->createMock(CorePermissions::class)
        );
        $controller->setContainer($this->internalContainer);

        $response = $controller->redirectAction(
            $this->request,
            $this->contactRequestHelper,
            $this->primaryCompanyHelper,
            $this->ipLookupHelper,
            $this->logger,
            $this->redirectModel,
            $this->pageModel,
            $redirectId
        );
        self::assertSame($targetUrl, $response->getTargetUrl());
        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
    }

    public static function provideRedirectUrls(): \Generator
    {
        yield 'No query parameters' => [
            'redirectUrl' => 'https://some.test.url/asset/1:examplefilejpg',
            'targetUrl'   => 'https://some.test.url/asset/1:examplefilejpg?ct=dummy_click_through',
        ];

        yield 'With query parameter' => [
            'redirectUrl' => 'https://some.test.url/asset/1:examplefilejpg?param=value',
            'targetUrl'   => 'https://some.test.url/asset/1:examplefilejpg?param=value&ct=dummy_click_through',
        ];

        yield 'With click-through parameter' => [
            'redirectUrl' => 'https://some.test.url/asset/1:examplefilejpg?ct=parameter',
            'targetUrl'   => 'https://some.test.url/asset/1:examplefilejpg?ct=dummy_click_through',
        ];
    }

    public function testMtcTrackingEvent(): void
    {
        $request = new Request(['foo' => 'bar']);
        $contact = new Lead();
        $contact->setEmail('foo@bar.com');

        $mtcSessionEventArray = ['mtc' => 'foobar'];

        $event           = new TrackingEvent($contact, $request, $mtcSessionEventArray);
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($event, PageEvents::ON_CONTACT_TRACKED)
            ->willReturnCallback(
                function (TrackingEvent $event) {
                    $contact  = $event->getContact()->getEmail();
                    $request  = $event->getRequest();
                    $response = $event->getResponse();

                    $response->set('tracking', $contact);
                    $response->set('foo', $request->get('foo'));

                    return $event;
                }
            );

        $security = $this->createMock(CorePermissions::class);
        $security->expects($this->once())
            ->method('isAnonymous')
            ->willReturn(true);

        $deviceTrackingService = $this->createMock(DeviceTrackingServiceInterface::class);

        $trackingHelper = $this->createMock(TrackingHelper::class);
        $trackingHelper->expects($this->once())
            ->method('getCacheItem')
            ->willReturn($mtcSessionEventArray);

        $contactTracker = $this->createMock(ContactTracker::class);
        $contactTracker->method('getContact')
            ->willReturn($contact);

        $publicController = new PublicController(
            $this->createMock(ManagerRegistry::class),
            $this->modelFactory,
            $this->createMock(UserHelper::class),
            $this->createMock(CoreParametersHelper::class),
            $eventDispatcher,
            $this->createMock(Translator::class),
            $this->createMock(FlashBag::class),
            new RequestStack(),
            $security
        );

        $response = $publicController->trackingAction(
            $request,
            $deviceTrackingService,
            $trackingHelper,
            $contactTracker,
            $this->pageModel
        );

        $json = json_decode($response->getContent(), true);

        $this->assertEquals(
            [
                'mtc'      => 'foobar',
                'tracking' => 'foo@bar.com',
                'foo'      => 'bar',
            ],
            $json['events']
        );
    }

    public function testTrackingActionWithInvalidCt(): void
    {
        $this->pageModel->expects($this->once())->method('hitPage')->willReturnCallback(
            function (): void {
                throw new InvalidDecodedStringException();
            }
        );

        $security = $this->createMock(CorePermissions::class);
        $security->expects($this->once())
            ->method('isAnonymous')
            ->willReturn(true);

        $publicController = new PublicController(
            $this->createMock(ManagerRegistry::class),
            $this->modelFactory,
            $this->createMock(UserHelper::class),
            $this->createMock(CoreParametersHelper::class),
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(Translator::class),
            $this->createMock(FlashBag::class),
            new RequestStack(),
            $security
        );

        $response = $publicController->trackingAction(
            $this->request,
            $this->createMock(DeviceTrackingServiceInterface::class),
            $this->createMock(TrackingHelper::class),
            $this->createMock(ContactTracker::class),
            $this->pageModel
        );
        $this->assertEquals(
            ['success' => 0],
            json_decode($response->getContent(), true)
        );
    }
}
