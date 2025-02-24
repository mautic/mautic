<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigTest;

class NumericExtension extends AbstractExtension
{
    public function getTests()
    {
        return [
            new TwigTest('numeric', fn ($value) => !is_array($value) && is_numeric($value)),
        ];
    }

    public function getFilters()
    {
        return [
            new TwigFilter('int', fn ($value): int => (int) $value),
            new TwigFilter('array', fn ($value) => (array) $value),
        ];
    }
}
