<?php

namespace Mautic\InstallBundle\Twig;

class TwigExtension
{
    #[\Twig\Attribute\AsTwigFilter('phpversion')]
    public function phpversion(string $value = ''): string|bool
    {
        return phpversion($value);
    }
}
