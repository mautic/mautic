<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFocusBundle\Tests\Twig\Extension;

use MauticPlugin\MauticFocusBundle\Twig\Extension\FocusBundleExtension;
use PHPUnit\Framework\TestCase;

final class FocusBundleExtensionTest extends TestCase
{
    private FocusBundleExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new FocusBundleExtension();
    }

    public function testCompileLess(): void
    {
        $less = '@primarycolor: #FF7F50;@color:#800080;h2{color: @primarycolor;}h3{color: @color;}';

        $expected = <<<CSS
            h2 {
              color: #FF7F50;
            }
            h3 {
              color: #800080;
            }

            CSS;

        $this->assertSame($expected, $this->extension->compileLess($less));
    }

    public function testMinifyCss(): void
    {
        $css = 'h2 {
  color: #FF7F50;
}';

        $this->assertSame('h2{color:coral}', $this->extension->minifyCss($css));
    }

    public function testFiltersAreRegistered(): void
    {
        $names = array_map(fn (\Twig\TwigFilter $filter): string => $filter->getName(), $this->extension->getFilters());

        $this->assertSame(['less_compile', 'css_minify'], $names);
    }

    public function testColorLightTestIsRegistered(): void
    {
        $names = array_map(fn (\Twig\TwigTest $test): string => $test->getName(), $this->extension->getTests());

        $this->assertSame(['color light'], $names);
    }
}
