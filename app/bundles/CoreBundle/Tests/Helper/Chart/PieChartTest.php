<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Helper\Chart;

use Mautic\CoreBundle\Helper\Chart\PieChart;
use PHPUnit\Framework\TestCase;

class PieChartTest extends TestCase
{
    /**
     * @var PieChart
     */
    private $pieChart;

    protected function setUp(): void
    {
        $this->pieChart = new PieChart();
    }

    public function testSetDatasetIncreasesTotalCount(): void
    {
        $this->pieChart->setDataset('Label 1', 10);
        $this->pieChart->setDataset('Label 2', 20);

        $reflection         = new \ReflectionClass($this->pieChart);
        $totalCountProperty = $reflection->getProperty('totalCount');
        $totalCountProperty->setAccessible(true);

        $totalCount = $totalCountProperty->getValue($this->pieChart);

        $this->assertEquals(30, $totalCount);
    }

    public function testSetDatasetStoresDataCorrectly(): void
    {
        $this->pieChart->setDataset('Label 1', 10);
        $this->pieChart->setDataset('Label 2', 20);

        $renderedChart  = $this->pieChart->render(false);
        $renderedChart2 = $this->pieChart->render();

        $this->assertCount(2, $renderedChart['labels']);
        $this->assertCount(2, $renderedChart['datasets'][0]['data']);

        $this->assertSame(['Label 1', 'Label 2'], $renderedChart['labels']);
        $this->assertSame(['Label 1; 10x, 33.33%', 'Label 2; 20x, 66.67%'], $renderedChart2['labels']);
        $this->assertSame([10, 20], $renderedChart['datasets'][0]['data']);
    }

    public function testRenderWithCounts(): void
    {
        $this->pieChart->setDataset('Label 1', 10);
        $this->pieChart->setDataset('Label 2', 20);

        $renderedChart = $this->pieChart->render(true);

        $this->assertSame('Label 1; 10x, 33.33%', $renderedChart['labels'][0]);
        $this->assertSame('Label 2; 20x, 66.67%', $renderedChart['labels'][1]);
    }

    public function testRenderWithoutCounts(): void
    {
        $this->pieChart->setDataset('Label 1', 10);
        $this->pieChart->setDataset('Label 2', 20);

        $renderedChart = $this->pieChart->render(false);

        $this->assertSame('Label 1', $renderedChart['labels'][0]);
        $this->assertSame('Label 2', $renderedChart['labels'][1]);
    }

    public function testBuildFullLabel(): void
    {
        $this->pieChart->setDataset('Label 1', 10);
        $this->pieChart->setDataset('Label 2', 20);

        $fullLabel = $this->pieChart->buildFullLabel('Label 1', 10);

        $this->assertSame('Label 1; 10x, 33.33%', $fullLabel);
    }

    public function testBuildFullLabelWithoutTotalCount(): void
    {
        $emptyPieChart = new PieChart();
        $fullLabel     = $emptyPieChart->buildFullLabel('Label 1', 10);

        $this->assertSame('Label 1', $fullLabel);
    }

    public function testGenerateColors(): void
    {
        $colors = $this->pieChart->render()['datasets'][0];

        $this->assertArrayHasKey('backgroundColor', $colors);
        $this->assertArrayHasKey('hoverBackgroundColor', $colors);
    }
}
