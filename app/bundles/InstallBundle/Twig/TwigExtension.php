<?php

declare(strict_types=1);

namespace Mautic\InstallBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * TwigExtension class.
 */
final class TwigExtension extends AbstractExtension
{
    /**
     * getFilters function.
     *
     * @return mixed[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('phpversion', $this->phpversion(...)),
        ];
    }

    public function phpversion(string $value = ''): string|bool
    {
        return phpversion($value);
    }
}
