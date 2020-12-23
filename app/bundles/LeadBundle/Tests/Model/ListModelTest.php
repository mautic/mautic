<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Model;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use Mautic\CategoryBundle\Model\CategoryModel;
use Mautic\CoreBundle\Helper\CacheStorageHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\LeadListRepository;
use Mautic\LeadBundle\Helper\ListCacheHelper;
use Mautic\LeadBundle\Model\ListModel;
use Mautic\LeadBundle\Segment\ContactSegmentService;
use Mautic\LeadBundle\Segment\Stat\SegmentChartQueryFactory;
use Monolog\Logger;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Cache\InvalidArgumentException;
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
     * @var CacheStorageHelper|MockObject
     */
    private $cacheStorageHelperMock;

    protected function setUp(): void
    {
        defined('MAUTIC_ENV') || define('MAUTIC_ENV', 'test');
        defined('MAUTIC_TABLE_PREFIX') || define('MAUTIC_TABLE_PREFIX', getenv('MAUTIC_DB_PREFIX') ?: '');

        $eventDispatcherInterfaceMock = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcherInterfaceMock->method('dispatch');
        $loggerMock                   = $this->createMock(Logger::class);
        $translatorMock               = $this->createMock(Translator::class);
        $this->leadListRepositoryMock = $this->createMock(LeadListRepository::class);

        $entityManagerMock = $this->createMock(EntityManager::class);
        $entityManagerMock->method('getRepository')
            ->willReturn($this->leadListRepositoryMock);

        $coreParametersHelperMock     = $this->createMock(CoreParametersHelper::class);
        $contactSegmentServiceMock    = $this->createMock(ContactSegmentService::class);
        $segmentChartQueryFactoryMock = $this->createMock(SegmentChartQueryFactory::class);
        $this->cacheStorageHelperMock = $this->createMock(CacheStorageHelper::class);

        $this->model = new ListModel(
            $coreParametersHelperMock,
            $contactSegmentServiceMock,
            $segmentChartQueryFactoryMock,
            $this->cacheStorageHelperMock
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

        $mockListModel = $this->getMockBuilder(ListModel::class)
            ->setConstructorArgs([$categoryModel, $coreParametersHelper, $leadSegment, $segmentChartQueryFactory, $requestStack])
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

    /**
     * @throws ORMException
     */
    public function testSegmentRebuildCountCacheGetsDeleted(): void
    {
        $leadList = new class() extends LeadList {
            public function getId(): int
            {
                return 765;
            }
        };
        $leadList->setFilters(['foo' => ['bar']]);

        $cacheKey = ListCacheHelper::generateCacheKey($leadList->getId());
        $this->cacheStorageHelperMock
            ->expects(self::once())
            ->method('has')
            ->with($cacheKey)
            ->willReturn(true);

        self::assertSame(0, $this->model->rebuildListLeads($leadList));
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testGetLeadsCount(): void
    {
        $segmentId = 765;
        $count     = 422;
        $cacheKey  = ListCacheHelper::generateCacheKey($segmentId);

        $this->cacheStorageHelperMock
            ->method('has')
            ->with($cacheKey)
            ->willReturn(true);
        $this->cacheStorageHelperMock
            ->method('get')
            ->with($cacheKey)
            ->willReturn($count);
        $this->leadListRepositoryMock
            ->method('getLeadCount')
            ->with($segmentId, $this->cacheStorageHelperMock)
            ->willReturn($count);

        self::assertSame([$segmentId => $count], $this->model->getLeadsCount([$segmentId]));
    }

    public function testAjaxGetLeadsCount(): void
    {
        $segmentId = 765;
        $count     = 422;

        $this->leadListRepositoryMock->expects(self::once())
            ->method('getLeadCount')
            ->with($segmentId, $this->cacheStorageHelperMock)
            ->willReturn($count);

        self::assertSame([$segmentId => $count], $this->model->getLeadsCount([$segmentId], true));
    }

    /**
     * @throws DBALException
     */
    public function testLeadListExists(): void
    {
        $segmentId = 765;
        $this->leadListRepositoryMock->expects(self::once())
            ->method('leadListExists')
            ->with($segmentId)
            ->willReturn(true);

        self::assertTrue($this->model->leadListExists($segmentId));
    }
}
