<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Mautic\CoreBundle\Helper\Chart\BarChart;
use Twig\Attribute\AsTwigFunction;

final class BarChartExtension
{
    /**
     * @param array<string> $labels
     */
    #[AsTwigFunction(name: 'barChartInitialize')]
    public function createNewChart(array $labels): BarChart
    {
        return new BarChart($labels);
    }
}
