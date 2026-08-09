<?php

declare(strict_types=1);

namespace Mautic\InstallBundle\Twig;

use Twig\Attribute\AsTwigFilter;

/**
 * TwigExtension class.
 */
final class TwigExtension
{
    #[AsTwigFilter(name: 'phpversion')]
    public function phpversion(string $value = ''): string|bool
    {
        return phpversion($value);
    }
}
