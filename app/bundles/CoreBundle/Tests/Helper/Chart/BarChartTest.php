<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Helper\Chart;

use Mautic\CoreBundle\Helper\Chart\BarChart;
use PHPUnit\Framework\TestCase;

class BarChartTest extends TestCase
{
    private BarChart $barChart;

    protected function setUp(): void
    {
        $this->barChart = new BarChart(['Label 1', 'Label 2']);
    }

    public function testConstructorSetsLabels(): void
    {
        $this->assertSame(['Label 1', 'Label 2'], $this->barChart->render()['labels']);
    }

    public function testSetDataset(): void
    {
        $this->barChart->setDataset('Dataset 1', [10, 20]);

        $renderedChart = $this->barChart->render();

        $this->assertCount(1, $renderedChart['datasets']);
        $this->assertSame('Dataset 1', $renderedChart['datasets'][0]['label']);
        $this->assertSame([10, 20], $renderedChart['datasets'][0]['data']);
    }

    public function testSetDatasetWithOrder(): void
    {
        $this->barChart->setDataset('Dataset 1', [10, 20], 1);
        $this->barChart->setDataset('Dataset 2', [30, 40], 0);

        $renderedChart = $this->barChart->render();

        $this->assertCount(2, $renderedChart['datasets']);
        $this->assertSame('Dataset 2', $renderedChart['datasets'][0]['label']);
        $this->assertSame('Dataset 1', $renderedChart['datasets'][1]['label']);
    }

    public function testGenerateColors(): void
    {
        $colors = $this->barChart->generateColors(0);

        $this->assertArrayHasKey('fill', $colors);
        $this->assertArrayHasKey('backgroundColor', $colors);
        $this->assertArrayHasKey('borderColor', $colors);
        $this->assertArrayHasKey('pointHoverBackgroundColor', $colors);
        $this->assertArrayHasKey('pointHoverBorderColor', $colors);
    }

    public function testRenderSortsDatasetsByOrder(): void
    {
        $this->barChart->setDataset('Dataset 1', [10, 20], 2);
        $this->barChart->setDataset('Dataset 2', [30, 40], 1);
        $this->barChart->setDataset('Dataset 3', [50, 60], 0);

        $renderedChart = $this->barChart->render();

        $this->assertSame('Dataset 3', $renderedChart['datasets'][0]['label']);
        $this->assertSame('Dataset 2', $renderedChart['datasets'][1]['label']);
        $this->assertSame('Dataset 1', $renderedChart['datasets'][2]['label']);
    }
}
