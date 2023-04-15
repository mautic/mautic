<?php

namespace Mautic\PageBundle\Tests\Controller;

use Exception;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Exception\InvalidDecodedStringException;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\CookieHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Twig\Helper\AnalyticsHelper;
use Mautic\CoreBundle\Twig\Helper\AssetsHelper;
use Mautic\FormBundle\Helper\FormFieldHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Helper\ContactRequestHelper;
use Mautic\LeadBundle\Helper\PrimaryCompanyHelper;
use Mautic\LeadBundle\Model\LeadModel;
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
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Asset\Packages;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Router;

class PublicControllerTest extends MauticMysqlTestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|Container
     */
    private $internalContainer;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /** @var ModelFactory<object>&MockObject */
    private $modelFactory;

    /** @var RedirectModel */
    private $redirectModel;

    /** @var Redirect */
    private $redirect;

    /** @var Request */
    private $request;

    /** @var IpLookupHelper */
    private $ipLookupHelper;

    /** @var IpAddress */
    private $ipAddress;

    /** @var LeadModel */
    private $leadModel;

    /** @var PageModel */
    private $pageModel;

    /** @var PrimaryCompanyHelper */
    private $primaryCompanyHelper;

    /** @var ContactRequestHelper&MockObject */
    private $contactRequestHelper;

    protected function setUp(): void
    {
        $this->request              = new Request();
        $this->internalContainer    = $this->createMock(Container::class);
        $this->logger               = $this->createMock(\Psr\Log\LoggerInterface::class);
        $this->modelFactory         = $this->createMock(ModelFactory::class);
        $this->redirectModel        = $this->createMock(RedirectModel::class);
        $this->redirect             = $this->createMock(Redirect::class);
        $this->ipLookupHelper       = $this->createMock(IpLookupHelper::class);
        $this->ipAddress            = $this->createMock(IpAddress::class);
        $this->leadModel            = $this->createMock(LeadModel::class);
        $this->pageModel            = $this->createMock(PageModel::class);
        $this->primaryCompanyHelper = $this->createMock(PrimaryCompanyHelper::class);
        $this->contactRequestHelper = $this->createMock(ContactRequestHelper::class);

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
     * @param $aCount
     * @param $bCount
     * @param $cCount
     *
     * @return string
     */
    private function getVariantContent($aCount, $bCount, $cCount)
    {
        $pageEntityB = $this->getMockBuilder(Page::class)
            ->disableOriginalConstructor()
            ->getMock();
        $pageEntityB->method('getId')
            ->will($this->returnValue(2));
        $pageEntityB->method('isPublished')
            ->will($this->returnValue(true));
        $pageEntityB->method('getVariantHits')
            ->will($this->returnValue($bCount));
        $pageEntityB->method('getTranslations')
            ->will($this->returnValue([]));
        $pageEntityB->method('isTranslation')
            ->will($this->returnValue(false));
        $pageEntityB->method('getContent')
            ->will($this->returnValue(null));
        $pageEntityB->method('getCustomHtml')
            ->will($this->returnValue('pageB'));
        $pageEntityB->method('getVariantSettings')
            ->will($this->returnValue(['weight' => '25']));

        $pageEntityC = $this->getMockBuilder(Page::class)
            ->disableOriginalConstructor()
            ->getMock();
        $pageEntityC->method('getId')
            ->will($this->returnValue(3));
        $pageEntityC->method('isPublished')
            ->will($this->returnValue(true));
        $pageEntityC->method('getVariantHits')
            ->will($this->returnValue($cCount));
        $pageEntityC->method('getTranslations')
            ->will($this->returnValue([]));
        $pageEntityC->method('isTranslation')
            ->will($this->returnValue(false));
        $pageEntityC->method('getContent')
            ->will($this->returnValue(null));
        $pageEntityC->method('getCustomHtml')
            ->will($this->returnValue('pageC'));
        $pageEntityC->method('getVariantSettings')
            ->will($this->returnValue(['weight' => '25']));

        $pageEntityA = $this->getMockBuilder(Page::class)
            ->disableOriginalConstructor()
            ->getMock();
        $pageEntityA->method('getId')
            ->will($this->returnValue(1));
        $pageEntityA->method('isPublished')
            ->will($this->returnValue(true));
        $pageEntityA->method('getVariants')
            ->will($this->returnValue([$pageEntityA, [2 => $pageEntityB, 3 => $pageEntityC]]));
        $pageEntityA->method('getVariantHits')
            ->will($this->returnValue($aCount));
        $pageEntityA->method('getTranslations')
            ->will($this->returnValue([]));
        $pageEntityA->method('isTranslation')
            ->will($this->returnValue(false));
        $pageEntityA->method('getContent')
            ->will($this->returnValue(null));
        $pageEntityA->method('getCustomHtml')
            ->will($this->returnValue('pageA'));
        $pageEntityA->method('getVariantSettings')
            ->will($this->returnValue(['weight' => '50']));

        $cookieHelper = $this->getMockBuilder(CookieHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $packagesMock = $this->getMockBuilder(Packages::class)
            ->disableOriginalConstructor()
            ->getMock();

        $assetHelper = new AssetsHelper($packagesMock);

        $mauticSecurity = $this->getMockBuilder(CorePermissions::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mauticSecurity->method('hasEntityAccess')
            ->will($this->returnValue(false));

        $analyticsHelper = new AnalyticsHelper($this->createMock(CoreParametersHelper::class));

        $pageModel = $this->getMockBuilder(PageModel::class)
            ->disableOriginalConstructor()
            ->getMock();
        $pageModel->method('getHitQuery')
            ->will($this->returnValue([]));
        $pageModel->method('getEntityBySlugs')
            ->will($this->returnValue($pageEntityA));
        $pageModel->method('hitPage')
            ->will($this->returnValue(true));

        $this->contactRequestHelper->method('getContactFromQuery')
            ->will($this->returnValue(new Lead()));

        $router = $this->getMockBuilder(Router::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dispatcher = new EventDispatcher();

        $modelFactory = $this->getMockBuilder(ModelFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $modelFactory->method('getModel')
            ->will(
                $this->returnValueMap(
                    [
                        ['page', $pageModel],
                        ['lead', $this->leadModel],
                    ]
                )
            );

        $container = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->getMock();
        $container->method('has')
            ->will($this->returnValue(true));
        $container->expects(self::once())
            ->method('get')
            ->willReturnMap(
                [
                    ['router', Container::EXCEPTION_ON_INVALID_REFERENCE, $router],
                ]
            );

        $this->request->attributes->set('ignore_mismatch', true);

        $controller = new PublicController(
            $mauticSecurity,
            $this->createMock(UserHelper::class),
            $this->createMock(FormFactoryInterface::class),
            $this->createMock(FormFieldHelper::class)
        );
        $controller->setContainer($container);
        $controller->setModelFactory($modelFactory);
        $controller->setDispatcher($dispatcher);

        $response = $controller->indexAction(
            $this->request,
            $this->contactRequestHelper,
            $cookieHelper,
            $analyticsHelper,
            $assetHelper,
            $this->createMock(Tracking404Model::class),
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

        $this->modelFactory->expects(self::exactly(2))
            ->method('getModel')
            ->withConsecutive(['page.redirect'], ['page'])
            ->willReturnOnConsecutiveCalls($this->redirectModel, $this->pageModel);

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

        $routerMock = $this->createMock(Router::class);
        $routerMock->expects(self::once())
            ->method('generate')
            ->willReturn('/asset/');

        $this->internalContainer
            ->expects(self::once())
            ->method('get')
            ->willReturnMap([
                ['router', Container::EXCEPTION_ON_INVALID_REFERENCE, $routerMock],
            ]);

        $this->request->query->set('ct', $clickTrough);

        $controller = new PublicController(
            $this->createMock(CorePermissions::class),
            $this->createMock(UserHelper::class),
            $this->createMock(FormFactoryInterface::class),
            $this->createMock(FormFieldHelper::class)
        );
        $controller->setContainer($this->internalContainer);
        $controller->setModelFactory($this->modelFactory);

        $response = $controller->redirectAction(
            $this->request,
            $this->contactRequestHelper,
            $this->primaryCompanyHelper,
            $this->ipLookupHelper,
            $this->logger,
            $redirectId
        );
        self::assertInstanceOf(RedirectResponse::class, $response);
    }

    /**
     * @throws Exception
     */
    public function testAssetRedirectUrlWithClickThrough(): void
    {
        $redirectId   = 'dummy_redirect_id';
        $clickThrough = 'dummy_click_through';
        $redirectUrl  = 'https://some.test.url/asset/1:examplefilejpg';
        $targetUrl    = $redirectUrl.'?ct='.$clickThrough;

        $this->redirectModel->expects(self::once())
            ->method('getRedirectById')
            ->with($redirectId)
            ->willReturn($this->redirect);

        $this->modelFactory->expects(self::exactly(2))
            ->method('getModel')
            ->withConsecutive(['page.redirect'], ['page'])
            ->willReturnOnConsecutiveCalls($this->redirectModel, $this->pageModel);

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

        $routerMock = $this->createMock(Router::class);
        $routerMock->expects(self::once())
            ->method('generate')
            ->with('mautic_asset_download')
            ->willReturn('/asset');

        $this->internalContainer
            ->expects(self::once())
            ->method('get')
            ->willReturnMap([
                ['router', Container::EXCEPTION_ON_INVALID_REFERENCE, $routerMock],
            ]);

        $this->request->query->set('ct', $clickThrough);

        $controller = new PublicController(
            $this->createMock(CorePermissions::class),
            $this->createMock(UserHelper::class),
            $this->createMock(FormFactoryInterface::class),
            $this->createMock(FormFieldHelper::class)
        );
        $controller->setContainer($this->internalContainer);
        $controller->setModelFactory($this->modelFactory);

        $response = $controller->redirectAction(
            $this->request,
            $this->contactRequestHelper,
            $this->primaryCompanyHelper,
            $this->ipLookupHelper,
            $this->logger,
            $redirectId
        );
        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame($targetUrl, $response->getTargetUrl());
        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \Mautic\PageBundle\Event\TrackingEvent::getContact
     * @covers \Mautic\PageBundle\Event\TrackingEvent::getResponse
     * @covers \Mautic\PageBundle\Event\TrackingEvent::getRequest
     *
     * @throws \Exception
     */
    public function testMtcTrackingEvent()
    {
        $request = new Request(
            [
                'foo' => 'bar',
            ]
        );

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

        $pageModel    = $this->createMock(PageModel::class);
        $modelFactory = $this->createMock(ModelFactory::class);
        $modelFactory->expects($this->once())
            ->method('getModel')
            ->with('page')
            ->willReturn($pageModel);

        $deviceTrackingService = $this->createMock(DeviceTrackingServiceInterface::class);

        $trackingHelper = $this->createMock(TrackingHelper::class);
        $trackingHelper->expects($this->once())
            ->method('getSession')
            ->willReturn($mtcSessionEventArray);

        $contactTracker = $this->createMock(ContactTracker::class);
        $contactTracker->method('getContact')
            ->willReturn($contact);

        $publicController = new PublicController(
            $security,
            $this->createMock(UserHelper::class),
            $this->createMock(FormFactoryInterface::class),
            $this->createMock(FormFieldHelper::class)
        );
        $publicController->setModelFactory($modelFactory);
        $publicController->setDispatcher($eventDispatcher);

        $response = $publicController->trackingAction(
            $request,
            $deviceTrackingService,
            $trackingHelper,
            $contactTracker
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

    public function testTrackingActionWithInvalidCt()
    {
        $request = new Request();

        $pageModel    = $this->createMock(PageModel::class);
        $pageModel->expects($this->once())->method('hitPage')->willReturnCallback(
            function () {
                throw new InvalidDecodedStringException();
            }
        );

        $modelFactory = $this->createMock(ModelFactory::class);
        $modelFactory->expects($this->once())
            ->method('getModel')
            ->with('page')
            ->willReturn($pageModel);

        $security = $this->createMock(CorePermissions::class);
        $security->expects($this->once())
            ->method('isAnonymous')
            ->willReturn(true);

        $publicController = new PublicController(
            $security,
            $this->createMock(UserHelper::class),
            $this->createMock(FormFactoryInterface::class),
            $this->createMock(FormFieldHelper::class)
        );
        $publicController->setModelFactory($modelFactory);

        $response = $publicController->trackingAction(
            $request,
            $this->createMock(DeviceTrackingServiceInterface::class),
            $this->createMock(TrackingHelper::class),
            $this->createMock(ContactTracker::class)
        );
        $this->assertEquals(
            ['success' => 0],
            json_decode($response->getContent(), true)
        );
    }

    public function testTrackingImageAction()
    {
        $this->client->request('GET', '/mtracking.gif?url=http%3A%2F%2Fmautic.org');

        $this->assertResponseStatusCodeSame(200);
    }
}
