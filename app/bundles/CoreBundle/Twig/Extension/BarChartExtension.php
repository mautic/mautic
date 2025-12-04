<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Mautic\CoreBundle\Helper\Chart\BarChart;

final class BarChartExtension
{
    /**
     * @param array<string> $labels
     */
    #[\Twig\Attribute\AsTwigFunction('barChartInitialize')]
    public function createNewChart(array $labels): BarChart
    {
        return new BarChart($labels);
    }
}
