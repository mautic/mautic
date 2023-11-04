<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFocusBundle\Twig\Extension;

use MatthiasMullie\Minify;
use MauticPlugin\MauticFocusBundle\Model\FocusModel;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigTest;

class FocusBundleExtension extends AbstractExtension
{
    /**
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('less_compile', [$this, 'compileLess'], ['is_safe' => ['all']]),
            new TwigFilter('css_minify', [$this, 'minifyCss'], ['is_safe' => ['all']]),
        ];
    }

    /**
     * @return TwigTest[]
     */
    public function getTests(): array
    {
        return [
            new TwigTest('color light', fn (string $hexColor) => FocusModel::isLightColor($hexColor)),
        ];
    }

    public function compileLess(string $less): string
    {
        $parser = new \Less_Parser();

        return $parser->parse($less)->getCss();
    }

    public function minifyCss(string $css): string
    {
        return (new Minify\CSS($css))->minify();
    }
}
