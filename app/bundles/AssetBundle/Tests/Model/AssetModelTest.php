<?php

declare(strict_types=1);

namespace Mautic\AssetBundle\Tests\Model;

use Doctrine\ORM\EntityManager;
use Mautic\AssetBundle\AssetEvents;
use Mautic\AssetBundle\Entity\Asset;
use Mautic\AssetBundle\Entity\AssetRepository;
use Mautic\AssetBundle\Entity\Download;
use Mautic\AssetBundle\Model\AssetModel;
use Mautic\CacheBundle\Cache\CacheProvider;
use Mautic\CategoryBundle\Model\CategoryModel;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Mautic\LeadBundle\Tracker\Factory\DeviceDetectorFactory\DeviceDetectorFactory;
use Mautic\LeadBundle\Tracker\Service\DeviceCreatorService\DeviceCreatorService;
use Mautic\LeadBundle\Tracker\Service\DeviceTrackingService\DeviceTrackingServiceInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\ServerBag;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AssetModelTest extends \PHPUnit\Framework\TestCase
{
    private AssetModel $assetModel;

    private CoreParametersHelper&MockObject $coreParametersHelper;

    private ContainerInterface&MockObject $container;

    private CacheProvider $cacheProvider;

    private LeadModel&MockObject $leadModel;

    private CategoryModel&MockObject $categoryModel;

    private RequestStack&MockObject $requestStack;

    private IpLookupHelper&MockObject $ipLookupHelper;

    private DeviceDetectorFactory $deviceDetectorFactory;

    private DeviceCreatorService $deviceCreatorService;

    private DeviceTrackingServiceInterface&MockObject $deviceTrackingService;

    private ContactTracker&MockObject $contactTracker;

    private EntityManager&MockObject $entityManager;

    private CorePermissions&MockObject $corePermissions;

    private EventDispatcherInterface&MockObject $eventDispatcher;

    private MockObject&UrlGeneratorInterface $urlGenerator;

    private Translator&MockObject $translator;

    private UserHelper&MockObject $userHelper;

    private LoggerInterface&MockObject $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);

        $this->coreParametersHelper->expects($this->once())
            ->method('get')
            ->with($this->equalTo('max_size'))
            ->willReturn('2MB');

        $this->container             = $this->createMock(ContainerInterface::class);
        $this->cacheProvider         = new CacheProvider($this->coreParametersHelper, $this->container);
        $this->leadModel             = $this->createMock(LeadModel::class);
        $this->categoryModel         = $this->createMock(CategoryModel::class);
        $this->requestStack          = $this->createMock(RequestStack::class);
        $this->ipLookupHelper        = $this->createMock(IpLookupHelper::class);
        $this->deviceDetectorFactory = new DeviceDetectorFactory($this->cacheProvider);
        $this->deviceCreatorService  = new DeviceCreatorService();
        $this->deviceTrackingService = $this->getMockBuilder(DeviceTrackingServiceInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->contactTracker  = $this->createMock(ContactTracker::class);
        $this->entityManager   = $this->createMock(EntityManager::class);
        $this->corePermissions = $this->createMock(CorePermissions::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->urlGenerator    = $this->createMock(UrlGeneratorInterface::class);
        $this->translator      = $this->createMock(Translator::class);
        $this->userHelper      = $this->createMock(UserHelper::class);
        $this->logger          = $this->createMock(LoggerInterface::class);

        $this->assetModel = new AssetModel(
            $this->leadModel,
            $this->categoryModel,
            $this->requestStack,
            $this->ipLookupHelper,
            $this->deviceCreatorService,
            $this->deviceDetectorFactory,
            $this->deviceTrackingService,
            $this->contactTracker,
            $this->entityManager,
            $this->corePermissions,
            $this->eventDispatcher,
            $this->urlGenerator,
            $this->translator,
            $this->userHelper,
            $this->logger,
            $this->coreParametersHelper,
        );
    }

    /**
     * Test that TrackDownload works only with a request.
     */
    public function testTrackDownloadRequest(): void
    {
        $asset = new Asset();

        $this->corePermissions->expects($this->once())
            ->method('isAnonymous')
            ->willReturn(true);

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn(null);

        $this->entityManager->expects($this->never())
            ->method('persist');

        $this->entityManager->expects($this->never())
            ->method('flush');

        $this->entityManager->expects($this->never())
            ->method('detach');

        $this->assetModel->trackDownload($asset);
    }

    /**
     * Test that TrackDownload works successfully.
     */
    public function testTrackDownload(): void
    {
        $asset = new Asset();
        $lead  = new Lead();

        $this->corePermissions->expects($this->once())
            ->method('isAnonymous')
            ->willReturn(true);

        $request = $this->createMock(Request::class);

        $serverBag = $this->createMock(ServerBag::class);

        $serverBag->expects($this->once())
            ->method('get')
            ->with($this->equalTo('HTTP_REFERER'))
            ->willReturn('http://localhost');

        $request->server = $serverBag;

        $request->expects($this->exactly(6))
            ->method('get')
            ->withConsecutive(
                [$this->equalTo('utm_campaign')],
                [$this->equalTo('utm_content')],
                [$this->equalTo('utm_medium')],
                [$this->equalTo('utm_source')],
                [$this->equalTo('utm_term')],
                [$this->equalTo('ct')]
            )
            ->willReturnOnConsecutiveCalls(
                'test_utm_campaign',
                'test_utm_content',
                'test_utm_medium',
                'test_utm_source',
                'test_utm_term',
                false
            );

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->deviceTrackingService->expects($this->once())
            ->method('isTracked')
            ->willReturn(false);

        $this->contactTracker->expects($this->once())
            ->method('getContact')
            ->willReturn($lead);

        $this->deviceTrackingService->expects($this->once())
            ->method('getTrackedDevice')
            ->willReturn(null);

        $assetRepository = $this->createMock(AssetRepository::class);

        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->with($this->equalTo(Asset::class))
            ->willReturn($assetRepository);

        $assetRepository->expects($this->once())
            ->method('upDownloadCount')
            ->with(
                $this->equalTo($asset->getId()),
                $this->equalTo(1),
                $this->equalTo(true),
            );

        $ipAddress = new IpAddress('127.0.0.1');

        $this->ipLookupHelper->expects($this->once())
            ->method('getIpAddress')
            ->willReturn($ipAddress);

        $this->eventDispatcher->expects($this->once())
            ->method('hasListeners')
            ->with($this->equalTo(AssetEvents::ASSET_ON_LOAD))
            ->willReturn(false);

        /** @var ?Download $download */
        $download = null;

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function ($downloadPersist) use (&$download) {
                $download = $downloadPersist;

                return $download instanceof Download;
            }));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->entityManager->expects($this->once())
            ->method('detach')
            ->with($this->callback(function ($downloadDetach) use (&$download) {
                $this->assertSame($downloadDetach, $download);

                return true;
            }));

        $this->assetModel->trackDownload($asset);

        $this->assertEquals('test_utm_campaign', $download->getUtmCampaign());
        $this->assertEquals('test_utm_content', $download->getUtmContent());
        $this->assertEquals('test_utm_medium', $download->getUtmMedium());
        $this->assertEquals('test_utm_source', $download->getUtmSource());
        $this->assertEquals('test_utm_term', $download->getUtmTerm());
        $this->assertEquals('200', $download->getCode());
        $this->assertEquals($ipAddress, $download->getIpAddress());
        $this->assertEquals($lead, $download->getLead());
        $this->assertEquals($asset, $download->getAsset());
        $this->assertEquals('http://localhost', $download->getReferer());
    }
}
