<?php

namespace Mautic\InstallBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * TwigExtension class.
 */
class TwigExtension
{
    #[\Twig\Attribute\AsTwigFilter(name: 'phpversion')]
    public function phpversion(string $value = ''): string|bool
    {
        return phpversion($value);
    }
}
