<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Mautic\CoreBundle\Helper\Chart\BarChart;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class BarChartExtension extends AbstractExtension
{
    public function getFunctions()
    {
        return [
            new TwigFunction('barChartInitialize', [$this, 'createNewChart']),
        ];
    }

    /**
     * @param array<string> $labels
     */
    public function createNewChart(array $labels): BarChart
    {
        return new BarChart($labels);
    }
}
