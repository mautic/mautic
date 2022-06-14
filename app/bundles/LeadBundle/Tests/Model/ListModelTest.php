<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Model;

use Doctrine\ORM\EntityManager;
use Mautic\CategoryBundle\Model\CategoryModel;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\LeadListRepository;
use Mautic\LeadBundle\Helper\SegmentCountCacheHelper;
use Mautic\LeadBundle\Model\ListModel;
use Mautic\LeadBundle\Segment\ContactSegmentService;
use Mautic\LeadBundle\Segment\Stat\SegmentChartQueryFactory;
use Monolog\Logger;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\Translator;

class ListModelTest extends TestCase
{
    /**
     * @var MockObject
     */
    protected $fixture;

    /**
     * @var ListModel
     */
    private $model;

    /**
     * @var LeadListRepository|MockObject
     */
    private $leadListRepositoryMock;

    /**
     * @var SegmentCountCacheHelper|MockObject
     */
    private $segmentCountCacheHelper;

    /**
     * @var ContactSegmentService|MockObject
     */
    private $contactSegmentServiceMock;

    protected function setUp(): void
    {
        $eventDispatcherInterfaceMock = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcherInterfaceMock->method('dispatch');
        $loggerMock                   = $this->createMock(Logger::class);
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

        $this->model = new ListModel(
            $categoryModelMock,
            $coreParametersHelperMock,
            $this->contactSegmentServiceMock,
            $segmentChartQueryFactoryMock,
            $requestStackMock,
            $this->segmentCountCacheHelper
        );
        $this->model->setDispatcher($eventDispatcherInterfaceMock);
        $this->model->setLogger($loggerMock);
        $this->model->setTranslator($translatorMock);
        $this->model->setEntityManager($entityManagerMock);
    }

    /**
     * @dataProvider sourceTypeTestDataProvider
     *
     * @param string|null $sourceType
     */
    public function testGetSourceLists(array $getLookupResultsReturn, $sourceType, array $expected): void
    {
        $this->prepareMockForTestGetSourcesLists($getLookupResultsReturn);
        $result = $this->fixture->getSourceLists($sourceType);
        $this->assertEquals($expected, $result);
    }

    private function prepareMockForTestGetSourcesLists(array $getLookupResultsReturn): void
    {
        $coreParametersHelper     = $this->getMockBuilder(CoreParametersHelper::class)->disableOriginalConstructor()->getMock();
        $leadSegment              = $this->getMockBuilder(ContactSegmentService::class)->disableOriginalConstructor()->getMock();
        $segmentChartQueryFactory = $this->getMockBuilder(SegmentChartQueryFactory::class)->disableOriginalConstructor()->getMock();
        $requestStack             = $this->getMockBuilder(RequestStack::class)->disableOriginalConstructor()->getMock();
        $categoryModel            = $this->getMockBuilder(CategoryModel::class)->disableOriginalConstructor()->getMock();
        $categoryModel->expects($this->once())->method('getLookupResults')->willReturn($getLookupResultsReturn);
        $segmentCountCacheHelperMock = $this->createMock(SegmentCountCacheHelper::class);

        $mockListModel = $this->getMockBuilder(ListModel::class)
            ->setConstructorArgs([$categoryModel, $coreParametersHelper, $leadSegment, $segmentChartQueryFactory, $requestStack, $segmentCountCacheHelperMock])
            ->addMethods([])
            ->getMock();

        $this->fixture = $mockListModel;
    }

    public function sourceTypeTestDataProvider(): array
    {
        return [
            [
                [],
                'categories',
                [],
            ],
            [
                [
                    0 => ['id' => 1, 'title' => 'Segment Test Category 1', 'bundle' => 'segment'],
                    1 => ['id' => 2, 'title' => 'Segment Test Category 2', 'bundle' => 'segment'],
                ],
                null,
                [
                    'categories' => [
                        1 => 'Segment Test Category 1',
                        2 => 'Segment Test Category 2',
                    ],
                ],
            ],
            [
                [
                    0 => ['id' => 1, 'title' => 'Segment Test Category 1', 'bundle' => 'segment'],
                    1 => ['id' => 2, 'title' => 'Segment Test Category 2', 'bundle' => 'segment'],
                ],
                'categories',
                [
                    1 => 'Segment Test Category 1',
                    2 => 'Segment Test Category 2',
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
            ->expects(self::once())
            ->method('getLeadCount')
            ->with($segmentId)
            ->willReturn($leadCount);

        $this->segmentCountCacheHelper
            ->expects(self::once())
            ->method('setSegmentContactCount')
            ->with($segmentId, $leadCount);

        $newLeadsCount[$segmentId] = [
            'maxId' => 0,
            'count' => 0,
        ];

        $this->contactSegmentServiceMock
            ->expects(self::once())
            ->method('getNewLeadListLeadsCount')
            ->with($leadList)
            ->willReturn($newLeadsCount);

        $orphanLeadsCount[$segmentId] = [
            'maxId' => 0,
            'count' => 0,
        ];

        $this->contactSegmentServiceMock
            ->expects(self::once())
            ->method('getOrphanedLeadListLeadsCount')
            ->with($leadList)
            ->willReturn($orphanLeadsCount);

        self::assertSame(0, $this->model->rebuildListLeads($leadList));

        $this->segmentCountCacheHelper
            ->expects(self::once())
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
            ->expects(self::once())
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
            ->expects(self::once())
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
            ->expects(self::once())
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
            ->expects(self::once())
            ->method('hasSegmentContactCount')
            ->with($segmentId)
            ->willReturn(true);

        $this->segmentCountCacheHelper
            ->expects(self::once())
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
            ->expects(self::once())
            ->method('hasSegmentContactCount')
            ->with($segmentId)
            ->willReturn(false);

        $this->leadListRepositoryMock
            ->expects(self::once())
            ->method('getLeadCount')
            ->with($segmentId)
            ->willReturn($leadCount);

        $leadCounts = $this->model->getSegmentContactCount([$segmentId]);

        self::assertSame([$segmentId => $leadCount], $leadCounts);
    }

    public function testLeadListExists(): void
    {
        $leadList  = $this->mockLeadList(765);
        $segmentId = $leadList->getId();
        $this->leadListRepositoryMock->expects(self::once())
            ->method('leadListExists')
            ->with($segmentId)
            ->willReturn(true);

        self::assertTrue($this->model->leadListExists($segmentId));
    }

    private function mockLeadList(int $id): LeadList
    {
        return new class($id) extends LeadList {
            /**
             * @var int
             */
            private $id;

            public function __construct(int $id)
            {
                $this->id = $id;
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
            /**
             * @var int
             */
            private $id;

            public function __construct(int $id)
            {
                $this->id = $id;
                parent::__construct();
            }

            public function getId(): int
            {
                return $this->id;
            }
        };
    }
}
