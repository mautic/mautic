<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Helper;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class ThemeHelperRuntimeBackedFilterExtension extends AbstractExtension
{
    /**
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('runtime_backed', [ThemeHelperRuntimeBackedFilterRuntime::class, 'transform']),
        ];
    }
}
