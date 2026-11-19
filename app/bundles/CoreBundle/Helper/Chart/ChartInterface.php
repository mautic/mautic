<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Helper\Chart;

interface ChartInterface
{
    /**
     * Render the chart data.
     */
    public function render();
}
