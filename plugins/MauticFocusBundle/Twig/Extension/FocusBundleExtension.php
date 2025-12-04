<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFocusBundle\Twig\Extension;

use MatthiasMullie\Minify;
use MauticPlugin\MauticFocusBundle\Model\FocusModel;

class FocusBundleExtension
{
    #[\Twig\Attribute\AsTwigTest('color light')]
    public function isColorLight(string $hexColor): bool
    {
        return FocusModel::isLightColor($hexColor);
    }

    #[\Twig\Attribute\AsTwigFilter('less_compile', isSafe: ['all'])]
    public function compileLess(string $less): string
    {
        return (new \Less_Parser())->parse($less)->getCss();
    }

    #[\Twig\Attribute\AsTwigFilter('css_minify', isSafe: ['all'])]
    public function minifyCss(string $css): string
    {
        return (new Minify\CSS($css))->minify();
    }
}
