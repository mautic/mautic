<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Model;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Mautic\CategoryBundle\Model\CategoryModel;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Entity\DoNotContactRepository;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\LeadListRepository;
use Mautic\LeadBundle\Helper\SegmentCountCacheHelper;
use Mautic\LeadBundle\Model\ListModel;
use Mautic\LeadBundle\Segment\ContactSegmentService;
use Mautic\LeadBundle\Segment\Stat\SegmentChartQueryFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class ListModelTest extends TestCase
{
    protected ?MockObject $fixture = null;

    private ListModel $model;

    /**
     * @var MockObject&LeadListRepository
     */
    private MockObject $leadListRepositoryMock;

    /**
     * @var MockObject&SegmentCountCacheHelper
     */
    private MockObject $segmentCountCacheHelper;

    /**
     * @var MockObject&ContactSegmentService
     */
    private MockObject $contactSegmentServiceMock;

    protected function setUp(): void
    {
        $eventDispatcherInterfaceMock = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcherInterfaceMock->method('dispatch');
        $loggerMock                   = $this->createMock(LoggerInterface::class);
        $translatorMock               = $this->createMock(Translator::class);
        $this->leadListRepositoryMock = $this->createMock(LeadListRepository::class);

        $entityManagerMock = $this->createMock(EntityManager::class);
        $entityManagerMock->method('getRepository')
            ->willReturn($this->leadListRepositoryMock);

        $coreParametersHelperMock              = $this->createMock(CoreParametersHelper::class);
        $this->contactSegmentServiceMock       = $this->createMock(ContactSegmentService::class);
        $segmentChartQueryFactoryMock          = $this->createMock(SegmentChartQueryFactory::class);
        $this->segmentCountCacheHelper         = $this->createMock(SegmentCountCacheHelper::class);
        $requestStackMock                      = $this->createMock(RequestStack::class);
        $categoryModelMock                     = $this->createMock(CategoryModel::class);
        $doNotContactRepositoryMock            = $this->createMock(DoNotContactRepository::class);

        $this->model = new ListModel(
            $categoryModelMock,
            $coreParametersHelperMock,
            $this->contactSegmentServiceMock,
            $segmentChartQueryFactoryMock,
            $requestStackMock,
            $this->segmentCountCacheHelper,
            $doNotContactRepositoryMock,
            $entityManagerMock,
            $this->createStub(CorePermissions::class),
            $eventDispatcherInterfaceMock,
            $this->createStub(UrlGeneratorInterface::class),
            $translatorMock,
            $this->createStub(UserHelper::class),
            $loggerMock
        );
    }

    /**
     * @param array<int, mixed> $getLookupResultsReturn
     * @param array<int, mixed> $expected
     */
    #[DataProvider('sourceTypeTestDataProvider')]
    public function testGetSourceLists(array $getLookupResultsReturn, ?string $sourceType, array $expected): void
    {
        $this->prepareMockForTestGetSourcesLists($getLookupResultsReturn);
        $result = $this->fixture->getSourceLists($sourceType);
        $this->assertEquals($expected, $result);
    }

    /** @param array<int, mixed> $getLookupResultsReturn */
    private function prepareMockForTestGetSourcesLists(array $getLookupResultsReturn): void
    {
        $coreParametersHelper     = $this->createMock(CoreParametersHelper::class);
        $leadSegment              = $this->createMock(ContactSegmentService::class);
        $segmentChartQueryFactory = $this->createMock(SegmentChartQueryFactory::class);
        $requestStack             = $this->createMock(RequestStack::class);
        $categoryModel            = $this->createMock(CategoryModel::class);
        $categoryModel->expects($this->once())->method('getLookupResults')->willReturn($getLookupResultsReturn);
        $segmentCountCacheHelperMock = $this->createMock(SegmentCountCacheHelper::class);
        $doNotContactRepositoryMock  = $this->createMock(DoNotContactRepository::class);

        $mockListModel = $this->getMockBuilder(ListModel::class)
            ->setConstructorArgs([
                $categoryModel,
                $coreParametersHelper,
                $leadSegment,
                $segmentChartQueryFactory,
                $requestStack,
                $segmentCountCacheHelperMock,
                $doNotContactRepositoryMock,
                $this->createStub(EntityManagerInterface::class),
                $this->createStub(CorePermissions::class),
                $this->createStub(EventDispatcherInterface::class),
                $this->createStub(UrlGeneratorInterface::class),
                $this->createStub(Translator::class),
                $this->createStub(UserHelper::class),
                $this->createStub(LoggerInterface::class)])
            ->onlyMethods([])
            ->getMock();

        $this->fixture = $mockListModel;
    }

    /** @return array<int, array{0: array<int, mixed>, 1: string|null, 2: array<string|int, mixed>}> */
    public static function sourceTypeTestDataProvider(): array
    {
        return [
            [
                [],
                'categories',
                [],
            ],
            [
                [
                    0 => [
                        'id'     => 1,
                        'title'  => 'Segment Test Category 1',
                        'alias'  => 'Alias Test Category 1',
                        'bundle' => 'segment',
                    ],
                    1 => [
                        'id'     => 2,
                        'title'  => 'Segment Test Category 2',
                        'alias'  => 'Alias Test Category 2',
                        'bundle' => 'segment',
                    ],
                ],
                null,
                [
                    'categories' => [
                        'Alias Test Category 1' => 'Segment Test Category 1',
                        'Alias Test Category 2' => 'Segment Test Category 2',
                    ],
                ],
            ],
            [
                [
                    0 => [
                        'id'     => 1,
                        'title'  => 'Segment Test Category 1',
                        'alias'  => 'Alias Test Category 1',
                        'bundle' => 'segment',
                    ],
                    1 => [
                        'id'     => 2,
                        'title'  => 'Segment Test Category 2',
                        'alias'  => 'Alias Test Category 2',
                        'bundle' => 'segment',
                    ],
                ],
                'categories',
                [
                    'Alias Test Category 1' => 'Segment Test Category 1',
                    'Alias Test Category 2' => 'Segment Test Category 2',
                ],
            ],
            [
                [],
                null,
                [
                    'categories' => [],
                ],
            ],
        ];
    }

    public function testSegmentRebuildCountCacheGetsUpdated(): void
    {
        $leadList  = $this->mockLeadList(765);
        $segmentId = $leadList->getId();
        $leadCount = 433;

        $this->leadListRepositoryMock
            ->expects($this->once())
            ->method('getLeadCount')
            ->with($segmentId)
            ->willReturn($leadCount);

        $this->segmentCountCacheHelper
            ->expects($this->once())
            ->method('setSegmentContactCount')
            ->with($segmentId, $leadCount);

        $newLeadsCount[$segmentId] = [
            'maxId' => 0,
            'count' => 0,
        ];

        $this->contactSegmentServiceMock
            ->expects($this->once())
            ->method('getNewLeadListLeadsCount')
            ->with($leadList)
            ->willReturn($newLeadsCount);

        $orphanLeadsCount[$segmentId] = [
            'maxId' => 0,
            'count' => 0,
        ];

        $this->contactSegmentServiceMock
            ->expects($this->once())
            ->method('getOrphanedLeadListLeadsCount')
            ->with($leadList)
            ->willReturn($orphanLeadsCount);

        self::assertSame(0, $this->model->rebuildListLeads($leadList));

        $this->segmentCountCacheHelper
            ->expects($this->once())
            ->method('getSegmentContactCount')
            ->with($segmentId)
            ->willReturn($leadCount);

        $leadCounts = $this->model->getSegmentContactCountFromCache([$segmentId]);

        self::assertSame([$segmentId => $leadCount], $leadCounts);
    }

    public function testRemoveLeadWillDecrementCacheCounter(): void
    {
        $leadList         = $this->mockLeadList(765);
        $segmentId        = $leadList->getId();
        $lead             = $this->mockLead(100);
        $currentLeadCount = 100;

        $this->model->removeLead($lead, $leadList);

        $this->segmentCountCacheHelper
            ->expects($this->once())
            ->method('getSegmentContactCount')
            ->with($segmentId)
            ->willReturn($currentLeadCount - 1);

        $leadCounts = $this->model->getSegmentContactCountFromCache([$segmentId]);

        self::assertSame([$segmentId => $currentLeadCount - 1], $leadCounts);
    }

    public function testGetSegmentContactCountFromCache(): void
    {
        $leadList  = $this->mockLeadList(765);
        $segmentId = $leadList->getId();
        $leadCount = 100;

        $this->segmentCountCacheHelper
            ->expects($this->once())
            ->method('getSegmentContactCount')
            ->with($segmentId)
            ->willReturn($leadCount);

        $leadCounts = $this->model->getSegmentContactCountFromCache([$segmentId]);

        self::assertSame([$segmentId => $leadCount], $leadCounts);
    }

    public function testAddLeadWillIncrementCacheCounter(): void
    {
        $leadList         = $this->mockLeadList(765);
        $segmentId        = $leadList->getId();
        $lead             = $this->mockLead(100);
        $currentLeadCount = 100;

        $this->model->addLead($lead, $leadList);

        $this->segmentCountCacheHelper
            ->expects($this->once())
            ->method('getSegmentContactCount')
            ->with($segmentId)
            ->willReturn($currentLeadCount + 1);

        $leadCounts = $this->model->getSegmentContactCountFromCache([$segmentId]);

        self::assertSame([$segmentId => $currentLeadCount + 1], $leadCounts);
    }

    public function testGetSegmentContactCountFromDatabaseHavingCache(): void
    {
        $leadList  = $this->mockLeadList(765);
        $segmentId = $leadList->getId();
        $leadCount = 100;

        $this->segmentCountCacheHelper
            ->expects($this->once())
            ->method('hasSegmentContactCount')
            ->with($segmentId)
            ->willReturn(true);

        $this->segmentCountCacheHelper
            ->expects($this->once())
            ->method('getSegmentContactCount')
            ->with($segmentId)
            ->willReturn($leadCount);

        $leadCounts = $this->model->getSegmentContactCount([$segmentId]);

        self::assertSame([$segmentId => $leadCount], $leadCounts);
    }

    public function testGetSegmentContactCountFromDatabase(): void
    {
        $leadList  = $this->mockLeadList(765);
        $segmentId = $leadList->getId();
        $leadCount = 100;

        $this->segmentCountCacheHelper
            ->expects($this->once())
            ->method('hasSegmentContactCount')
            ->with($segmentId)
            ->willReturn(false);

        $this->leadListRepositoryMock
            ->expects($this->once())
            ->method('getLeadCount')
            ->with($segmentId)
            ->willReturn($leadCount);

        $leadCounts = $this->model->getSegmentContactCount([$segmentId]);

        self::assertSame([$segmentId => $leadCount], $leadCounts);
    }

    public function testGetActiveSegmentContactCount(): void
    {
        $segmentId = 123;
        $total     = 10;
        $dnc       = 3;

        $this->leadListRepositoryMock
            ->expects($this->once())
            ->method('getLeadCount')
            ->with($segmentId)
            ->willReturn($total);

        $doNotContactRepository = $this->createMock(DoNotContactRepository::class);
        $doNotContactRepository
            ->expects($this->once())
            ->method('getCount')
            ->with(null, null, null, $segmentId)
            ->willReturn($dnc);

        $reflection = new \ReflectionClass($this->model);
        $property   = $reflection->getProperty('doNotContactRepository');
        $property->setValue($this->model, $doNotContactRepository);

        $active = $this->model->getActiveSegmentContactCount($segmentId);
        self::assertSame($total - $dnc, $active);
    }

    public function testLeadListExists(): void
    {
        $leadList  = $this->mockLeadList(765);
        $segmentId = $leadList->getId();
        $this->leadListRepositoryMock->expects($this->once())
            ->method('leadListExists')
            ->with($segmentId)
            ->willReturn(true);

        self::assertTrue($this->model->leadListExists($segmentId));
    }

    private function mockLeadList(int $id): LeadList
    {
        return new class($id) extends LeadList {
            public function __construct(
                private readonly int $id,
            ) {
                parent::__construct();
            }

            public function getId(): int
            {
                return $this->id;
            }
        };
    }

    private function mockLead(int $id): Lead
    {
        return new class($id) extends Lead {
            public function __construct(
                private readonly int $id,
            ) {
                parent::__construct();
            }

            public function getId(): int
            {
                return $this->id;
            }
        };
    }
}
