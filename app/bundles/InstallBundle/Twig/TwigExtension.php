<?php

namespace Mautic\InstallBundle\Twig;

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
