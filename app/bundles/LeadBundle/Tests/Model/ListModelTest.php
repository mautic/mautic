<?php

namespace Mautic\LeadBundle\Tests\Model;

use Mautic\CategoryBundle\Model\CategoryModel;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\LeadBundle\Model\ListModel;
use Mautic\LeadBundle\Segment\ContactSegmentService;
use Mautic\LeadBundle\Segment\Stat\SegmentChartQueryFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;

class ListModelTest extends TestCase
{
    /**
     * @var MockObject
     */
    protected $fixture;

    protected function setUp(): void
    {
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
}
