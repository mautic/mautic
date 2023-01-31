<?php

namespace Mautic\InstallBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * TwigExtension class.
 */
class TwigExtension extends AbstractExtension
{
    /**
     * getFilters function.
     *
     * @return mixed[]
     */
    public function getFilters()
    {
        return [
            new TwigFilter('phpversion', [$this, 'phpversion']),
        ];
    }

    /**
     * @return false|string
     */
    public function phpversion(string $value = '')
    {
        return phpversion($value);
    }
}
