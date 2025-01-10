<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Helper\Chart;

use Mautic\CoreBundle\Helper\Chart\SeriesPieChart;
use PHPUnit\Framework\TestCase;

class SeriesPieChartTest extends TestCase
{
    private SeriesPieChart $chart;

    protected function setUp(): void
    {
        $this->chart = new SeriesPieChart();
    }

    public function testSetDataset(): void
    {
        $dataset = [10, 20, 30];

        $this->chart->setDataset($dataset);

        $this->assertSame([$dataset], $this->chart->getDatasets());
        $this->assertEquals(60, $this->chart->getTotalCount());
    }

    public function testBuildFullLabelWithTotalCount(): void
    {
        $this->chart->setTotalCount(100);
        $label = $this->chart->buildFullLabel('Test Label', 25);

        $this->assertEquals('Test Label; 25x, 25%', $label);
    }

    public function testBuildFullLabelWithoutTotalCount(): void
    {
        $label = $this->chart->buildFullLabel('Test Label', 25);

        $this->assertEquals('Test Label', $label);
    }

    public function testRender(): void
    {
        $this->chart->setLabels(['Label 1', 'Label 2']);
        $this->chart->setDataset([10, 20]);

        $result = $this->chart->render();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('labels', $result);
        $this->assertArrayHasKey('datasets', $result);

        $this->assertSame(['Label 1', 'Label 2'], $result['labels']);

        $this->assertCount(1, $result['datasets']);
        $this->assertCount(2, $result['datasets'][0]['data']);
        $this->assertEquals([10, 20], $result['datasets'][0]['data']);
    }

    public function testSetTotalCount(): void
    {
        $this->chart->setTotalCount(150);
        $this->assertEquals(150, $this->chart->getTotalCount());
    }
}
