<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFocusBundle\Twig\Extension;

use MatthiasMullie\Minify;
use MauticPlugin\MauticFocusBundle\Model\FocusModel;
use Twig\TwigTest;

class FocusBundleExtension
{
    /**
     * @return TwigTest[]
     */
    public function getTests(): array
    {
        return [
            new TwigTest('color light', fn (string $hexColor) => FocusModel::isLightColor($hexColor)),
        ];
    }

    #[\Twig\Attribute\AsTwigFilter('less_compile', isSafe: ['all'])]
    public function compileLess(string $less): string
    {
        $parser = new \Less_Parser();

        return $parser->parse($less)->getCss();
    }

    #[\Twig\Attribute\AsTwigFilter('css_minify', isSafe: ['all'])]
    public function minifyCss(string $css): string
    {
        return (new Minify\CSS($css))->minify();
    }
}
